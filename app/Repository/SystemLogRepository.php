<?php

namespace App\Repository;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Google\Cloud\Logging\LoggingClient;
use App\Helpers\StackdriverHandler;
use Illuminate\Support\Facades\DB;

class SystemLogRepository {

    public function __construct() { }

    /**
     * postLog
     *
     * @param  mixed $systemName
     * @param  mixed $subSystem
     * @param  mixed $descricao
     * @param  mixed $referencias
     * @param  mixed $tipo
     * @param  mixed $mensagem
     * @return void
     */
    public function postLog($systemName, $subSystem, $descricao, $referencias, $tipo, $mensagem, $cod_references = 0) {
        try {

//            $log = new Logger("{ "
//                    . "'systemName': '{$systemName}', }"
//                    . "'descricao': '{$descricao}', }"
//                    . "'referencias': '{$referencias}', }"
//                    . "'tipo': '{$tipo}', }"
//                    . "'mensagem': '{$mensagem}', }"
//                    . "'cod_references': '{$cod_references}' }");
//            $log->pushHandler(new StreamHandler("php://stdout"), Level::Info);
//            return $log->pushHandler(new StreamHandler("php://stdout"));

            $return = [];
            $return['success'] = true;
            $return['message'] = '';
            $return['total'] = 0;
            $return['data'] = '';
            $return['statusCode'] = 0;

//            $log->info('teste logs debugger ms-sm');
            GcpLogger::submit("{ "
                . "'systemName': '{$systemName}', }"
                . "'descricao': '{$descricao}', }"
                . "'referencias': '{$referencias}', }"
                . "'tipo': '{$tipo}', }"
                . "'mensagem': '{$mensagem}', }"
                . "'cod_references': '{$cod_references}' }"
            );

            $return['success'] = true;
            $return['message'] = "Log incluido com sucesso.";
            $return['statusCode'] = 201;
            return $return;
        } catch (\Exception $err) {
            $err->getMessage();
            return false;
        }
    }
}

class GcpLogger {

    public static function mountLogger() {
        $projectId = env('APP_NAME', 'ms-cv');

        $loggingClientOptions = [
            'keyFile' => self::mountConfigGcp()
        ];

        $stackdriverHandler = new StackdriverHandler(
                $projectId,
                $loggingClientOptions
        );
        return new Logger('stackdriver', [$stackdriverHandler]);
    }

    public static function submit(string $resume = '', array $context = [], int $statusCode = 200) {
        $logger = self::mountLogger();

        if ($statusCode >= 200 && $statusCode < 250) {
            $logger->info($resume, $context);
        } else if ($statusCode >= 250 && $statusCode < 300) {
            $logger->notice($resume, $context);
        } else if ($statusCode >= 300 && $statusCode < 400) {
            $logger->warning($resume, $context);
        } else if ($statusCode >= 400 && $statusCode < 500) {
            $logger->error($resume, $context);
        } else if ($statusCode >= 500 && $statusCode < 550) {
            $logger->critical($resume, $context);
        } else if ($statusCode >= 550 && $statusCode < 600) {
            $logger->alert($resume, $context);
        } else if ($statusCode == 600) {
            $logger->emergency($resume, $context);
        } else {
            $logger->debug($resume, $context);
        }
    }

    private static function mountConfigGcp() {
        return [
            'type' => env('GCLOUD_LOGGING_TYPE'),
            'project_id' => env('GCLOUD_LOGGING_PROJECT_ID'),
            'private_key_id' => env('GCLOUD_LOGGING_PRIVATE_KEY_ID'),
            'private_key' => env('GCLOUD_LOGGING_PRIVATE_KEY'),
            'client_email' => env('GCLOUD_LOGGING_CLIENT_EMAIL'),
            'client_id' => env('GCLOUD_LOGGING_CLIENT_ID'),
            'auth_uri' => env('GCLOUD_LOGGING_AUTH_URI'),
            'token_uri' => env('GCLOUD_LOGGING_TOKEN_URI'),
            'auth_provider_x509_cert_url' => env('GCLOUD_LOGGING_AUTH_PROVIDER_X509_CERT_URL'),
            'client_x509_cert_url' => env('GCLOUD_LOGGING_CLIENT_X509_CERT_URL'),
        ];
    }

    public function handleParams($request) {
        try {
            $data['systemName'] = !empty($request['systemName']) ? $request['systemName'] : '';
            $data['subSystem'] = !empty($request['subSystem']) ? $request['subSystem'] : '';
            $data['descricao'] = !empty($request['descricao']) ? $request['descricao'] : '';
            $data['referencias'] = !empty($request['referencias']) ? $request['referencias'] : '';
            $data['tipo'] = !empty($request['tipo']) ? $request['tipo'] : '';
            $data['mensagem'] = !empty($request['mensagem']) ? $request['mensagem'] : '';

            // Nomes originais
            $data['cod_references'] = !empty($request['cod_references']) ? $request['cod_references'] : '';
            $data['system_name'] = !empty($request['system_name']) ? $request['system_name'] : '';
            $data['sub_system'] = !empty($request['sub_system']) ? $request['sub_system'] : '';
            return $data;
        } catch (\Exception $err) {
            $err->getMessage();
            return false;
        }
    }

}
?>