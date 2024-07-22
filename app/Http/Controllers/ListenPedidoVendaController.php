<?php

namespace App\Http\Controllers;

use App\Repository\SystemLogRepository;
use Exception;
use Illuminate\Http\Request;
use App\UseCases\ListenPedidoVendaUseCase;
use Illuminate\Http\JsonResponse;
Class ListenPedidoVendaController
{
    private $log;

    public function __construct()
    {
        $this->log = new SystemLogRepository();
    }

    /**
    * Controller para segunda interacao, recebendo os dados de Pedido de venda do PubSub.
    * 
    * @author Whelmer Silveira Neto
    * @return JsonResponse
    */

    public function __invoke(ListenPedidoVendaUseCase $listenPedidoVendaUseCase, Request $request)
    {
        try {
            return response()->json($listenPedidoVendaUseCase->execute($request->all()));
        } catch (Exception $exception) {

            $this->log->postLog(
                'ms-conversao-offline',
                '',
                'Erro ao publicar lead no topido pedido.gclid',
                'ListenPedidoVendaGclidController',
                'Error',
                'Mensagem: ' . json_encode($exception->getMessage())
            );

            return response()->json([
                'data' => [],
                'msg' => $exception->getMessage(),
                'success' => false,
                'status' => $exception->getCode() != 0 ? $exception->getCode() : 500
            ], 500);
        }
    }
}
