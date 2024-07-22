<?php

namespace App\Repository;

use App\Models\Leads;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Repository\SystemLogRepository;
use Exception;

Class PedidoRepository
{
    private $log;
    public function __construct()
    {
        $this->log = new SystemLogRepository();
    }

    /**
    * Classe para processamento de Leads
    * 
    * @author Whelmer Silveira Neto
    * @return array
    */

    public function getLeads($clientInfo)
    {
        //  Considerando TFID 7 SN
        if ($clientInfo["TFID"] == 7) {
            #   Se possui lead vinculado ao pedido de venda, busca pelo lead
            if (!is_null($clientInfo["LeadID"])) {
                $this->log->postLog(
                    'ms-conversao-offline',
                    '',
                    'Buscando Leads pelo MobiautoID',
                    'PedidoRepository@getLeads',
                    'Publish',
                    'Payload: '. json_encode($clientInfo)
                );

               $lead = $this->getLeadsByMobiID($clientInfo);
            } else {
                $this->log->postLog(
                    'ms-conversao-offline',
                    '',
                    'Buscando Leads pelos dados do Cliente',
                    'PedidoRepository@getLeads',
                    'Publish',
                    'Payload: '. json_encode($clientInfo)
                );
                #   Se nao possui lead vinculado ao pedido de venda, busca por like
                $lead = $this->getLeadsByClientInfo($clientInfo);
            }

            if ($lead && !is_null($lead['gclid_value'])) {
                $this->log->postLog(
                    'ms-conversao-offline',
                    '',
                    'Lead com gclid vinculado encontrado',
                    'PedidoRepository@getLeads',
                    'Publish',
                    'Payload: '. json_encode($lead)
                );

                return [
                    "data" => $lead,
                    "msg" => "Lead com gclid vinculado encontrado",
                    "success" => true
                ];
            }

            $this->log->postLog(
                'ms-conversao-offline',
                '',
                'Lead com gclid não encontrado',
                'PedidoRepository@getLeads',
                'Publish',
                'Payload: '. json_encode($lead)
            );
            #   Se não tem gclid, retorna false
            return [
                "data" => [],
                "msg" => "Lead com gclid não encontrado",
                "success" => false
            ];
        }
    }

    /**
    * Metodo para buscar Leads pela pelo LeadID informado no pedido de venda. Buscando a partir do MobiautoID
    * 
    * @author Whelmer Silveira Neto
    * @param array $clientInfo
    * @return array
    */
    private function getLeadsByMobiID($clientInfo)
    {
        try {
            $leads = Leads::orderByDesc('id')->take(600)->get();
            $lead = [];

            foreach ($leads as $lead_busca) {
                //  Solucao para Leads sem response armazenado
                if (is_array(json_decode($lead_busca["response"])) || empty(json_decode($lead_busca["response"]))) {
                    continue;
                }

                //  Solucao para buscar mobiautoID dentro da coluna response(json)
                $response = json_decode($lead_busca["response"]);
                if (isset($response->mobiauto) && !is_null(json_decode($response->mobiauto))) {
                    $leadSalvo = json_decode($response->mobiauto)->id;
                }

                if ($clientInfo["LeadID"] == $leadSalvo) {
                    $lead = $lead_busca->toArray();
                    //  *Solucao temporaria | Merge lead + pedido para uso na Terceira interacao com os dados
                    $lead = array_merge($lead, $clientInfo);
                }
            }

            return $lead;
        } catch (Exception $e) {
            $this->log->postLog(
                'ms-conversao-offline',
                '',
                'Falha ao buscar pedido de venda',
                'PublishPedidoVendaListener@getPedidoAprovado',
                'Error',
                'Mensagem: ' . json_encode($e->getMessage())
            );
        }
    }

    /**
    * Metodo para buscar Leads pela informacao do cliente no pedido de venda
    * 
    * @author Whelmer Silveira Neto
    * @param array $clientInfo
    * @return array
    */
    private function getLeadsByClientInfo($clientInfo)
    {
        #   Se nao possui lead vinculado ao pedido de venda, busca por like
        $lead = Leads::select('*')->where('veiculo_id', $clientInfo["FrotaID"])
        ->where(function ($query) use ($clientInfo) {
            $query->where('pessoa_nome', 'like', "%{$clientInfo["RazaoSocial"]}%")
                ->orWhere('pessoa_email', 'like', "%{$clientInfo["Email"]}%");
        })
        ->first();

        if ($lead) {
            $lead = array_merge($lead->toArray(), $clientInfo);
        }

        return $lead;
    }
}
