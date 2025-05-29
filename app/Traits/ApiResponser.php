<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource; // Para as constantes de status HTTP
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

trait ApiResponser
{
    /**
     * Retorna uma resposta de sucesso padronizada.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function jsonResponseSuccess($data, string|null $message = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->additional($response)->response()->setStatusCode($statusCode);
        }

        $response['data'] = $data;

        return response()->json($response, $statusCode);
    }


    /**
     * Retorna uma resposta de erro padronizada.
     *
     * @param string|null $message
     * @param int $statusCode
     * @param mixed|null $errors
     * @return JsonResponse
     */
    protected function jsonResponseError(string|null $message = null, int $statusCode, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Resposta para itens criados com sucesso (201).
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function jsonCreatedResponse($data, string $message = 'Recurso criado com sucesso.'): JsonResponse
    {
        return $this->jsonResponseSuccess($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Resposta para deleção bem-sucedida (204 No Content).
     *
     * @param string|null $message (opcional, geralmente não há corpo na resposta 204)
     * @return JsonResponse
     */
    protected function jsonResponseNoContent(string|null $message = null): JsonResponse
    {
        if ($message) {
            return $this->jsonResponseSuccess(null, $message, Response::HTTP_OK);
        }
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
