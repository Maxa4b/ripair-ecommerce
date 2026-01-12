<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Support\ReportService;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reports,
    ) {
    }

    public function index()
    {
        return view('admin.dashboard', [
            'metrics' => $this->reports->dashboardMetrics(),
            'revenueByCategory' => $this->reports->revenueByCategory(),
        ]);
    }
}
