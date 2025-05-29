<?php

namespace Tests\Unit\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Client;
use Symfony\Component\HttpFoundation\Response;

class ClientApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $endpointTestClients = '/api/clients';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_list_clients_successfully(): void
    {
        Client::factory()->count(3)->create();

        $response = $this->getJson($this->endpointTestClients);
        $response->assertStatus(Response::HTTP_OK)->assertJsonCount(3, 'data.data');
    }

    public function test_create_client_successfully(): void
    {
        $clientData = Client::factory()->make()->toArray();

        $response = $this->postJson($this->endpointTestClients, $clientData);
        $response->assertStatus(Response::HTTP_CREATED)->assertJsonFragment(['email' => $clientData['email']]);

        $this->assertDatabaseHas('clients', ['email' => $clientData['email']]);
    }

    public function test_create_client_fails_when_email_missing(): void
    {
        $clientData = Client::factory()->make(['email' => null])->toArray();

        $response = $this->postJson($this->endpointTestClients, $clientData);
        
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['email']);
    }

    public function test_create_client_fails_when_email_invalid(): void
    {
        $clientData = Client::factory()->make(['email' => 'invalid-email'])->toArray();

        $response = $this->postJson($this->endpointTestClients, $clientData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['email']);
    }

    public function test_create_client_fails_when_all_required_fields_missing(): void
    {
        $response = $this->postJson($this->endpointTestClients, []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['name', 'email', 'phone', 'birth_date', 'address', 'neighborhood', 'zip_code']);
    }

    public function test_create_client_fails_with_duplicate_email(): void
    {
        $olderClient = Client::factory()->create();

        $newClientData = Client::factory()->make([
            'email' => $olderClient->email,
        ])->toArray();

        $response = $this->postJson($this->endpointTestClients, $newClientData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJsonValidationErrors(['email']);
    }

    public function test_show_client_returns_correct_data(): void
    {
        $client = Client::factory()->create();

        $response = $this->getJson("{$this->endpointTestClients}/{$client->id}");
        
        $response->assertStatus(Response::HTTP_OK)->assertJsonFragment(['id' => (string)$client->id, 'email' => $client->email]);
    }

    public function test_show_client_for_invalid_id(): void
    {
        $response = $this->getJson("{$this->endpointTestClients}/999999");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_update_client_successfully(): void
    {
        $client = Client::factory()->create();

        $newName = $this->faker->name;
        $newEmail = $this->faker->unique()->safeEmail;

        $updateData = [
            'name' => $newName,
            'email' => $newEmail,
        ];

        $response = $this->putJson("{$this->endpointTestClients}/{$client->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)->assertJsonFragment(['name' => $newName, 'email' => $newEmail]);

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => $newName, 'email' => $newEmail]);
    }

    public function test_delete_client_soft_deletes_record(): void
    {
        $client = Client::factory()->create();

        $response = $this->deleteJson("{$this->endpointTestClients}/{$client->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('clients', ['id' => $client->id, 'deleted_at' => null]);
    }
}
