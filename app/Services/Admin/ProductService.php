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
    private function productListCacheKey(int $page): string
    {
        return "products:page:{$page}";
    }
 
    private function productDetailCacheKey(int $id): string
    {
        return "product:detail:{$id}";
    }
 
    public function createProduct(array $data, array $images = []): Product
    {
        if (count($images) > 5) {
            throw new \Exception('Maximum 5 images allowed per product');
        }
 
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
                if (!in_array($img->getMimeType(), ['image/jpeg', 'image/png'])) {
                    continue;
                }
 
                $fileName      = Str::uuid() . '.' . strtolower($img->getClientOriginalExtension());
                $path          = $img->storeAs('uploads/products', $fileName, 'private');
                $storedPaths[] = $path;
 
                $product->image()->create(['image_path' => $path]);
            }
 
            DB::commit();

            foreach ($storedPaths as $path) {
                ProcessProductImageJob::dispatch($path);
            }

            $this->invalidateProductListCache();
 
            return $product->load('image', 'store');
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating product: ' . $e->getMessage());
            throw $e;
        }
    }
 
    public function deleteProduct(int $id): bool
    {
        $product = Product::withCount(['orders' => function ($query) {
            $query->whereIn('status', ['pending', 'processing']);
        }])->findOrFail($id);
 
        if ($product->orders_count > 0) {
            throw new \Exception('Cannot delete product with active pending orders.');
        }

        DB::beginTransaction();
 
        try {
            $imagePaths = $product->image->pluck('image_path')->toArray();
 
            $product->delete();
 
            DB::commit();

            if (!empty($imagePaths)) {
                DeleteProductImagesJob::dispatch($imagePaths);
            }
 
            Cache::forget($this->productDetailCacheKey($id));
            $this->invalidateProductListCache();
 
            return true;
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: ' . $e->getMessage());
            throw $e;
        }
    }

    private function invalidateProductListCache(): void
    {
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget($this->productListCacheKey($page));
        }
    }
}