<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use App\Traits\ApiResponseTrait;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected DashboardService $dashboardService) {}

    public function index()
    {
        return $this->apiResponse(
            $this->dashboardService->getStats(),
            'Dashboard stats fetched successfully'
        );
    }
}