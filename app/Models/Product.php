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
        $tenant = Filament::getTenant();

        if ($tenant) {
            static::addGlobalScope('team', static function (Builder $query) use ($tenant) {
                $query->where('company_id', $tenant->id);
            });

            static::creating(static function ($model) use ($tenant) {
                if (! $model->company_id) {
                    $model->company_id = $tenant->id;
                }
            });
        }
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
