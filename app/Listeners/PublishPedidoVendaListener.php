<?php

namespace App\Listeners;

use App\Events\PublishPedidoVendaEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Services\PubSub\PubSubEventService;
use App\Repository\SystemLogRepository;
use Exception;

class PublishPedidoVendaListener implements ShouldQueue
{
    /**
     * Criando o Listener para recebimendo de dados e publicacao no topico do PubSub.
     *
     * @author Whelmer Silveira Neto
     * @return void
     */

    private $pubSubProject;
    private PubSubEventService $pubSubService;
    private $log;

    public function __construct(PubSubEventService $pubSubService)
    {
        $this->pubSubService = $pubSubService;
        $this->pubSubProject = getenv('GCP_PUBSUB_PROJECT_ID');
        $this->log = new SystemLogRepository();
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\PublishPedidoVendaEvent  $event
     * @return void
     */
    public function handle(PublishPedidoVendaEvent $event)
    {
        try {
            $payload = $event->payload;

            $pedido = $this->getPedidoAprovado($payload["FrotaID"], $payload["TFID"]);

            if ($pedido) {
                $publish = $this->pubSubService->enviarPayload([
                    'project' => $this->pubSubProject,
                    'topic' => 'pedido.criado',
                    'ordering_key' => 'FrotaID',
                    'payload' => [
                        "FrotaID" => $pedido["FrotaID"],
                        "LeadID" => $pedido["LeadID"],
                        "RazaoSocial" => $pedido["RazaoSocial"],
                        "Email" => $pedido["Email"],
                        "TFID" => $payload["TFID"],
                        "DataPedido" => $pedido["DataPedido"],
                        "ValorVenda" => $pedido["ValorVenda"]
                    ]
                ]);

                $this->log->postLog(
                    'ms-conversao-offline',
                    '',
                    'Payload publicado no topido pedido.criado',
                    'PublishPedidoVendaListener@handle',
                    'Publish',
                    'Publish response: ' . json_encode($publish)
                );
            } else {
                $this->log->postLog(
                    'ms-conversao-offline',
                    '',
                    'Pedido de venda não encontrado. Falha ao publicar no topico pedido.criado',
                    'PublishPedidoVendaListener@handle',
                    'Error',
                    "FrotaID = {$payload['FrotaID']}"
                );
            }
        } catch (Exception $e) {
            $this->log->postLog(
                'ms-conversao-offline',
                '',
                'Falha ao buscar pedido de venda',
                'PublishPedidoVendaListener@handle',
                'Publish',
                'Mensagem: ' . json_encode($e->getMessage())
            );
        }
    }

    /**
     * Funcao importada no Listener para ganho de performance
     * 
     * @param string|number $frotaID
     * @param string|number $tfid
     */
    private function getPedidoAprovado(string|number $frotaID, string|number $tfid)
    {
        try {
            //  TFID FILIAIS SN
            if ($tfid == 7) {
                #   QUERY REMOVIDA POR CONTER DADOS SENSIVEIS
                $query = "RAW-QUERY-PARA-SELECAO-DE-PEDIDOS-DE-VENDA ";

                $queryResult = collect(DB::connection('mysql_vetor')->select(DB::raw($query), ['frotaID' => $frotaID]))->first();

                $this->log->postLog(
                    'ms-conversao-offline',
                    '',
                    'Resultado da query getPedidoAprovado',
                    'PublishPedidoVendaListener@getPedidoAprovado',
                    'Publish',
                    'Query: ' . json_encode($query) . ' => Resultado: '. json_encode($queryResult)
                );

                if ($queryResult) {
                    return get_object_vars($queryResult);
                }
                return null;
            }
        } catch(Exception $e) {
            $this->log->postLog(
                'ms-conversao-offline',
                '',
                'Falha ao buscar pedido de venda',
                'PublishPedidoVendaListener@getPedidoAprovado',
                'Error',
                'Mensagem: ' . json_encode($e->getMessage())
            );

            return null;
        }
    }
}
