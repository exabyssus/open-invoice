<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Money\Money;
use PHPUnit\Event\Runtime\PHP;

/**
 * @property string title
 * @property int quantity
 * @property Money price
 * @property Money total
 * @property Money vat_total
 * @property ?int vat
 * @property string unit
 */
class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'quantity',
        'price',
        'vat',
        'unit',
    ];

    public function getPriceAttribute(): Money
    {
        return Money::EUR($this->attributes['price']);
    }

    public function getTotalAttribute(): Money
    {
        return Money::EUR(int)round($this->quantity * $this->price->getAmount()));
    }

    public function getVatTotalAttribute(): Money
    {
        return Money::EUR((int)round($this->total->getAmount() * $this->vat / 100));
    }
}
