<?php

namespace App\UseCases;

use App\Services\PubSub\PubSubEventService;
use App\Models\Leads;
use App\Repository\PedidoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Repository\SystemLogRepository;
use Exception;

class ListenPedidoVendaUseCase 
{
    /**
    * Classe para segunda interacao, recebendo os dados de Pedido de venda do PubSub
    * E fara o publish caso possua Lead vinculado ao cliente
    * 
    * @author Whelmer Silveira Neto
    * @return Array
    */

	private $pedidoRepository;
    private $pubSubService;
    private $pubSubProject;
    private $log;

	public function __construct()
    {
        $this->pubSubProject = getenv('GCP_PUBSUB_PROJECT_ID');
        $this->pedidoRepository = new PedidoRepository();
        $this->pubSubService = new PubSubEventService();
        $this->log = new SystemLogRepository();
    }

    public function execute($pedidoInfo) 
    {
        try {
            $leads = $this->pedidoRepository->getLeads($pedidoInfo);

            # se retornar $leads, fazer o publish pro topico pedido.gclid
            if ($leads["success"]) {
                $response = $this->pubSubService->enviarPayload([
                    'project' => $this->pubSubProject,
                    'topic' => 'pedido.gclid',
                    'ordering_key'=> 'id',
                    'payload' => $leads["data"]
                ]);

                $this->log->postLog(
                    'ms-conversao-offline',
                    '',
                    'Payload publicado no topido pedido.gclid',
                    'ListenPedidoVendaUseCase@execute',
                    'Publish',
                    'Payload: '. json_encode($leads["data"])
                );

                return [
                    "success" => true,
                    "msg" => $response
                ];
            }

            $this->log->postLog(
                'ms-conversao-offline',
                '',
                'NAO Publicou Payload no topido pedido.gclid',
                'ListenPedidoVendaUseCase@execute',
                'Publish',
                'Leads: '. json_encode($leads)
            );
            return [
                "success" => $leads["success"],
                "msg" => $leads["msg"]
            ];
        } catch (Exception $e) {
            $this->log->postLog(
                'ms-conversao-offline',
                '',
                'Falha ao buscar pedido de venda',
                'PublishPedidoVendaListener@execute',
                'Error',
                'Mensagem: ' . json_encode($e->getMessage())
            );
        }
    }
}
