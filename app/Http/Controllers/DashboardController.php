<?php

namespace App\Http\Controllers;

use App\Models\Consignment;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // KPI calculations
        $totalConsignments = Consignment::count();
        $totalRevenue = Consignment::sum('amount');
        $pendingBalance = Consignment::where('balance', '>', 0)->sum('balance');
        $monthlyConsignments = Consignment::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        
        return view('dashboard', compact(
            'totalConsignments',
            'totalRevenue',
            'pendingBalance',
            'monthlyConsignments'
        ));
    }
    
    public function search(Request $request)
    {
        $biltyNo = $request->input('bilty_no');
        $consignment = null;
        
        if ($biltyNo) {
            $consignment = Consignment::with('company')
                ->where('bilty_no', $biltyNo)
                ->first();
        }
        
        return view('dashboard', compact('consignment', 'biltyNo'));
    }
}
