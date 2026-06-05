<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    public function dashboard()
    {
        return view('staff.dashboard');
    }

    public function scan()
    {
        return view('staff.scan');
    }

    public function upload()
    {
        return view('staff.upload');
    }

    public function search()
    {
        return view('staff.search');
    }

    public function handleUpload(Request $request)
    {
        // Handle file upload logic here
        return response()->json(['success' => true, 'message' => 'Files uploaded successfully']);
    }

    public function handleScan(Request $request)
    {
        // Handle scan logic here
        return response()->json(['success' => true, 'message' => 'Scan completed successfully']);
    }

    public function handleSearch(Request $request)
    {
        // Handle search logic here
        return response()->json(['success' => true, 'message' => 'Search completed successfully']);
    }
    public function validateSession(Request $request)
    {
        if (Auth::check()) {
            return response()->json(['valid' => true]);
        }
        return response()->json(['valid' => false], 401);
    }

    
}
