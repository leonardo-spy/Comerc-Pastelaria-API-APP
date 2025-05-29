@php
    $productRows = collect($order->products)->map(function ($product) {
        $quantity = $product->pivot->quantity;
        $price = $product->pivot->price_at_purchase;
        return [
            'name' => $product->name,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $quantity * $price,
        ];
    });

    $total = $productRows->sum('subtotal');
@endphp

<x-mail::message>
# Pedido Confirmado! 🎉

Olá, **{{ $clientName }}**!

Obrigado pelo seu pedido! Seu pedido #**{{ $order->id }}** foi recebido e está sendo processado.

**Detalhes do Pedido:**

<x-mail::table>
| Produto       | Quantidade    | Preço Unitário | Subtotal      |
| :------------ |:-------------:|:---------------:|:--------------:|
@foreach ($productRows as $row)
| {{ $row['name'] }} | {{ $row['quantity'] }} | R$ {{ number_format($row['price'], 2, ',', '.') }} | R$ {{ number_format($row['subtotal'], 2, ',', '.') }} |
@endforeach
</x-mail::table>

**Total do Pedido: R$ {{ number_format($total, 2, ',', '.') }}**

Você pode ver os detalhes do seu pedido aqui:
<x-mail::button :url="$orderUrl">
Ver Pedido
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
