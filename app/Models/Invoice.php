<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Money\Money;

/**
 * @property string invoice_number
 * @property string client_id
 * @property Company company
 * @property Client client
 * @property Carbon date
 * @property ?Carbon due_date
 * @property ?int dept_amount
 * @property bool is_advance
 * @property bool is_paid
 * @property string comment
 * @property string language
 * @property User created_by
 * @property Collection lines
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        // auto-sets values on creation
        static::creating(static function ($query) {
            $query->template = 'default';
            $query->created_by = auth()->user()->id;
        });
    }

    protected static function booted(): void
    {
        $tenant = Filament::getTenant();

        static::addGlobalScope('team', static function (Builder $query) use ($tenant) {
            $query->whereBelongsTo($tenant);
        });
    }

    protected $casts = [
        'date' => 'immutable_datetime',
        'due_date' => 'immutable_datetime',
        'is_advance' => 'boolean',
        'is_paid' => 'boolean'
    ];

    protected $fillable = [
        'invoice_number',
        'client_id',
        'date',
        'due_date',
        'is_advance',
        'is_paid',
        'dept_amount',
        'comment',
        'currency',
        'language',
    ];

    public function createdByUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function getTotal(): Money
    {
        $amount = $this
            ->lines
            ->sum(fn(InvoiceLine $line) => $line->total->getAmount());

        return Money::EUR($amount);
    }

    public function getTotalWithVatAndTax(): Money
    {
        return $this->getTotal()
            ->add($this->getTotalVAT())
            ->add($this->getTotalDept());
    }

    public function getTotalDept(): Money
    {
        return Money::EUR($this->dept_amount ?? 0);
    }

    // TODO: Display in Invoice for each PVN group
    public function getVATLines(): Collection
    {
        return $this
            ->lines
            ->groupBy(fn(InvoiceLine $line) => $line->vat)
            ->map(fn(\Illuminate\Support\Collection $group) => $group->sum(fn(InvoiceLine $line) => (float)$line->vat_total->getAmount()))
            ->map(fn(float $sum) => Money::EUR((int)$sum));
    }

    public function getTotalVAT(): Money
    {
        $amount = $this
            ->lines
            ->sum(fn(InvoiceLine $line) => $line->vat_total->getAmount());

        return Money::EUR($amount);
    }
}
