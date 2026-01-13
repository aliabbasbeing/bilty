<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Consignment;
use App\Models\VehicleMaintenance;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create sample companies
        $companies = [
            ['name' => 'ABC Transport Ltd', 'address' => '123 Main Street, Lahore'],
            ['name' => 'XYZ Logistics', 'address' => '456 Park Road, Karachi'],
            ['name' => 'Fast Cargo Services', 'address' => '789 Highway Avenue, Islamabad'],
        ];

        foreach ($companies as $companyData) {
            $company = Company::create($companyData);
            
            // Create 2-3 consignments for each company
            for ($i = 1; $i <= rand(2, 3); $i++) {
                Consignment::create([
                    'company_id' => $company->id,
                    'bilty_no' => Consignment::getNextBiltyNo(),
                    'date' => now()->subDays(rand(1, 30)),
                    'vehicle_no' => 'ABC-' . rand(1000, 9999),
                    'driver_name' => ['Ahmed Ali', 'Muhammad Hassan', 'Ali Raza', 'Usman Khan'][rand(0, 3)],
                    'driver_number' => '+92 3' . rand(00, 99) . ' ' . rand(1000000, 9999999),
                    'vehicle_type' => ['Truck', 'Container', 'Trailer'][rand(0, 2)],
                    'vehicle_owner' => rand(0, 1) ? 'own' : 'rental',
                    'sender_name' => 'Sender ' . rand(1, 10),
                    'from_city' => ['Lahore', 'Karachi', 'Islamabad'][rand(0, 2)],
                    'to_city' => ['Multan', 'Faisalabad', 'Rawalpindi'][rand(0, 2)],
                    'qty' => rand(10, 100),
                    'details' => 'Sample consignment details',
                    'km' => rand(100, 500),
                    'rate' => rand(50, 150),
                    'rate_type' => rand(0, 1) ? 'PerKM' : 'Fixed',
                    'amount' => rand(10000, 50000),
                    'advance' => rand(5000, 20000),
                    'balance' => 0, // Will be calculated
                ]);
            }
        }

        // Update balances for all consignments
        Consignment::all()->each(function ($consignment) {
            $consignment->balance = max(0, $consignment->amount - $consignment->advance);
            $consignment->save();
        });

        // Create sample vehicle maintenance records
        $vehicleNos = Consignment::pluck('vehicle_no')->unique()->take(5);
        foreach ($vehicleNos as $vehicleNo) {
            VehicleMaintenance::create([
                'entry_date' => now()->subDays(rand(1, 60)),
                'vehicle_no' => $vehicleNo,
                'expense_type' => ['Oil Change', 'Tire Replacement', 'Engine Repair', 'General Service'][rand(0, 3)],
                'amount' => rand(5000, 25000),
                'narration' => 'Regular maintenance service',
            ]);
        }
    }
}

