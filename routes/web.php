<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BillController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/search', [DashboardController::class, 'search'])->name('dashboard.search');

// Consignments (Bilties)
Route::resource('consignments', ConsignmentController::class);

// Companies
Route::resource('companies', CompanyController::class);

// Bills
Route::resource('bills', BillController::class);
Route::post('bills/{bill}/finalize', [BillController::class, 'finalize'])->name('bills.finalize');
Route::post('bills/{bill}/payment', [BillController::class, 'updatePayment'])->name('bills.payment');
