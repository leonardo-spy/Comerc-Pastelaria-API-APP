<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Mail\OrderCreatedMail;
use App\Models\Order;
use App\Models\Product;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Data\Order\OrderDataOut;

/**
 * @OA\Schema(
 *     schema="Order",
 *     type="object",
 *     required={"id", "client", "products"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="client", ref="#/components/schemas/Client"),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"id", "name", "quantity", "priceAtTimeOfOrder"},
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Produto A"),
 *             @OA\Property(property="type", type="string", nullable=true, example="eletrônico"),
 *             @OA\Property(property="photoUrl", type="string", nullable=true, example="products_photos/abcd1234.jpg"),
 *             @OA\Property(property="quantity", type="integer", example=2),
 *             @OA\Property(property="priceAtTimeOfOrder", type="number", format="float", example=39.98)
 *         )
 *     ),
 *     @OA\Property(property="createdAt", type="string", format="date-time", nullable=true, example="2025-05-28T20:00:00Z"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", nullable=true, example="2025-05-29T15:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="OrderCreateRequest",
 *     type="object",
 *     required={"client_id", "products"},
 *     @OA\Property(property="client_id", type="integer", example=1),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         minItems=1,
 *         @OA\Items(
 *             type="object",
 *             required={"product_id", "quantity"},
 *             @OA\Property(property="product_id", type="integer", example=1),
 *             @OA\Property(property="quantity", type="integer", minimum=1, example=2)
 *         )
 *     )
 * )
 */
class OrderController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Lista todos os pedidos",
     *     tags={"Orders"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de pedidos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Order")
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $orders = Order::with(['client', 'products'])->paginate(15);
        return $this->jsonResponseSuccess(OrderDataOut::collect($orders), 'Pedidos listados com sucesso.');
    }

    /**
     * Store a newly created resource in storage.
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Cria um novo pedido",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/OrderCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pedido criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(StoreOrderRequest $request)
    {
        $validatedData = $request->validated();
        $order = null;

        DB::beginTransaction();

        try {
            $order = Order::create(['client_id' => $validatedData['client_id']]);

            $productsToAttach = collect($validatedData['products'])->mapWithKeys(function ($productData) {
                $product = Product::findOrFail($productData['product_id']); // search for the product by id
                return [
                    $productData['product_id'] => [
                        'quantity' => $productData['quantity'],
                        'price_at_purchase' => $product->price, // select the price at the time of purchase
                    ],
                ];
            })->all();

            $order->products()->attach($productsToAttach);
            $order->load('client', 'products'); // loading for sending email

            // Send email to the client
            Mail::to($order->client->email)->send(new OrderCreatedMail($order));

            DB::commit();

            return $this->jsonCreatedResponse(OrderDataOut::fromModel($order), 'Pedido criado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar pedido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $validatedData
            ]);

            return $this->jsonResponseError(
                'Ocorreu um erro ao processar seu pedido. Por favor, tente novamente mais tarde.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                config('app.debug') ? ['debug_error' => $e->getMessage()] : null
            );
        }
    }

    /**
     * Display the specified resource.
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Mostra detalhes de um pedido",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do pedido",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do pedido",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(response=404, description="Pedido não encontrado")
     * )
     */
    public function show(Order $order)
    {
        // dont need to check for 404, route model binding handles it
        return $this->jsonResponseSuccess(OrderDataOut::fromModel($order->load(['client', 'products'])), 'Pedido exibido com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     * @OA\Put(
     *     path="/api/orders/{id}",
     *     summary="Atualiza um pedido existente",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do pedido a ser atualizado",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/OrderCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pedido atualizado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"client_id": {"The client id field is required."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido não encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            // Update the client if provided
            if (isset($validatedData['client_id'])) {
                $order->update([
                    'client_id' => $validatedData['client_id'],
                ]);
            }

            // Update the products (detach + attach new products)
            $productsToAttach = collect($validatedData['products'])->mapWithKeys(function ($productData) {
                $product = Product::findOrFail($productData['product_id']);
                return [
                    $productData['product_id'] => [
                        'quantity' => $productData['quantity'],
                        'price_at_purchase' => $product->price,
                    ],
                ];
            })->all();

            $order->products()->sync($productsToAttach);

            $order->load('client', 'products');

            DB::commit();

            return $this->jsonResponseSuccess(OrderDataOut::fromModel($order), 'Pedido atualizado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar pedido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $validatedData
            ]);

            return $this->jsonResponseError(
                'Ocorreu um erro ao atualizar o pedido. Por favor, tente novamente mais tarde.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                config('app.debug') ? ['debug_error' => $e->getMessage()] : null
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     * @OA\Delete(
     *     path="/api/orders/{id}",
     *     summary="Deleta um pedido",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do pedido",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=204, description="Pedido deletado com sucesso"),
     *     @OA\Response(response=404, description="Pedido não encontrado")
     * )
     */
    public function destroy(Order $order)
    {
        $order->delete(); // Soft delete
        return $this->jsonResponseNoContent();
    }

}
