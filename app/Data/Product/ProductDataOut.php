<?php

namespace App\Data\Product;

use App\Models\Product;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

#[TypeScript]
class ProductDataOut extends Data
{
    public function __construct(
        public string|Optional $id,
        #[Rule('required|string|max:255')]
        public string $name,
        #[Rule('required|numeric|min:0')]
        public float $price,
        #[Rule('required|string')]
        public string $photo,
        #[Rule('required|string|max:100')]
        public string $type,
        #[Rule('nullable|date')]
        public ?string $created_at,
        #[Rule('nullable|date')]
        public ?string $updated_at,
    ) {}

    public function getPriceAttribute($value): float
    {
        return (float) $value;
    }

    public function getPhotoUrlAttribute(): string|null
    {
        /** @disregard P1013 Undefined method */
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }

    public function getCreatedAtAttribute($value): ?string
    {
        return $this->created_at ? Carbon::parse($value)->toIso8601String() : null;
    }

    public function getUpdatedAtAttribute($value): ?string
    {
        return $this->updated_at ? Carbon::parse($value)->toIso8601String() : null;
    }

    public static function fromModel( Product $product): self
    {
        /** @disregard P1013 Undefined method */
        return self::from([
            'id' => $product->id ?? null,
            'name' => $product->name,
            'price' => (float) $product->price,
            'photo' => Storage::disk('public')->url($product->photo),
            'type' => $product->type,
            'created_at' => $product->created_at->toIso8601String(),
            'updated_at' => $product->updated_at ? $product->updated_at->toIso8601String() : null,
        ]);
    }

    public static function attributes(...$args): array
    {
        return [
            'name' => 'Name',
            'price' => 'Price',
            'photo_url' => 'Photo URL',
            'type' => 'Type',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
