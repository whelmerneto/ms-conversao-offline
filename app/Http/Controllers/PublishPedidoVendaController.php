<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use App\Events\PublishPedidoVendaEvent;
use Illuminate\Support\Facades\DB;
use App\Repository\SystemLogRepository;
use Illuminate\Http\JsonResponse;

Class PublishPedidoVendaController extends Controller
{
     /**
     * Controller para primeira interacao pos venda para verificacao de conversao offline.
     *
     * @author Whelmer Silveira Neto
     * @return JsonResponse
     */
    public function __construct() 
    {}

    public function __invoke(Request $request, SystemLogRepository $log)
    {
        try {
            //  Alternativa para a falta do FormRequest no Lumen
            $this->validate($request, [
                'FrotaID' => 'required',
                'TFID' => 'required'
            ]);
            event(new PublishPedidoVendaEvent($request->all()));

            $log->postLog(
                'ms-conversao-offline',
                '',
                'Evento disparado. FrotaID recebido',
                'PublishPedidoVendaController',
                'Publish',
                "Mensagem: FrotaID = {$request['FrotaID']}, TFID = {$request['TFID']}"
            );

            $response = [
                "msg" => "FrotaID recebido. Processando dados.",
                "code" => 200
            ];

        } catch (Exception $e) {
            $response = [
                "msg" => "Falha ao disparar evento. Conferir Logs.",
                "code" => 500
            ];

            $log->postLog(
                'ms-conversao-offline',
                '',
                'Falha ao disparar evento PublishPedidoVendaEvent',
                'PublishPedidoVendaController',
                'Erro',
                'Mensagem: ' . $e->getMessage()
            );
        }
        return response()->json([
            "success" => true,
            "message" => $response['msg']
        ], $response['code']);
    }
}
