<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'photo',
        'type',
    ];

    protected $casts = [
        'price' => 'decimal:2', // Preço com 2 casas decimais
    ];

    /**
     * The orders that belong to the product.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)->withTimestamps();
    }

    /**
     * Get the full URL of the product photo.
     */
    public function getPhotoUrlAttribute(): string|null
    {
        if ($this->photo) {
            /** @disregard P1013 Undefined method */
            return Storage::disk('public')->url($this->photo);
        }
        return null;
    }
}
