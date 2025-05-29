<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        // 'status',
        // 'total_amount',
    ];

    protected $casts = [
        // 'total_amount' => 'decimal:2',
    ];

    /**
     * Get the client that owns the order.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The products that belong to the order.
     */
    public function products(): BelongsToMany
    {
        // Se precisar de campos adicionais na tabela pivot (ex: quantity, price_at_time_of_order)
        return $this->belongsToMany(Product::class, 'order_product')
                    ->withPivot('quantity', 'price_at_purchase') // Exemplo com quantidade e preço no momento do pedido
                    ->withTimestamps();
    }
}
