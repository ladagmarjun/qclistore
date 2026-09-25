<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Store stats, the latest orders, and products running low.
     */
    public function __invoke(DashboardService $dashboard): JsonResponse
    {
        return response()->json($dashboard->summary());
    }
}
