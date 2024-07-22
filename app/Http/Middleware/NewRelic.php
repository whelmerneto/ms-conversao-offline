<?php

namespace App\Http\Middleware;

use Closure;

class Newrelic
{
    public function handle($request, Closure $next)
    {
        $uri = $request->getPathInfo();
        $param = $request->getMethod() === 'GET';

        $this->newRelicTransaction($uri,$param,$request);

        return $next($request);
    }

    private function newRelicTransaction(string $uri, bool $param = false, $request = []): void
    {
        if (extension_loaded('newrelic')) {
            try {
                @newrelic_start_transaction(ini_get("newrelic.appname")); // start recording a new transaction
                newrelic_name_transaction($uri);

                newrelic_capture_params($param);
                $save_collumns = [
                    #   Inserir colunas com nome das propriedades dos payloads
                    "PedidoID",
                    "LeadID",
                    "AvaliacaoID",
                    "DataPedido",
                    "StatusID",
                    "PessoaID",
                    "FrotaID",
                    "CNPJ",
                    "RazaoSocial",
                    "Celular",
                    "Email",
                    "sender",
                    "pessoa_id",
                    "pessoa_nome",
                    "pessoa_cpf",
                    "pessoa_email",
                    "pessoa_telefone",
                    "veiculo_id",
                    "lead_titulo",
                    "lead_mensagem",
                    "lead_portal",
                    "lead_canal",
                    "lead_financiar",
                    "lead_seminovos_opt_int",
                    "gravar",
                    "response",
                    "dtcadastro",
                    "status",
                    "anotations",
                    "pedido_id",
                    "dtretirada",
                    "gclid_value",
                    "gclid_expiration"
                ];

                $payload = $request->all();
                if (!empty($payload)) {
                    foreach ($payload as $coluna => $valor) {
                        if (is_array($valor)) {
                            foreach ($valor as $parametro => $parametro_value) {
                                array_push($save_collumns, $parametro_value);
                            }
                        }
                        if (in_array($coluna, $save_collumns)) {
                            newrelic_add_custom_parameter($coluna, $valor);
                        }
                    }
                } 

                newrelic_add_custom_parameter('IP_Address', $_SERVER['REMOTE_ADDR']);

            } catch (\Exception $e) {
                // echo '<script> console.log("Exception: $e->getMessage()")</script>';
                // return;
            }
        }
    }
}