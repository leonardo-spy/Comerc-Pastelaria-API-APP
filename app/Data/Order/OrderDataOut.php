<?php

namespace App\Data\Order;

use App\Models\Order;
use App\Data\Product\ProductDataOut;
use App\Data\Client\ClientDataOut;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Optional;
use Carbon\Carbon;

#[TypeScript]
class OrderDataOut extends Data
{
    public function __construct(
        public string|Optional $id,
        #[Rule('required|exists:clients,id')]
        public string $client_id,
        #[Rule('required|date')]
        public string $created_at,
        public ?array $client = null,
        public ?Collection $products = null,
        #[Rule('nullable|date')]
        public ?string $updated_at = null,
    ) {}

    public function getCreatedAtAttribute($value): ?string
    {
        return $this->created_at ? Carbon::parse($value)->toIso8601String() : null;
    }

    public function getUpdatedAtAttribute($value): ?string
    {
        return $this->updated_at ? Carbon::parse($value)->toIso8601String() : null;
    }

    public static function fromModel(Order $order, string $context = 'order'): self
    {
        return self::from([
            'id' => $order->id ?? null,
            'client_id' => $order->client_id,
            'created_at' => $order->created_at->toIso8601String(),
            'client' => $context === 'order' ? ClientDataOut::fromModel($order->client, 'order')->toArray(): null, // avoid circular reference
            'products' => $order->products->map(fn($product) => array_merge(
                ProductDataOut::fromModel($product)->toArray(), 
                ['quantity' => (int) $product->pivot->quantity,
                'priceAtOrder' => (float) $product->pivot->price_at_purchase]
            )),
            'updated_at' => $order->updated_at ? $order->updated_at->toIso8601String() : null,
        ]);
    }

    public static function attributes(...$args): array
    {
        return [
            'id' => 'ID',
            'client_id' => 'Client',
            'created_at' => 'Created At',
            'products' => 'Products',
            'updated_at' => 'Updated At',
        ];
    }
}
