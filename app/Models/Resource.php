<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'location', 'total_quantity', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_quantity' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
