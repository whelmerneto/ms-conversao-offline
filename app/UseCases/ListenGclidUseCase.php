<?php

namespace App\UseCases;

use App\Services\PubSub\PubSubEventService;
use App\Models\Leads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Services\UploadOfflineConversion;
use App\Services\GoogleAds\GoogleAdsService;
use Carbon\Carbon;
use App\Utils\GoogleAds\ArgumentParser;
use App\Utils\GoogleAds\ArgumentNames;
use GetOpt\GetOpt;
use App\Repository\SystemLogRepository;
use Exception;

class ListenGclidUseCase 
{
    /**
    * Classe para interacao final, recebendo os dados de Pedido de venda e do Lead pelo PubSub.
    * Fara o envio para o Google Ads via Service
    * 
    * @author Whelmer Silveira Neto
    * @return Array
    */

    private $pubSubService;
    private $pubSubProject;
    private $customerIdValue; 
    private $conversionActionId;
    private $googleAdsService;
    private $log;

	public function __construct(GoogleAdsService $googleAdsService) 
    {
        $this->pubSubProject = getenv('GCP_PUBSUB_PROJECT_ID');
        $this->pubSubService = new PubSubEventService();
        $this->googleAdsService = $googleAdsService;

        // Array de Customer ID do Google Ads parametrizado conforme TFID
        $this->customerIdValue = [
            '7' => getenv("CUSTOMER_ID_SN")
        ];
        // Array de ConversionActionId do Google Ads parametrizado conforme TFID
        $this->conversionActionId = [
            '7' => getenv("CONVERSION_ACTION_ID_SN")
        ];

        $this->log = new SystemLogRepository();
    }

    public function execute($pedidoInfo) 
    {
        try {
            $this->log->postLog(
                'ms-conversao-offline',
                'ListenGclidUseCase',
                'Debugger ListenGclidUse case: $pedidoInfo value',
                '',
                'Debug',
               $pedidoInfo
            );

            $arrayData = [
                'customer_id' => $this->customerIdValue[$pedidoInfo["TFID"]],
                'conversion_action_id' => $this->conversionActionId[$pedidoInfo["TFID"]],
                'gclid_value' => $pedidoInfo["gclid_value"],
                'data_pedido' => $pedidoInfo["DataPedido"],
                'valor_venda' => $pedidoInfo["ValorVenda"]
            ];

            /** usando carbon::now ao inves da data do pedido
             *  pois a data pode ter sido antes da criacao do gclid
             */
            $dateFormatted = Carbon::now()->format('Y-m-d H:i:sP');

            //  Formatando os dados conforme biblioteca do PHP
            $options = (new ArgumentParser())->parseCommandArguments([
                ArgumentNames::CUSTOMER_ID => GetOpt::REQUIRED_ARGUMENT,
                ArgumentNames::CONVERSION_ACTION_ID => GetOpt::REQUIRED_ARGUMENT,
                ArgumentNames::GCLID => GetOpt::OPTIONAL_ARGUMENT,
                ArgumentNames::GBRAID => GetOpt::OPTIONAL_ARGUMENT,
                ArgumentNames::WBRAID => GetOpt::OPTIONAL_ARGUMENT,
                ArgumentNames::CONVERSION_DATE_TIME => GetOpt::REQUIRED_ARGUMENT,
                ArgumentNames::CONVERSION_VALUE => GetOpt::REQUIRED_ARGUMENT,
                ArgumentNames::CONVERSION_CUSTOM_VARIABLE_ID => GetOpt::OPTIONAL_ARGUMENT,
                ArgumentNames::CONVERSION_CUSTOM_VARIABLE_VALUE => GetOpt::OPTIONAL_ARGUMENT
            ]);

            //  Enviando conversao
            $uploadConversion = $this->googleAdsService->upLoadConversion(
                $options[ArgumentNames::CUSTOMER_ID] ?: $this->customerIdValue[$pedidoInfo["TFID"]],
                $options[ArgumentNames::CONVERSION_ACTION_ID] ?: $this->conversionActionId[$pedidoInfo["TFID"]],
                $options[ArgumentNames::GCLID] ?: $arrayData["gclid_value"],
                null,
                null,
                $options[ArgumentNames::CONVERSION_DATE_TIME] ?: $dateFormatted,
                $options[ArgumentNames::CONVERSION_VALUE] ?: $arrayData["valor_venda"],
                null,
                null
            );

            return $uploadConversion;
        	
        } catch (Exception $exception) {
            $this->log->postLog(
                'ms-conversao-offline',
                'ListenGclidUseCase',
                'Erro ao enviar conversao para a Api do Google Ads',
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
