<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignment_id',
        'payment_date',
        'amount',
        'method',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the consignment that owns this payment
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }
}
