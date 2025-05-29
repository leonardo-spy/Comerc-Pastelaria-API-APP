<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Traits\ApiResponser;
use App\Data\Client\ClientDataOut;

/**
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     required={"id", "name", "email", "phone", "birthDate", "address", "neighborhood", "zipCode"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", maxLength=255, example="João Silva"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="joao.silva@email.com"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="11999999999"),
 *     @OA\Property(property="birthDate", type="string", format="date", example="1985-12-25"),
 *     @OA\Property(property="address", type="string", maxLength=255, example="Rua Exemplo, 123"),
 *     @OA\Property(property="complement", type="string", nullable=true, maxLength=255, example="Apartamento 101"),
 *     @OA\Property(property="neighborhood", type="string", maxLength=255, example="Centro"),
 *     @OA\Property(property="zipCode", type="string", maxLength=9, example="12345-678"),
 *     @OA\Property(property="createdAt", type="string", format="date-time", nullable=true, example="2025-05-28T20:00:00Z"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", nullable=true, example="2025-05-29T15:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="ClientCreateRequest",
 *     type="object",
 *     required={"name", "email", "phone", "birthDate", "address", "neighborhood", "zipCode"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="João Silva"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="joao.silva@email.com"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="11999999999"),
 *     @OA\Property(property="birthDate", type="string", format="date", example="1985-12-25"),
 *     @OA\Property(property="address", type="string", maxLength=255, example="Rua Exemplo, 123"),
 *     @OA\Property(property="complement", type="string", nullable=true, maxLength=255, example="Apartamento 101"),
 *     @OA\Property(property="neighborhood", type="string", maxLength=255, example="Centro"),
 *     @OA\Property(property="zipCode", type="string", maxLength=9, example="12345-678")
 * )
 *
 * @OA\Schema(
 *     schema="ClientUpdateRequest",
 *     type="object",
 *     @OA\Property(property="name", type="string", maxLength=255, example="João Silva"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="joao.silva@email.com"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="11999999999"),
 *     @OA\Property(property="birthDate", type="string", format="date", example="1985-12-25"),
 *     @OA\Property(property="address", type="string", maxLength=255, example="Rua Exemplo, 123"),
 *     @OA\Property(property="complement", type="string", nullable=true, maxLength=255, example="Apartamento 101"),
 *     @OA\Property(property="neighborhood", type="string", maxLength=255, example="Centro"),
 *     @OA\Property(property="zipCode", type="string", maxLength=9, example="12345-678")
 * )
 */
class ClientController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     * @OA\Get(
     *     path="/api/clients",
     *     summary="Lista todos os clientes",
     *     tags={"Clients"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de clientes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Client")
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $clients = Client::paginate(15);

        return $this->jsonResponseSuccess(ClientDataOut::collect($clients), 'Clientes listados com sucesso.');
    }

    /**
     * Store a newly created resource in storage.
     * @OA\Post(
     *     path="/api/clients",
     *     summary="Cria um novo cliente",
     *     tags={"Clients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ClientCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->validated());

        return $this->jsonCreatedResponse(ClientDataOut::fromModel($client), 'Cliente criado com sucesso.');
    }

    /**
     * Display the specified resource.
     * @OA\Get(
     *     path="/api/clients/{id}",
     *     summary="Mostra detalhes de um cliente",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do cliente",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do cliente",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(response=404, description="Cliente não encontrado")
     * )
     */
    public function show(Client $client)
    {
        // dont need to check for 404, route model binding handles it
        return $this->jsonResponseSuccess(ClientDataOut::fromModel($client), 'Cliente exibido com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     * @OA\Put(
     *     path="/api/clients/{id}",
     *     summary="Atualiza um cliente existente",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do cliente",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ClientUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente atualizado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(response=404, description="Cliente não encontrado"),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return $this->jsonResponseSuccess(ClientDataOut::fromModel($client), 'Cliente atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     * @OA\Delete(
     *     path="/api/clients/{id}",
     *     summary="Deleta um cliente",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do cliente",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=204, description="Cliente deletado com sucesso"),
     *     @OA\Response(response=404, description="Cliente não encontrado")
     * )
     */
    public function destroy(Client $client)
    {
        $client->delete(); // Soft delete
        return $this->jsonResponseNoContent();
    }

}
