<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffDocumentController extends Controller
{
    // Main staff dashboard
    public function index()
    {
        return view('staff.dashboard');
    }

    // Upload documents page
    public function upload()
    {
        return view('staff.upload');
    }

    // Scan documents page
    public function scan()
    {
        return view('staff.scan');
    }

    // Search documents page
    public function search(Request $request)
    {
        return view('staff.search');
    }

    // Staff history page
    public function history()
    {
        return view('staff.history');
    }
}
