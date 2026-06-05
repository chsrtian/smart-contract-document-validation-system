<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    public function index()
    {
        $health = SystemHealthService::getHealthMetrics();
        return view('admin.system-health.index', compact('health'));
    }

    public function refresh()
    {
        $health = SystemHealthService::getHealthMetrics();
        return response()->json($health);
    }
}