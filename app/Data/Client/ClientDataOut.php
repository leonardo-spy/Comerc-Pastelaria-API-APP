<?php

namespace App\Data\Client;

use App\Models\Client;
use App\Data\Order\OrderDataOut;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Carbon\Carbon;

#[TypeScript]
class ClientDataOut extends Data
{
    public function __construct(
        public string|Optional $id,
        #[Rule('required|string|max:255')]
        public string $name,
        #[Rule('required|email|unique:clients,email')]
        public string $email,
        #[Rule('nullable|string')]
        public ?string $phone,
        #[Rule('nullable|date')]
        public ?string $birth_date,
        #[Rule('nullable|string|max:255')]
        public ?string $address,
        #[Rule('nullable|string|max:255')]
        public ?string $complement,
        #[Rule('nullable|string|max:255')]
        public ?string $neighborhood,
        #[Rule('nullable|string|max:20')]
        public ?string $zip_code,
        #[Rule('nullable|date')]
        public ?string $created_at,
        public ?array $orders = null,
    ) {}

    public function getBirthDateAttribute($value): ?string
    {
        return $this->birth_date ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function getCreatedAtAttribute($value): ?string
    {
        return $this->created_at ? Carbon::parse($value)->toIso8601String() : null;
    }

    public static function fromModel(Client $client, string $context = 'client'): self
    {
        return self::from([
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'birth_date' => $client->birth_date,
            'address' => $client->address,
            'complement' => $client->complement,
            'neighborhood' => $client->neighborhood,
            'zip_code' => $client->zip_code,
            'orders' => $context === 'client' ? $client->orders->map(fn($order) => OrderDataOut::fromModel($order, 'client')->toArray())->toArray(): null, // avoid circular reference
            'created_at' => $client->created_at->toIso8601String(),
        ]);
    }

    public static function attributes(...$args): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'birth_date' => 'Birth Date',
            'address' => 'Address',
            'complement' => 'Complement',
            'neighborhood' => 'Neighborhood',
            'zip_code' => 'ZIP Code',
            'created_at' => 'Created At',
            'orders' => 'Orders',
        ];
    }
}
