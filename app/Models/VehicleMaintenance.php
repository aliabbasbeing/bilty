<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleMaintenance extends Model
{
    use HasFactory;

    protected $table = 'vehicle_maintenance';

    protected $fillable = [
        'entry_date',
        'vehicle_no',
        'expense_type',
        'amount',
        'narration',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
