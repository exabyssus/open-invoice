<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'product_number',
        'price',
        'default_quantity',
        'unit',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('team', static function (Builder $query) {
            ($tenant = Filament::getTenant()) ? $query->where('company_id', $tenant->id) : $query->whereRaw('0 = 1');
        });

        static::creating(static function ($model) {
            if (! $model->company_id && Filament::getTenant()) {
                $model->company_id = Filament::getTenant()->id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getDisplayName(): string
    {
        if ($this->product_number) {
            return "{$this->name} ({$this->product_number})";
        }
        return $this->name;
    }
}
