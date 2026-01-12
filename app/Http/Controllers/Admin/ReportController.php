<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Support\ReportService;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reports,
    ) {
    }

    public function index()
    {
        return view('admin.reports.index', [
            'revenueByCategory' => $this->reports->revenueByCategory(),
            'orders' => $this->reports->exportOrders(),
        ]);
    }
}
