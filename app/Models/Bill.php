<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_no',
        'financial_year',
        'issue_date',
        'company_id',
        'consignment_ids',
        'gross_amount',
        'tax_percent',
        'tax_amount',
        'net_amount',
        'meta',
        'status',
        'payment_status',
        'payment_date',
        'payment_note',
        'pdf_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'payment_date' => 'date',
        'consignment_ids' => 'array',
        'meta' => 'array',
        'gross_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    /**
     * Get the company that owns this bill
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get consignments associated with this bill
     */
    public function getConsignments()
    {
        if (!$this->consignment_ids) {
            return collect();
        }
        
        return Consignment::whereIn('id', $this->consignment_ids)->get();
    }

    /**
     * Calculate tax amount based on gross amount and tax percent
     */
    public function calculateTaxAmount(): float
    {
        return round(($this->gross_amount * $this->tax_percent) / 100, 2);
    }

    /**
     * Calculate net amount (gross + tax)
     */
    public function calculateNetAmount(): float
    {
        return round($this->gross_amount + $this->tax_amount, 2);
    }

    /**
     * Finalize the bill
     */
    public function finalize(): void
    {
        $this->tax_amount = $this->calculateTaxAmount();
        $this->net_amount = $this->calculateNetAmount();
        $this->status = 'FINAL';
        $this->save();
    }

    /**
     * Mark bill as paid
     */
    public function markAsPaid(string $date, ?string $note = null): void
    {
        $this->payment_status = 'PAID';
        $this->payment_date = $date;
        $this->payment_note = $note;
        $this->save();
    }
}
