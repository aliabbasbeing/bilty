<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'bilty_no',
        'date',
        'vehicle_no',
        'driver_name',
        'driver_number',
        'vehicle_type',
        'vehicle_owner',
        'sender_name',
        'from_city',
        'to_city',
        'qty',
        'details',
        'km',
        'rate',
        'rate_type',
        'amount',
        'advance',
        'balance',
        'pdf_path',
    ];

    protected $casts = [
        'date' => 'date',
        'km' => 'integer',
        'qty' => 'integer',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'advance' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the company that owns this consignment
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all payments for this consignment
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Generate the next bilty number based on latest ID
     */
    public static function getNextBiltyNo(): string
    {
        $maxId = static::max('id') ?? 0;
        return (string)($maxId + 1);
    }

    /**
     * Calculate amount based on rate type
     */
    public function calculateAmount(): float
    {
        if ($this->rate_type === 'Fixed') {
            return (float)$this->amount;
        }
        
        return (float)($this->km * $this->rate);
    }

    /**
     * Calculate balance
     */
    public function calculateBalance(): float
    {
        return max(0, $this->amount - $this->advance);
    }

    /**
     * Update balance after payment
     */
    public function updateBalanceAfterPayment(float $paymentAmount): void
    {
        $this->advance += $paymentAmount;
        $this->balance = $this->calculateBalance();
        $this->save();
    }
}
