<?php
 
namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
 
class CartService
{
    private function cartCacheKey(int $userId, int $page = 1): string
    {
        return "cart:user:{$userId}:page:{$page}";
    }
 
    public function remove($id)
    {
        $userId = auth()->id();

        return DB::transaction(function () use ($id, $userId) {
 
            $cart = Cart::where('id', $id)->where('user_id', $userId)->lockForUpdate() ->first();
 
            if (!$cart) { return ['error' => 'Cart item not found', 'status' => 404]; }
 
            $cart->delete();
 
            Cache::forget($this->cartCacheKey($userId));
 
            return true;
        });
    }
 
    public function updateQuantity($id, $quantity)
    {
        if (!is_numeric($quantity) || (int) $quantity <= 0) {
            return ['error' => 'Quantity must be a positive number', 'status' => 422];
        }
 
        $quantity = (int) $quantity;
 
        return DB::transaction(function () use ($id, $quantity) {
 
            $userId = auth()->id();
 
            $cart = Cart::where('id', $id)->where('user_id', $userId)->lockForUpdate()->first();
 
            if (!$cart) { return ['error' => 'Cart item not found', 'status' => 404]; }
 
            $product = Product::find($cart->product_id);

            if (!$product) {
                return ['error' => 'Product not found', 'status' => 404];
            }

            if ($product->quantity < $quantity) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }

            if (!$product) { return ['error' => 'Product not found', 'status' => 404]; }
 
            if ($product->quantity < $quantity) { return ['error' => 'Not enough stock', 'status' => 422]; }
 
            $cart->update(['quantity' => $quantity]);
 
            Cache::forget($this->cartCacheKey($userId, 1)); 
            Cache::forget("product:detail:{$product->id}");
 
            return $cart->fresh('product');
        });
    }
 
    public function show()
    {
        $userId  = auth()->id();
        $perPage = 10;
        $page    = (int) request()->get('page', 1);
        $cacheKey = $this->cartCacheKey($userId, $page);
        $cart = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $perPage) {
            return Cart::where('user_id', $userId)
                ->with('product')
                ->paginate($perPage);
        });
 
        return [
            'data' => $cart->getCollection()->map(function ($item) {
                return [
                    'id'           => $item->id,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name ?? 'Product unavailable',
                    'quantity'     => $item->quantity,
                    'price'        => $item->product->price ?? null,
                ];
            }),
            'pagination' => [
                'current_page' => $cart->currentPage(),
                'last_page'    => $cart->lastPage(),
                'total'        => $cart->total(),
            ],
        ];
    }

    private function getLogChannel(): string
    {
        $testType = request()->header('X-Test-Type');
        $operation = request()->header('X-Operation');

        if ($testType === 'combined_100') {
            return match ($operation) {
                'cart' => 'combined_cart',
                default => 'combined',
            };
        }

        return match ($testType) {
            'cart_stress' => 'cart_stress',
            default       => 'cart_race',
        };
    }

    public function add(array $data)
    {
        if (config('app.strict_nfr_mode', false)) { return $this->optimizedAdd($data); }
        return $this->legacyAdd($data);
    }

    private function legacyAdd(array $data)
    {
        $channel = $this->getLogChannel();
        $userId    = auth()->id();
        $productId = $data['product_id'];
        $qty       = (int) $data['quantity'];

        Log::channel($channel)->info("USER {$userId} START vulnerable checkout");

        $product = Product::find($productId);

        if (!$product) {
            return ['error' => 'Product not found', 'status' => 404];
        }

        Log::channel($channel)->info("USER {$userId} READ stock={$product->quantity}");

        if (request()->header('X-Test-Type') !== 'combined_100') {
            sleep(1);
            Log::channel($channel)->info("USER {$userId} AFTER SLEEP");
        }

        if ($product->quantity < $qty) {
            Log::channel($channel)->info("USER {$userId} FAILED stock={$product->quantity}");
            return ['error' => 'Not enough stock', 'status' => 422];
        }

        Log::channel($channel)->info( "USER {$userId} STOCK VALIDATED" );

        $cartItem = Cart::firstOrNew([
            'user_id'    => $userId,
            'product_id' => $productId,
        ]);

        $cartItem->quantity += $qty;
        $cartItem->save();

        return $cartItem;
    }

    public function optimizedAdd(array $data)
    {
        $channel = $this->getLogChannel();
        $userId    = auth()->id();
        $productId = $data['product_id'];
        $qty       = (int) $data['quantity'];
        $maxPerProduct = 100;

        Log::channel($channel)->debug("USER {$userId} START optimized add");

        return DB::transaction(function () use ($userId, $productId, $qty, $maxPerProduct, $channel) {

            Log::channel($channel)->debug("USER {$userId} ACQUIRING DB LOCK");

            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->first();

            Log::channel($channel)->debug("USER {$userId} ACQUIRING DB LOCK");

            if (!$product) {
                Log::channel($channel)->debug("USER {$userId} FAILED product not found");
                return ['error' => 'Product not found', 'status' => 404];
            }

            Log::channel($channel)->debug("USER {$userId} ACQUIRED DB LOCK stock={$product->quantity}");

            $cartItem   = Cart::where('user_id', $userId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $currentQty = $cartItem?->quantity ?? 0;
            $newQty     = $currentQty + $qty;

            if ($newQty > $maxPerProduct) {
                Log::channel($channel)->debug("USER {$userId} FAILED max per product");
                return ['error' => "Max allowed is {$maxPerProduct}", 'status' => 422];
            }

            if ($product->quantity < $qty) {
                return ['error' => 'Not enough stock', 'status' => 422];
            }

            $product->decrement('quantity', $qty);

            Log::channel($channel)->debug("USER {$userId} WRITE new_stock=" . ($product->quantity - $qty));

            $result = Cart::updateOrCreate(
                ['user_id' => $userId, 'product_id' => $productId],
                ['quantity' => $newQty]
            );

            Log::channel($channel)->debug("USER {$userId} RELEASED DB LOCK");

            Cache::forget($this->cartCacheKey($userId, 1));
            Cache::forget("cart_count:{$userId}");
            Cache::forget("product:detail:{$productId}");

            return $result;
        });
    }
}