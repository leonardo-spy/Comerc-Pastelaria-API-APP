<?php

namespace Tests\Unit\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Product;
use App\Models\Client;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCreatedMail;


class OrderApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $endpointTestOrders = '/api/orders';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_list_orders_successfully(): void
    {
        Order::factory()->count(3)->create();

        $response = $this->getJson($this->endpointTestOrders);
    
        $response->assertStatus(Response::HTTP_OK)->assertJsonCount(3, 'data.data');
    }

    public function test_create_order_successfully(): void
    {
        Mail::fake(); //important to intercepts all mail sent during the test

        $order = Order::factory()->create();

        $productsPayload = $order->products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'quantity' => $product->pivot->quantity,
                'price_at_purchase' => $product->pivot->price_at_purchase,
            ];
        })->toArray();

        $orderData = [
            'client_id' => $order->client_id,
            'products' => $productsPayload,
        ];

        $response = $this->postJson($this->endpointTestOrders, $orderData);

        $response->assertStatus(Response::HTTP_CREATED)->assertJsonPath('data.client.id', (string) $orderData['client_id']);

        $this->assertDatabaseHas('orders', ['client_id' => $orderData['client_id'], 'id' => (string)$response['data']['id']]);
    }

    public function test_create_order_fails_when_client_missing(): void
    {
        $order =  Order::factory()->create();

        $productsPayload = $order->products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'quantity' => $product->pivot->quantity,
                'price_at_purchase' => $product->pivot->price_at_purchase,
            ];
        })->toArray();

        $orderData = [
            'products' => $productsPayload
        ];

        $response = $this->postJson($this->endpointTestOrders, $orderData);
        
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['client_id']);
    }

    public function test_create_order_fails_when_products_missing(): void
    {
        $order = Order::factory()->make();

        $orderData = [
            'client_id' => $order->client_id
        ];

        $response = $this->postJson($this->endpointTestOrders, $orderData);
        
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['products']);
    }

    public function test_create_order_fails_when_client_invalid(): void
    {
        $orderData = Order::factory()->make(['client_id' => 'invalid-client'])->toArray();

        $response = $this->postJson($this->endpointTestOrders, $orderData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['client_id']);
    }

    public function test_order_ignores_invalid_price_at_purchase_and_uses_real_product_price(): void
    {
        Mail::fake(); //important to intercepts all mail sent during the test
        
        $order = Order::factory()->create();

        $productsPayload = $order->products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'quantity' => $product->pivot->quantity,
                'price_at_purchase' => 'invalid-price', // ignored, will use real product price
            ];
        })->toArray();

        $orderData = [
            'client_id' => $order->client_id,
            'products' => $productsPayload,
        ];

        $response = $this->postJson($this->endpointTestOrders, $orderData);

        $response->assertStatus(Response::HTTP_CREATED);

        collect($productsPayload)->each(function ($productData) use ($response) {
            $this->assertDatabaseHas('order_product', [
                'order_id' => $response->json('data.id'),
                'product_id' => $productData['product_id'],
                // check if price_at_purchase is the real product price
                'price_at_purchase' => Product::find($productData['product_id'])->price,
            ]);
        });
    }

    public function test_create_order_fails_when_all_required_fields_missing(): void
    {
        $response = $this->postJson($this->endpointTestOrders, []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['client_id', 'products']);
    }

    public function test_show_order_returns_correct_data(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("{$this->endpointTestOrders}/{$order->id}");

        $response->assertStatus(Response::HTTP_OK)->assertJsonFragment(['id' => (string)$order->id]);
    }

    public function test_show_order_for_invalid_id(): void
    {
        $response = $this->getJson("{$this->endpointTestOrders}/999999");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_update_order_successfully(): void
    {
        $order = Order::factory()->create();
        
        $newProducts = Product::factory()->count(2)->create();

        $updateData = [
            'client_id' => $order->client_id,
            'products' => [
                [
                    'product_id' => $newProducts[0]->id,
                    'quantity' => 3,
                ],
                [
                    'product_id' => $newProducts[1]->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->putJson("{$this->endpointTestOrders}/{$order->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'client_id' => (string) $updateData['client_id'],
            ]);

        collect($updateData['products'])->each(function ($productData) use ($order) {
            $this->assertDatabaseHas('order_product', [
                'order_id' => $order->id,
                'product_id' => $productData['product_id'],
                'quantity' => $productData['quantity']
            ]);
        });
        
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'client_id' => $updateData['client_id']]);
    }

    public function test_delete_order_soft_deletes_record(): void
    {
        $order = Order::factory()->create();

        $response = $this->deleteJson("{$this->endpointTestOrders}/{$order->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id, 'deleted_at' => null]);
    }

    public function test_can_create_order_and_sends_email(): void
    {
        Mail::fake(); //important to intercepts all mail sent during the test

        $client = Client::factory()->create(['name' => 'Cliente do email teste']);
        $product1 = Product::factory()->create(['price' => 50.00]);
        $product2 = Product::factory()->create(['price' => 30.00]);

        $orderData = [
            'client_id' => $client->id,
            'products' => [
                ['product_id' => $product1->id, 'quantity' => 2], // 2 * 50 = 100
                ['product_id' => $product2->id, 'quantity' => 1], // 1 * 30 = 30
            ],
        ];

        $response = $this->postJson($this->endpointTestOrders, $orderData);

        $response->assertStatus(Response::HTTP_CREATED)->assertJsonFragment(['client_id' => (string) $client->id]);

        $this->assertDatabaseHas('orders', ['client_id' => $client->id]);
        $this->assertDatabaseCount('order_product', 2);

        Mail::assertSent(OrderCreatedMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });

        Mail::assertSent(OrderCreatedMail::class, function ($mail) use ($response) {
            $orderId = $response->json('data.id');
            return (string) $mail->order->id === $orderId;
        });
    }

    public function test_create_order_successfully_with_email_custom(): void
    {
        $email = "exemplo@email.com";

        $client = Client::factory()->create(['email' => $email]);
        
        $order = Order::factory()->create(['client_id' => $client->id]);

        $productsPayload = $order->products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'quantity' => $product->pivot->quantity,
                'price_at_purchase' => $product->pivot->price_at_purchase,
            ];
        })->toArray();

        $orderData = [
            'client_id' => $order->client_id,
            'products' => $productsPayload,
        ];

        $response = $this->postJson($this->endpointTestOrders, $orderData);

        $response->assertStatus(Response::HTTP_CREATED)->assertJsonPath('data.client.id', (string) $orderData['client_id']);
        $response->assertStatus(Response::HTTP_CREATED)->assertJsonPath( 'data.client.email', $email);
        $response->assertStatus(Response::HTTP_CREATED)->assertJsonFragment(['message' => 'Pedido criado com sucesso.']);

        $this->assertDatabaseHas('orders', ['client_id' => $orderData['client_id'], 'id' => (string)$response['data']['id']]);
        $this->assertDatabaseHas('clients', ['id' => $orderData['client_id'],'email' => $email]);
    }
}
