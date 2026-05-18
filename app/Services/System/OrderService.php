<?php
 
namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
 
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;

use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendCancellationNotificationJob;

 
class OrderService
{
    public function getUserOrders()
    {
        $page = request('page', 1);
 
        return Cache::remember(
            "user_orders_" . auth()->id() . "_page_" . $page,
            60,
            function () {
                return Order::with('items.product')
                    ->where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
            }
        );
    }
    
    /*
    *___________________________________________________
        Test NFR 1 (Before Handling) -- Locking 
    *___________________________________________________
    */

    // public function placeOrder()
    // {
    //     $userId = auth()->id();
    
    //     $cartItems = Cart::where('user_id', $userId)
    //         ->with('product')
    //         ->get();
    
    //     if ($cartItems->isEmpty()) {
    //         return ['error' => 'Cart is empty', 'status' => 422];
    //     }
    
    //     $productIds = $cartItems->pluck('product_id')->unique()->values();
    
    //     $products = Product::whereIn('id', $productIds)
    //         ->get()
    //         ->keyBy('id');
    
    //     sleep(1);
    
    //     $preparedItems = [];
    //     $totalPrice    = 0;
    
    //     foreach ($cartItems as $cartItem) {
    
    //         $product = $products->get($cartItem->product_id);
    
    //         if (!$product) {
    //             return ['error' => 'Product not found', 'status' => 404];
    //         }
    
    //         if ($product->quantity < $cartItem->quantity) {
    //             return ['error' => "Not enough stock for: {$product->name}", 'status' => 422];
    //         }
    
    //         $preparedItems[] = [
    //             'product'    => $product,
    //             'product_id' => $product->id,
    //             'quantity'   => $cartItem->quantity,
    //             'price'      => (float) $product->price,
    //         ];
    
    //         $totalPrice += (float) $product->price * $cartItem->quantity;
    //     }
    
    //     $order = Order::create([
    //         'user_id'     => $userId,
    //         'total_price' => $totalPrice,
    //         'status'      => 'pending',
    //     ]);
    
    //     foreach ($preparedItems as $item) {
    
