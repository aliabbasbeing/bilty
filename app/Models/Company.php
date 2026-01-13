<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
    ];

    /**
     * Get all consignments for this company
     */
    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }

    /**
     * Get all bills for this company
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
