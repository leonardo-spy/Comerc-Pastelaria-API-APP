<?php

namespace Tests\Unit\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $endpointTestProducts = '/api/products';

    protected function setUp(): void
    {
        parent::setUp();
    }
    
    public function test_can_list_products_successfully(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson($this->endpointTestProducts);
    
        $response->assertStatus(Response::HTTP_OK)->assertJsonCount(3, 'data.data');
    }

    public function test_create_product_with_photo_successfully(): void
    {
        $productData = Product::factory()->make()->toArray();

        $photoName = basename($productData['photo']);
        $productData['photo'] = UploadedFile::fake()->image($photoName);

        $response = $this->post($this->endpointTestProducts, $productData);
        $response->assertStatus(Response::HTTP_CREATED)->assertJsonFragment(['name' => $productData['name']]);

        $this->assertDatabaseHas('products', ['name' => $productData['name'], 'id'=> $response['data']['id']]);
    }

    public function test_create_product_fails_when_photo_missing(): void
    {
        $productData = Product::factory()->make(['photo' => null])->toArray();

        $response = $this->postJson($this->endpointTestProducts, $productData);
        
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['photo']);
    }

    public function test_create_product_fails_when_photo_invalid(): void
    {
        $productData= Product::factory()->make()->toArray();
        // Photo as string instead of UploadedFile

        $response = $this->postJson($this->endpointTestProducts, $productData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['photo']);
    }

    public function test_create_product_fails_when_price_invalid(): void
    {
        $productData= Product::factory()->make()->toArray();
        
        $photoName = basename($productData['photo']);
        $productData['photo'] = UploadedFile::fake()->image($photoName);
        $productData['price'] = 'invalid-price';

        $response = $this->postJson($this->endpointTestProducts, $productData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['price']);
    }

    public function test_create_product_fails_when_all_required_fields_missing(): void
    {
        $response = $this->postJson($this->endpointTestProducts, []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['name','price','photo']);
    }

    public function test_show_product_returns_correct_data(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("{$this->endpointTestProducts}/{$product->id}");

        $response->assertStatus(Response::HTTP_OK)->assertJsonFragment(['id' => (string) $product->id, 'name' => $product->name]);
    }

    public function test_show_product_for_invalid_id(): void
    {
        $response = $this->getJson("{$this->endpointTestProducts}/999999");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_update_client_successfully(): void
    {
        $product = Product::factory()->create();

        $newName = $this->faker->words(3, true);
        $newPrice = $this->faker->randomFloat(2, 10, 1000);

        $updateData = [
            'name' => $newName,
            'price' => $newPrice,
        ];

        $response = $this->putJson("{$this->endpointTestProducts}/{$product->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)->assertJsonFragment(['name' => $newName, 'price' => $newPrice]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $newName, 'price' => $newPrice]);
    }

    public function test_delete_client_soft_deletes_record(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("{$this->endpointTestProducts}/{$product->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id, 'deleted_at' => null]);
    }
}
