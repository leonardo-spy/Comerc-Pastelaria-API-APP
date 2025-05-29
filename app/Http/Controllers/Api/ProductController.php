<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Storage;
use App\Data\Product\ProductDataOut;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     required={"id", "name", "price", "photo", "type"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="Produto A"),
 *     @OA\Property(property="price", type="number", format="float", example=19.99),
 *     @OA\Property(property="photo", type="string", example="products_photos/abcd1234.jpg"),
 *     @OA\Property(property="type", type="string", nullable=true, example="eletrônico"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2025-05-28T20:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2025-05-29T15:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="ProductCreateRequest",
 *     type="object",
 *     required={"name", "price", "photo"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Produto A"),
 *     @OA\Property(property="price", type="number", format="float", minimum=0.01, example=19.99),
 *     @OA\Property(
 *         property="photo",
 *         type="string",
 *         format="binary",
 *         description="Imagem do produto (jpeg, png, jpg, gif, svg), max 2048KB"
 *     ),
 *     @OA\Property(property="type", type="string", maxLength=100, nullable=true, example="eletrônico")
 * )
 *
 * @OA\Schema(
 *     schema="ProductUpdateRequest",
 *     type="object",
 *     @OA\Property(property="name", type="string", maxLength=255, example="Produto A"),
 *     @OA\Property(property="price", type="number", format="float", minimum=0.01, example=19.99),
 *     @OA\Property(
 *         property="photo",
 *         type="string",
 *         format="binary",
 *         nullable=true,
 *         description="Imagem do produto (jpeg, png, jpg, gif, svg), max 2048KB"
 *     ),
 *     @OA\Property(property="type", type="string", maxLength=100, nullable=true, example="eletrônico")
 * )
 */
class ProductController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     * @OA\Get(
     *     path="/api/products",
     *     summary="Lista todos os produtos",
     *     tags={"Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de produtos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $products = Product::paginate(15);
        return $this->jsonResponseSuccess(ProductDataOut::collect($products), 'Produtos listados com sucesso.');
    }

    /**
     * Store a newly created resource in storage.
     * @OA\Post(
     *     path="/api/products",
     *     summary="Cria um novo produto",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ProductCreateRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Produto criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store(env('APP_PATH_PRODUCTS_PHOTO'), 'public');
            $validatedData['photo'] = $path;
        }

        $product = Product::create($validatedData);
        return $this->jsonCreatedResponse(ProductDataOut::fromModel($product), 'Produto criado com sucesso.');
    }

    /**
     * Display the specified resource.
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Mostra detalhes de um produto",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do produto",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do produto",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=404, description="Produto não encontrado")
     * )
     */
    public function show(Product $product)
    {
        // dont need to check for 404, route model binding handles it
        return $this->jsonResponseSuccess(ProductDataOut::fromModel($product), 'Produto exibido com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     * @OA\Put(
     *     path="/api/products/{id}",
     *     summary="Atualiza um produto existente",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do produto",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ProductUpdateRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produto atualizado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=404, description="Produto não encontrado"),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('photo')) {
            // delete the old photo if it exists
            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
            }
            $path = $request->file('photo')->store(env('APP_PATH_PRODUCTS_PHOTO'), 'public');
            $validatedData['photo'] = $path;
        }

        $product->update($validatedData);
        return $this->jsonResponseSuccess(ProductDataOut::fromModel($product), 'Produto atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     summary="Deleta um produto",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do produto",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=204, description="Produto deletado com sucesso"),
     *     @OA\Response(response=404, description="Produto não encontrado")
     * )
     */
    public function destroy(Product $product)
    {
        // with soft delete, we don't delete the photo physically!
        $product->delete();
        return $this->jsonResponseNoContent();
    }

}