    //         OrderItem::create([
    //             'order_id'   => $order->id,
    //             'product_id' => $item['product_id'],
    //             'quantity'   => $item['quantity'],
    //             'price'      => $item['price'],
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);
    //         $newQuantity = $item['product']->quantity - $item['quantity'];
    //         Product::where('id', $item['product_id'])
    //             ->update(['quantity' => $newQuantity]);
    //     }
    
    //     Cart::where('user_id', $userId)->delete();
    
    //     return $order->load('items.product');
    // }
 

    /*
    *___________________________________________________
        Test NFR 3 (Before Handling) -- Queue
    *___________________________________________________
    */

    // public function placeOrder()
    // {
    //     $userId = auth()->id();
 
    //     /**
    //      * Capacity Control NFR(2)
    //      */
    //     $maxCartItems = 50;
 
    //     $cartItemsCount = Cache::remember("cart_count:{$userId}", 300, function () use ($userId) {
    //         return Cart::where('user_id', $userId)->count();
    //     });
 
    //     if ($cartItemsCount > $maxCartItems) {
    //         return $this->error(
    //             "Cart cannot exceed {$maxCartItems} different products",
    //             422
    //         );
    //     }
 
    //     /**
    //      * Distributed Lock (Redis) NFR(7)
    //      */
    //     $lock = Cache::lock("place_order:{$userId}", 10);
 
    //     if (!$lock->get()) {
    //         return $this->error('Another order is being processed, please wait', 429);
    //     }
 
    //     try {
 
    //         /**
    //          * ACID Transaction NFR(8)
    //          */
    //         $order = DB::transaction(function () use ($userId) {

    //             # for test NFR 3 Queue
    //             $cartItems = Cart::where('user_id', $userId)->with('product')->get();
 
    //             if ($cartItems->isEmpty()) {
    //                 return $this->error('Cart is empty', 422);
    //             }
 
    //             $productIds = $cartItems->pluck('product_id')->unique()->values();
 
    //             /**
    //              * Pessimistic Locking NFR(7) — Critical Section
    //              */
    //             $products = Product::whereIn('id', $productIds)
    //                 ->orderBy('id')     
    //                 ->lockForUpdate() 
    //                 ->get()
    //                 ->keyBy('id');
 
    //             $preparedItems = [];
    //             $totalPrice    = 0;
 
    //             foreach ($cartItems as $cartItem) {
 
    //                 $product = $products->get($cartItem->product_id);
 
    //                 if (!$product) {
    //                     return $this->error('Product not found', 404);
    //                 }
 
    //                 // Race Condition Check
    //                 if ($product->quantity < $cartItem->quantity) {
    //                     return $this->error(
    //                         "Not enough stock for product: {$product->name}",
    //                         422
    //                     );
    //                 }
 
    //                 $price = (float) $product->price;
 
    //                 $preparedItems[] = [
    //                     'product'    => $product,
    //                     'product_id' => $product->id,
    //                     'quantity'   => $cartItem->quantity,
    //                     'price'      => $price,
    //                 ];
 
    //                 $totalPrice += $price * $cartItem->quantity;
    //             }
    //             $order = Order::create([
    //                 'user_id'     => $userId,
    //                 'total_price' => $totalPrice,
    //                 'status'      => 'pending',
    //             ]);
 
    //             /**
    //              * Batch Insert + Stock Update NFR(4)
    //              */
    //             $orderItems = [];
 
    //             foreach ($preparedItems as $item) {
 
    //                 $orderItems[] = [
    //                     'order_id'   => $order->id,
    //                     'product_id' => $item['product_id'],
    //                     'quantity'   => $item['quantity'],
    //                     'price'      => $item['price'],
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];
 
    //                 $item['product']->decrement('quantity', $item['quantity']);
    //             }
 
    //             OrderItem::insert($orderItems); // Batch Insert
    //             Cart::where('user_id', $userId)->delete();
 
    //             /**
    //              * Cache Invalidation NFR(6)
    //              */
    //             Cache::forget("cart:{$userId}");
    //             Cache::forget("cart_count:{$userId}");
    //             Cache::forget("user_orders:{$userId}");
 
    //             return $order->load('items.product');
    //         });
 
    //         /**
    //          * Async Queue NFR(3)
    //          */
    //         if (!isset($order['error'])) {
    //             sleep(3);
    //         }
 
    //         return $order;
 
    //     } finally {
    //         $lock->release();
    //     }
    // }
 
    /*
    *___________________________________________________
        Test NFR 4 (Before Handling) -- Batch 
    *___________________________________________________
    */

    // public function placeOrder()
    // {
    //     $userId = auth()->id();
 
    //     /**
    //      * Capacity Control NFR(2)
    //      */
    //     $maxCartItems = 50;
 
    //     $cartItemsCount = Cache::remember("cart_count:{$userId}", 300, function () use ($userId) {
    //         return Cart::where('user_id', $userId)->count();
    //     });
 
    //     if ($cartItemsCount > $maxCartItems) {
    //         return $this->error(
    //             "Cart cannot exceed {$maxCartItems} different products",
    //             422
    //         );
    //     }
 
    //     /**
    //      * Distributed Lock (Redis) NFR(7)
    //      */
    //     $lock = Cache::lock("place_order:{$userId}", 10);
 
    //     if (!$lock->get()) {
    //         return $this->error('Another order is being processed, please wait', 429);
    //     }
 
    //     try {
 
    //         /**
    //          * ACID Transaction NFR(8)
    //          */
    //         $order = DB::transaction(function () use ($userId) {
 
    //             $cartItems = Cart::where('user_id', $userId)->with('product')->get();

    //             # for test NFR 3 Queue
    //             // $cartItems = Cart::where('user_id', $userId)->with('product')->get();
 
    //             if ($cartItems->isEmpty()) {
    //                 return $this->error('Cart is empty', 422);
    //             }
 
    //             $productIds = $cartItems->pluck('product_id')->unique()->values();
 
    //             /**
    //              * Pessimistic Locking NFR(7) — Critical Section
    //              */
    //             $products = Product::whereIn('id', $productIds)
    //                 ->orderBy('id')     
    //                 ->lockForUpdate() 
    //                 ->get()
    //                 ->keyBy('id');
 
    //             $preparedItems = [];
    //             $totalPrice    = 0;
 
    //             foreach ($cartItems as $cartItem) {
 
    //                 $product = $products->get($cartItem->product_id);
 
    //                 if (!$product) {
    //                     return $this->error('Product not found', 404);
    //                 }
 
    //                 // Race Condition Check
    //                 if ($product->quantity < $cartItem->quantity) {
    //                     return $this->error(
    //                         "Not enough stock for product: {$product->name}",
    //                         422
    //                     );
    //                 }
 
    //                 $price = (float) $product->price;
 
    //                 $preparedItems[] = [
    //                     'product'    => $product,
    //                     'product_id' => $product->id,
    //                     'quantity'   => $cartItem->quantity,
    //                     'price'      => $price,
    //                 ];
 
    //                 $totalPrice += $price * $cartItem->quantity;
    //             }
    //             $order = Order::create([
    //                 'user_id'     => $userId,
    //                 'total_price' => $totalPrice,
    //                 'status'      => 'pending',
    //             ]);
 
    //             foreach ($preparedItems as $item) {
    //                 OrderItem::create([
    //                     'order_id'   => $order->id,
    //                     'product_id' => $item['product_id'],
    //                     'quantity'   => $item['quantity'],
    //                     'price'      => $item['price'],
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ]);
    //             }
               
    //             Cart::where('user_id', $userId)->delete();
 
    //             /**
    //              * Cache Invalidation NFR(6)
    //              */
    //             Cache::forget("cart:{$userId}");
    //             Cache::forget("cart_count:{$userId}");
    //             Cache::forget("user_orders:{$userId}");
 
    //             return $order->load('items.product');
    //         });
 
    //         /**
    //          * Async Queue NFR(3)
    //          */
    //         if (!isset($order['error'])) {
    //             SendOrderConfirmationJob::dispatch($order);
    //             GenerateInvoiceJob::dispatch($order);
    //             // sleep(3);
    //         }
 
    //         return $order;
 
    //     } finally {
    //         $lock->release();
    //     }
    // }

    /*
    *___________________________________________________
        Improved Code
    *___________________________________________________
    */
    public function placeOrder()
    {
        $userId = auth()->id();
 
        /**
         * Capacity Control NFR(2)
         */
        $maxCartItems = 50;
 
        $cartItemsCount = Cache::remember("cart_count:{$userId}", 300, function () use ($userId) {
            return Cart::where('user_id', $userId)->count();
        });
 
        if ($cartItemsCount > $maxCartItems) {
            return $this->error(
                "Cart cannot exceed {$maxCartItems} different products",
                422
            );
        }
 
        /**
         * Distributed Lock (Redis) NFR(7)
         */
        $lock = Cache::lock("place_order:{$userId}", 10);
 
        if (!$lock->get()) {
            return $this->error('Another order is being processed, please wait', 429);
        }
 
        try {
 
            /**
             * ACID Transaction NFR(8)
             */
            $order = DB::transaction(function () use ($userId) {
 
                /**
                 * Cart (Cached Read) NFR(6)
                 */
                $cartItems = Cache::remember("cart:{$userId}", 120, function () use ($userId) {
                    return Cart::where('user_id', $userId)
                        ->with('product')
                        ->get();
                });

                # for test NFR 3 Queue
                // $cartItems = Cart::where('user_id', $userId)->with('product')->get();
 
                if ($cartItems->isEmpty()) {
                    return $this->error('Cart is empty', 422);
                }
 
                $productIds = $cartItems->pluck('product_id')->unique()->values();
 
                /**
                 * Pessimistic Locking NFR(7) — Critical Section
                 */
                $products = Product::whereIn('id', $productIds)
                    ->orderBy('id')     
                    ->lockForUpdate() 
                    ->get()
                    ->keyBy('id');
 
                $preparedItems = [];
                $totalPrice    = 0;
 
                foreach ($cartItems as $cartItem) {
 
                    $product = $products->get($cartItem->product_id);
 
                    if (!$product) {
                        return $this->error('Product not found', 404);
                    }
 
                    // Race Condition Check
                    if ($product->quantity < $cartItem->quantity) {
                        return $this->error(
                            "Not enough stock for product: {$product->name}",
                            422
                        );
                    }
 
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
 
                /**
                 * Batch Insert + Stock Update NFR(4)
                 */
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
                }
 
                OrderItem::insert($orderItems); // Batch Insert
                Cart::where('user_id', $userId)->delete();
 
                /**
                 * Cache Invalidation NFR(6)
                 */
                Cache::forget("cart:{$userId}");
                Cache::forget("cart_count:{$userId}");
                Cache::forget("user_orders:{$userId}");
 
                return $order->load('items.product');
            });
 
            /**
             * Async Queue NFR(3)
             */
            if (!isset($order['error'])) {
                SendOrderConfirmationJob::dispatch($order);
                GenerateInvoiceJob::dispatch($order);
                // sleep(3);
            }
 
            return $order;
 
        } finally {
            $lock->release();
        }
    }
 
    public function cancelOrder($id)
    {
        $order = DB::transaction(function () use ($id) {
 
            /**
             * Pessimistic Locking NFR(7) 
             */
            $order = Order::where('id', $id)
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->with('items')
                ->first();
 
            if (!$order) {
                return $this->error('Order not found', 404);
            }
 
            if ($order->status !== 'pending') {
                return $this->error('This order cannot be cancelled');
            }
 
            foreach ($order->items as $item) {
 
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
 
                if ($product) {
                    $product->increment('quantity', $item->quantity);
 
                    # Cache Invalidation NFR(6)
                    Cache::forget("product:{$product->id}");
                }
            }
 
            $order->update(['status' => 'cancelled']);
 
            # Cache Invalidation NFR(6)
            Cache::forget("order:{$order->id}");
 
            return $order->fresh()->load('items.product');
        });
 
        /**
         * Async Notification NFR(3)
         */
        if (!isset($order['error'])) {
            SendCancellationNotificationJob::dispatch($order);
        }
 
        return $order;
    }
 
    public function show($orderId)
    {
        $cacheKey = 'order_' . auth()->id() . '_' . $orderId;
 
        # Distributed Caching NFR(6)
        $order = Cache::remember($cacheKey, 300, function () use ($orderId) {
            return Order::with('items.product')
                ->where('id', $orderId)
                ->where('user_id', auth()->id())
                ->first();
        });
 
        if (!$order) {
            return $this->error('Order not found', 404);
        }
 
        return $order;
    }
 
    public function updateProductQuantity($orderId, $productId, $quantity)
    {
        # Capacity Control NFR(2)
        if (!is_numeric($quantity) || $quantity < 0) {
            return $this->error('Invalid quantity value', 422);
        }
 
        return DB::transaction(function () use ($orderId, $productId, $quantity) {
 
            /**
             * Pessimistic Locking NFR(7)
             */
            $order = Order::where('id', $orderId)
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();
 
            if (!$order) {
                return $this->error('Order not found', 404);
            }
 
            if ($order->status !== 'pending') {
                return $this->error('This order cannot be edited');
            }
 
            $orderItem = $order->items()
                ->where('product_id', $productId)
                ->first();
 
            if (!$orderItem) {
                return $this->error('Product not found in this order', 404);
            }
 
            # Pessimistic Locking NFR(7)
            $product = Product::lockForUpdate()->find($productId);
 
            if (!$product) {
                return $this->error('Product not found', 404);
            }
 
            if ($quantity === 0) {
 
                $product->increment('quantity', $orderItem->quantity);
                $orderItem->delete();
 
                $this->recalculateOrderTotal($order);
 
                # Cache Invalidation NFR(6)
                Cache::forget("product_{$productId}");
                Cache::forget("order_{$orderId}");
 
                return $order->fresh()->load('items.product');
            }
 
            $diff = $quantity - $orderItem->quantity;
 
            if ($diff > 0) {
                // Race Condition Check
                if ($product->quantity < $diff) {
                    return $this->error('Not enough stock', 422);
                }
                $product->decrement('quantity', $diff);
            } elseif ($diff < 0) {
                $product->increment('quantity', abs($diff));
            }
 
            $orderItem->update(['quantity' => $quantity]);
 
            $this->recalculateOrderTotal($order);
 
            # Cache Invalidation NFR(6)
            Cache::forget("product_{$productId}");
            Cache::forget("order_{$orderId}");
 
            return $order->fresh()->load('items.product');
        });
    }

    private function recalculateOrderTotal(Order $order): void
    {
        $items = $order->items()->get();
 
        $totalPrice = $items->sum(fn($item) => $item->quantity * $item->price);
 
        $order->update(['total_price' => $totalPrice]);
    }
 
    private function error(string $message, int $status = 400): array
    {
        return [
            'error'  => $message,
            'status' => $status,
        ];
    }
}