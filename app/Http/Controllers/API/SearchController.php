<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; 

class SearchController extends Controller
{
    use ApiResponseTrait;

    private function getLogChannel(): string
    {
        $testType = request()->header('X-Test-Type');
        $operation = request()->header('X-Operation');

        if ($testType === 'combined_100') {
            return match ($operation) {
                'search' => 'combined_search',
                default  => 'combined',
            };
        }
        return 'search';
    }

    public function search(Request $request)
    {
        $channel = $this->getLogChannel(); 
        $isOptimized = config('app.strict_nfr_mode', true);

        $query = $request->input('search', 'empty_search');
        Log::channel($channel)->info("User performed a search. Query: [{$query}] - Mode: " . ($isOptimized ? 'Optimized' : 'Vulnerable'));

        if ($isOptimized) { return $this->searchOptimized($request); }
        return $this->searchVulnerable($request);
    }

    private function searchVulnerable(Request $request)
    {
        $channel = $this->getLogChannel();
        $request->validate(['search' => 'required|string|max:100']);

        $query = $request->input('search');
        //NFR #10 - Benchmarking
        Log::channel($channel)->info("SEARCH VULNERABLE | query={$query} | LIKE %...% (no cache, no index)");

        $stores = Store::with('image')->where('name', 'like', "%{$query}%")->get();
        $products = Product::with('image')->where('name', 'like', "%{$query}%")->get();

        return $this->apiResponse([
            'stores' => $stores, 'products' => $products,
        ], 'Search results fetched successfully (Vulnerable Mode)');
    }

    private function searchOptimized(Request $request)
    {
        $channel = $this->getLogChannel();
        //NFR #2 - Resource Management & Capacity Control
        $request->validate([
            'search'   => 'required|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query   = $request->input('search');
        $perPage = $request->get('per_page', 15);
        //NFR #6 - Distributed Caching (Cache Key Design)
        $cacheKey = "search_fulltext:" . md5($query . $perPage);

        //NFR #10 - Benchmarking
        if (Cache::has($cacheKey)) {
            Log::channel($channel)->info("SEARCH CACHE HIT | query={$query}");
        } else {
            Log::channel($channel)->info("SEARCH CACHE MISS | query={$query} | FullText lookup");
        }

        //NFR #6 - Distributed Caching
        $results = Cache::remember($cacheKey, now()->addMinute(), function () use ($query, $perPage) {
            //NFR #2 - Resource Management
            //NFR #10 - Bottleneck Fix (Full-Text vs LIKE)
            $stores = Store::with('image')
                ->whereFullText('name', $query, ['mode' => 'boolean'])
                ->select(['id', 'name', 'delivery_cost', 'distance'])
                ->limit($perPage)->get();

            $products = Product::with('image')
                ->whereFullText('name', $query, ['mode' => 'boolean'])
                ->select(['id', 'name', 'price', 'quantity', 'store_id'])
                ->limit($perPage)->get();

            return ['stores' => $stores->toArray(), 'products' => $products->toArray()];
        });

        return $this->apiResponse($results, 'Search results fetched successfully (Optimized Mode)');
    }
}
