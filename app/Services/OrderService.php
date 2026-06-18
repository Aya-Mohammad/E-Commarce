<?php
 
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
 
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;

use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendCancellationNotificationJob;
use Illuminate\Support\Facades\RateLimiter;


class OrderService
{
    public function getUserOrders()
    {
        $page = request('page', 1);
 
        return Cache::remember(
            "user_orders_" . auth()->id() . "_page_" . $page, 60,
            function () {
                return Order::with('items.product')
                    ->where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
            }
        );
    }

    private function getLogChannel(): string
    {
        $testType = request()->header('X-Test-Type', 'order_stress');
        $operation = request()->header('X-Operation');

        if ($testType === 'combined_100') {
            return match ($operation) {
                'order' => 'combined_order',
                default => 'combined',
            };
        }
        return match ($testType) {
            'order_race'      => 'order_race',
            'order_duplicate' => 'order_duplicate',
            default           => 'order_stress',
        };
    }

    public function placeOrder()
    {
        $isOptimized = config('app.strict_nfr_mode', true);
        if ($isOptimized) { return $this->placeOrderOptimized(); }
        return $this->placeOrderVulnerable();
    }

    private function placeOrderVulnerable()
    {
        $channel = $this->getLogChannel();
        $userId = auth()->id();

        Log::channel($channel)->info("USER {$userId} START vulnerable checkout");
        $cartItems = Cart::where('user_id', $userId)->with('product')->get();
        Log::channel($channel)->info("USER {$userId} CART ITEMS COUNT = " . $cartItems->count());

        if ($cartItems->isEmpty()) {
            return ['error' => 'Cart is empty', 'status' => 422];
        }

        $productIds = $cartItems->pluck('product_id')->unique()->values();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $preparedItems = [];
        $totalPrice    = 0;

        foreach ($cartItems as $cartItem) {

            $product = $products->get($cartItem->product_id);
            if (request()->header('X-Test-Type') !== 'combined_100') {
                sleep(1);
                Log::channel($channel)->info("USER {$userId} AFTER SLEEP");
            }

            Log::channel($channel)->info("USER {$userId} READ stock={$product->quantity}");

            if (!$product || $product->quantity < $cartItem->quantity) {
                return [
                    'error' => "Not enough stock for: {$product?->name}",
                    'status' => 422
                ];
            }

            $newQuantity = $product->quantity - $cartItem->quantity;

            Log::channel($channel)->info("USER {$userId} WRITE new_stock={$newQuantity}");

            $preparedItems[] = [
                'product_id'    => $product->id,
                'quantity'      => $cartItem->quantity,
                'price'         => (float) $product->price,
                'current_stock' => $product->quantity,
            ];

            $totalPrice += (float) $product->price * $cartItem->quantity;
        }
        $order = Order::create([
            'user_id'     => $userId,
            'total_price' => $totalPrice,
            'status'      => 'pending',
        ]);

        foreach ($preparedItems as $item) {

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'price'      => $item['price'],
            ]);

            $newQuantity = $item['current_stock'] - $item['quantity'];
            Product::where('id', $item['product_id'])->update(['quantity' => $newQuantity]);
        }

        // Cart::where('user_id', $userId)->delete();

        return $order->load('items.product');
    }

    private function placeOrderOptimized()
    {
        $channel = $this->getLogChannel();
        $globalExecuted = RateLimiter::attempt(
            'global-system-orders',
            $maxOrdersPerMinute = 1000,
            function () {},
            $decaySeconds = 60
        );

        if (!$globalExecuted) {
            return response()->json([
                'success' => false,
                'message' => 'Too Many Requests. The server is currently overloaded, please try again later.',
            ], 429);
        }

        $userId = auth()->id();

        Log::channel($channel)->info("USER {$userId} START checkout request");
        $maxCartItems = 50;

        $cartItemsCount = Cache::remember("cart_count:{$userId}", 300, function () use ($userId) {
            return Cart::where('user_id', $userId)->count();
        });

        if ($cartItemsCount > $maxCartItems) {
            return [
                'error'  => "Cart cannot exceed {$maxCartItems} different products",
                'status' => 422
            ];
        }

        $lock = Cache::lock("place_order:{$userId}", 10);

        if (!$lock->get()) {
            Log::channel($channel)->info("USER {$userId} BLOCKED by Redis lock");
            return [
                'error'  => 'Another order is being processed, please wait',
                'status' => 429
            ];
        }

        Log::channel($channel)->info("USER {$userId} ACQUIRED Redis lock");

        try {

            $order = DB::transaction(function () use ($userId, $channel) {

                Log::channel($channel)->info("USER {$userId} ENTER transaction");

                $cartItems = Cart::where('user_id', $userId)->with('product')->get();

                if ($cartItems->isEmpty()) {
                    Log::channel($channel)->info("USER {$userId} CART EMPTY");
                    return [
                        'error'  => 'Cart is empty',
                        'status' => 422
                    ];
                }

                $productIds = $cartItems
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                Log::channel($channel)->info("USER {$userId} WAITING FOR DB LOCK");

                $products = Product::whereIn('id', $productIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                Log::channel($channel)->info("USER {$userId} ACQUIRED DB LOCK");

                $preparedItems = [];
                $totalPrice    = 0;

                foreach ($cartItems as $cartItem) {

                    $product = $products->get($cartItem->product_id);
                    Log::channel($channel)->info("USER {$userId} READ stock={$product->quantity}");

                    if (!$product || $product->quantity < $cartItem->quantity) {

                        Log::channel($channel)->info("USER {$userId} FAILED insufficient stock={$product?->quantity}");

                        return [
                            'error'  => "Not enough stock for product: {$product?->name}",
                            'status' => 422
                        ];
                    }

                    $newStock = $product->quantity - $cartItem->quantity;

                    Log::channel($channel)->info("USER {$userId} WRITE new_stock={$newStock}");

                    $price = (float) $product->price;

                    $preparedItems[] = [
                        'product'    => $product,
                        'product_id' => $product->id,
                        'quantity'   => $cartItem->quantity,
                        'price'      => $price,
                    ];

                    $totalPrice += $price * $cartItem->quantity;
                }

                $order = Order::create([
                    'user_id'     => $userId,
                    'total_price' => $totalPrice,
                    'status'      => 'pending',
                ]);

                $orderItems = [];

                foreach ($preparedItems as $item) {

                    $orderItems[] = [
                        'order_id'   => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $item['product']->decrement('quantity', $item['quantity']);

                    Log::channel($channel)->info("USER {$userId} DECREMENTED product={$item['product_id']}");
                }

                OrderItem::insert($orderItems);

                Cart::where('user_id', $userId)->delete();

                DB::afterCommit(function () use ($order, $userId) {
                    
                    Cache::forget("cart_count:{$userId}");
                    Cache::forget("cart:user:{$userId}:page:1");
                    Cache::forget("user_orders_{$userId}_page_1");

                    SendOrderConfirmationJob::dispatch($order);
                    GenerateInvoiceJob::dispatch($order);
                });

                return $order->load('items.product');
            });

            return $order;

        } finally {
            Log::channel($channel)->info("USER {$userId} RELEASED Redis lock");
            $lock->release();
        }
    }
 
    private function error(string $message, int $status = 400): array
    {
        return [
            'error'  => $message,
            'status' => $status,
        ];
    }

}




  