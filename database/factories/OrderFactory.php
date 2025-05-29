<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Get a new Faker instance.
     *
     * @return \Faker\Generator
     */
    public function withFaker()
    {
        return \Faker\Factory::create('pt_BR');
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            // Cria de 1 a 3 produtos e associa ao pedido
            $products = Product::factory()->count(rand(1, 3))->create();

            collect($products)->each(function ($product) use ($order) {
                $order->products()->attach($product->id, [
                    'quantity' => rand(1, 5),
                    'price_at_purchase' => $product->price ?? fake()->randomFloat(2, 10, 100),
                ]);
            });
        });
    }
}
