<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ProductService $service) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->apiResponse(
            $this->service->index($perPage),
            'Products retrieved successfully',
            200
        );
    }

    public function show($id)
    {
        $result = $this->service->show($id);

        if (is_array($result) && isset($result['error'])) {
            return response()->json([
                'message' => $result['error']
            ], $result['status']);
        }

        return $this->apiResponse($result, 'Product fetched successfully');
    }
}