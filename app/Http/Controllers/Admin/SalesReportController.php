<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SalesReportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected SalesReportService $service) {}

    public function index(Request $request)
    {
        $days  = min((int) $request->get('days', 30), 90);
        $reports = $this->service->getReports($days);

        return $this->apiResponse($reports, 'Sales reports fetched successfully');
    }

    public function show(string $date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->apiResponse(null, 'Invalid date format. Use YYYY-MM-DD', 422);
        }
        $report = $this->service->getReportByDate($date);

        if (!$report) {
            return $this->apiResponse(null, 'No report found for this date', 404);
        }
        return $this->apiResponse($report, 'Report fetched successfully');
    }

    public function trigger(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $this->service->triggerReport($request->date);

        return $this->apiResponse(
            ['date' => $request->date, 'queued' => true],
            'Report generation queued successfully'
        );
    }
}
