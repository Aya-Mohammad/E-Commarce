<?php
 
namespace App\Services\Admin;
 
use App\Jobs\DeleteProductImagesJob;
use App\Jobs\ProcessProductImageJob;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
 
class ProductService
{
    /**
     * Cache Key Strategy (6)
     */
    private function productListCacheKey(int $page): string
    {
        return "products:page:{$page}";
    }
 
    private function productDetailCacheKey(int $id): string
    {
        return "product:detail:{$id}";
    }
 
    public function getAllProducts(int $perPage = 15)
    {
        $page = (int) request()->get('page', 1);
 
        /**
         * Distributed Caching (6)
         * Resource Management (2)
         */
        return Cache::remember($this->productListCacheKey($page), now()->addHour(), function () use ($perPage) {
            return Product::with('image', 'store')->paginate($perPage);
        });
    }
 
    public function createProduct(array $data, array $images = []): Product
    {
        // Capacity Control (2) 
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per product');
        }
 
        /**
         * ACID Transaction (8)
         */
        DB::beginTransaction();
 
        try {
            $storedPaths = [];
 
            $product = Product::create([
                'name'        => $data['name'],
                'description' => $data['description'],
                'price'       => $data['price'],
                'quantity'    => $data['quantity'],
                'store_id'    => $data['store_id'],
            ]);
 
            foreach ($images as $img) {
                // Data Integrity (1)
                if (!in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }
 
                $fileName      = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());
                $path          = $img->storeAs('uploads/products', $fileName, 'private');
                $storedPaths[] = $path;
 
                $product->image()->create(['image_path' => $path]);
            }
 
            DB::commit();
 
            /**
             * Async Queue (3)
             */
            foreach ($storedPaths as $path) {
                ProcessProductImageJob::dispatch($path);
            }
 
            /**
             * Cache Invalidation (6)
             */
            $this->invalidateProductListCache();
 
            return $product->load('image', 'store');
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating product: ' . $e->getMessage());
            throw $e;
        }
    }
 
    public function updateProduct(int $id, array $data, array $images = []): Product
    {
        // Data Integrity (1) 
        $product = Product::findOrFail($id);
 
        // Capacity Control (2)
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per product');
        }
 
        /**
         * ACID Transaction (8)
         */
        DB::beginTransaction();
 
        try {
            $storedPaths = [];
 
            /**
             * array_filter + array_intersect_key:
             */
            $product->update(array_filter(
                array_intersect_key($data, array_flip([
                    'name', 'description', 'price', 'quantity', 'store_id',
                ])),
                fn($value) => !is_null($value)
            ));
 
            foreach ($images as $img) {
                // Data Integrity (1) 
                if (!in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }
 
                $fileName      = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());
                $path          = $img->storeAs('uploads/products', $fileName, 'private');
                $storedPaths[] = $path;
 
                $product->image()->create(['image_path' => $path]);
            }
 
            DB::commit();
 
            // Async Queue (3)
            foreach ($storedPaths as $path) {
                ProcessProductImageJob::dispatch($path);
            }
 
            /**
             * Cache Invalidation (6)
             */
            Cache::forget($this->productDetailCacheKey($id));
            $this->invalidateProductListCache();
 
            return $product->fresh('image', 'store');
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating product: ' . $e->getMessage());
            throw $e;
        }
    }
 
    public function getProductById(int $id): ?Product
    {
        // Capacity Control (2) 
        if (!is_numeric($id) || (int) $id <= 0) {
            return null;
        }
 
        $id = (int) $id;
 
        /**
         * Distributed Caching (6)
         */
        return Cache::remember($this->productDetailCacheKey($id), now()->addHours(24), function () use ($id) {
            return Product::with('image', 'store')->findOrFail($id);
        });
    }
 
    public function deleteProduct(int $id): bool
    {
        /**
         * Data Integrity (1)
         */
        $product = Product::withCount(['orders' => function ($query) {
            $query->whereIn('status', ['pending', 'processing']);
        }])->findOrFail($id);
 
        if ($product->orders_count > 0) {
            throw new \Exception('Cannot delete product with active pending orders.');
        }
 
        /**
         * ACID Transaction (8)
         */
        DB::beginTransaction();
 
        try {
            $imagePaths = $product->image->pluck('image_path')->toArray();
 
            $product->delete();
 
            DB::commit();
 
            /**
             * Async Queue (3)
             */
            if (!empty($imagePaths)) {
                DeleteProductImagesJob::dispatch($imagePaths);
            }
 
            // Cache Invalidation (6)
            Cache::forget($this->productDetailCacheKey($id));
            $this->invalidateProductListCache();
 
            return true;
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: ' . $e->getMessage());
            throw $e;
        }
    }
 
    /**
     * Cache Invalidation (6)
     */
    private function invalidateProductListCache(): void
    {
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget($this->productListCacheKey($page));
        }
    }
}