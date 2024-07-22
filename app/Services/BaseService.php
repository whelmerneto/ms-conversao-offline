<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Redis;

abstract class BaseService
{
    protected Client $client;
    protected int $statusCode;

    /**
     * Cria um cliente guzzle com o base url e os headers
     * @param string|null $url
     * @param array|null $headers
     */
    final protected function client(?string $url = null, ?array $headers = []): void
    {
        if ($url) {
            $this->client = new Client([
                'base_uri' => $url,
                'headers' => $headers
            ]);
        } else {
            $this->client = new Client();
        }
    }

    /**
     * Efetua a request de acordo com o verbo e os parâmetros passados
     * @param string $type = [post, get, put, delete]
     * @param string $uri = /url-valid/param-valid
     * @param array|null $params
     * @return mixed
     * @throws \Exception
     * @throws GuzzleException
     */
    final protected function request(string $type, string $uri, ?array $params = [])
    {
        try {
            $response = $this->client->request($type, $uri, $params);

            /**
             * HTTP_STATUS_CODE types
             * https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status
             */
            $this->statusCode = $response->getStatusCode();
            if ($this->statusCode >= 200 && $this->statusCode <= 299) {
                $body = json_decode($response->getBody(), true);
                if (is_array($body)) {
                    self::filterStatus($body);
                }
                return $body;
            }
            throw new \Exception(
                "Não foi possível concluir a requisição, código: {$response->getStatusCode()} ",
                400
            );
        } catch (ClientException $e) {
            throw new \Exception(
                $e->getMessage(),
                $e->getCode() !== 0 ? $e->getCode() : 400
            );
        }
    }

    /**
     * Obtem dados do cache redis baseado na chave
     * @param string $key
     * @return false|mixed
     * @throws \Exception
     */
    final protected function getRedisCache(string $key): ?array
    {
        try {
            if ($data = Redis::get($key)) {
                return json_decode($data, true);
            }
            return null;
        } catch (\RedisException $redisException) {
            throw new \Exception("Falha ao obter o cache, erro: {$redisException->getMessage()}", 400);
        }
    }

    /**
     * Cria o cache dentro do redis baseado na chave e nos dados.
     * @param string $key
     * @param array $data
     * @param int|null $time
     * @throws \Exception
     */
    final protected function createRedisCache(string $key, array $data, ?int $time = 600)
    {
        try {
            Redis::setex($key, $time, json_encode($data));
        } catch (\RedisException $redisException) {
            throw new \Exception("Falha ao gerar o cache, erro: {$redisException->getMessage()}", 400);
        }
    }

    /**
     * Limpa o cache do redis de indices passados
     * @param string $key
     * @throws \Exception
     */
    final protected function deleteRedisCache(string $key)
    {
        try {
            Redis::del($key);
        } catch (\RedisException $redisException) {
            throw new \Exception("Falha ao limpar o cache, erro: {$redisException->getMessage()}", 400);
        }
    }

    /**
     * Monta um array padrão para o response
     * @param array $response
     * @param int|null $statusCode
     * @return array
     * @throws \Exception
     */
    public static function getDefaultResponse(array $response, ?int $statusCode = 200): array
    {
        $data = isset($response['data']) ? $response['data'] : [];
        $message = isset($response['message'])
            ? $response['message']
            : (isset($response['msg'])
                ? $response['msg']
                : "Falha ao concluir a solicitação, verifique.");
        $statusCode = isset($response['statusCode'])
            ? $response['statusCode']
            : $statusCode;

        return [
            'data' => $data,
            'msg' => $message,
            'success' => $statusCode >= 200 && $statusCode <= 299 ?? false,
            'status' => $statusCode,
        ];
    }

    /**
     * Muitas vezes o status code é 200 e o success é false
     * Este método trata esse dado, e cria uma exception nova baseado no erro.
     * @param null|array $response
     * @throws \Exception
     */
    private static function filterStatus(?array $response)
    {
        if (isset($response['success'])) {
            if ($response['success'] === false) {
                $message = isset($response['message'])
                    ? $response['message']
                    : (isset($response['msg'])
                        ? $response['msg']
                        : "Falha ao concluir a solicitação, verifique.");
                throw new \Exception($message, 400);
            }
        }
    }

    protected function remoteAuth()
    {

    }

    protected function sentry()
    {

    }
}
