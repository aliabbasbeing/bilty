<?php

namespace App\Http\Controllers;

use App\Models\Consignment;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Consignment::with('company');
        
        // Apply filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bilty_no', 'like', "%{$search}%")
                  ->orWhere('vehicle_no', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%");
            });
        }
        
        $consignments = $query->orderBy('date', 'desc')->paginate(15);
        $companies = Company::orderBy('name')->get();
        
        return view('consignments.index', compact('consignments', 'companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::orderBy('name')->get();
        $nextBiltyNo = Consignment::getNextBiltyNo();
        
        return view('consignments.create', compact('companies', 'nextBiltyNo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'bilty_no' => 'required|unique:consignments,bilty_no',
            'date' => 'required|date',
            'vehicle_no' => 'required|string',
            'driver_name' => 'required|string',
            'driver_number' => 'nullable|string',
            'vehicle_type' => 'nullable|string',
            'vehicle_owner' => 'required|in:own,rental',
            'sender_name' => 'nullable|string',
            'from_city' => 'nullable|string',
            'to_city' => 'nullable|string',
            'qty' => 'required|integer|min:0',
            'details' => 'nullable|string',
            'km' => 'required|integer|min:0',
            'rate' => 'required|numeric|min:0',
            'rate_type' => 'required|in:Fixed,PerKM',
            'advance' => 'required|numeric|min:0',
        ]);
        
        // Calculate amount
        if ($validated['rate_type'] === 'Fixed') {
            $validated['amount'] = $request->input('amount', 0);
        } else {
            $validated['amount'] = $validated['km'] * $validated['rate'];
        }
        
        // Calculate balance
        $validated['balance'] = max(0, $validated['amount'] - $validated['advance']);
        
        $consignment = Consignment::create($validated);
        
        return redirect()->route('consignments.show', $consignment)
            ->with('success', 'Consignment created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Consignment $consignment)
    {
        $consignment->load('company', 'payments');
        return view('consignments.show', compact('consignment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Consignment $consignment)
    {
        $companies = Company::orderBy('name')->get();
        return view('consignments.edit', compact('consignment', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Consignment $consignment)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'bilty_no' => 'required|unique:consignments,bilty_no,' . $consignment->id,
            'date' => 'required|date',
            'vehicle_no' => 'required|string',
            'driver_name' => 'required|string',
            'driver_number' => 'nullable|string',
            'vehicle_type' => 'nullable|string',
            'vehicle_owner' => 'required|in:own,rental',
            'sender_name' => 'nullable|string',
            'from_city' => 'nullable|string',
            'to_city' => 'nullable|string',
            'qty' => 'required|integer|min:0',
            'details' => 'nullable|string',
            'km' => 'required|integer|min:0',
            'rate' => 'required|numeric|min:0',
            'rate_type' => 'required|in:Fixed,PerKM',
            'advance' => 'required|numeric|min:0',
        ]);
        
        // Calculate amount
        if ($validated['rate_type'] === 'Fixed') {
            $validated['amount'] = $request->input('amount', 0);
        } else {
            $validated['amount'] = $validated['km'] * $validated['rate'];
        }
        
        // Calculate balance
        $validated['balance'] = max(0, $validated['amount'] - $validated['advance']);
        
        $consignment->update($validated);
        
        return redirect()->route('consignments.show', $consignment)
            ->with('success', 'Consignment updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Consignment $consignment)
    {
        $consignment->delete();
        
        return redirect()->route('consignments.index')
            ->with('success', 'Consignment deleted successfully!');
    }
}
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
