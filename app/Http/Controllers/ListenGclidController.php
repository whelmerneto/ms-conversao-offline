<?php

namespace App\Http\Controllers;

use App\Repository\SystemLogRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\UseCases\ListenGclidUseCase;

Class ListenGclidController
{
    private $log;
    /**
    * Controller para terceira interacao, recebe os dados de Pedido de venda e Leads do PubSub.
    * 
    * @author Whelmer Silveira Neto
    * @return JsonResponse
    */

    public function __construct()
    {
        $this->log = new SystemLogRepository();
    }
    public function __invoke(ListenGclidUseCase $listenGclidUseCase, Request $request)
    {
        try {
            return response()->json($listenGclidUseCase->execute($request->all()));
        } catch (Exception $exception) {

            $this->log->postLog(
                'ms-conversao-offline',
                'ListenPedidoVendaGclidController',
                'Erro enviar conversao para a api google ads',
                '',
                'Error',
                $exception->getMessage()
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
