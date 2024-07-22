<?php
namespace App\Services\PubSub;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Illuminate\Support\Facades\Http;

class PubSubEventService extends BaseService
{
    /**
     * @var string|null
     */
    private ?string $bearer_token;

    private $url;

    protected $http;

    public function __construct()
    {
        $this->bearer_token = getenv('MS_PUBLISH_EVENT_TOKEN');
        $this->url = getenv('MS_PUBLISH_EVENT_URL');
        $this->http = Http::baseUrl($this->url);
        $this->headers();
    }


    public function enviarPayload(array $parametros)
    {

        if ( ! isset($parametros['payload'])) {
            throw new Exception('Nenhum payload informada para ser encaminhado ao pubsub');
        }

        $response = $this->http->post('/api/v1/pubsub/publish', $parametros);

        $parametros['resposta'] = $response->json();

        return $parametros;
    }

    protected function headers()
    {
        $this->http->withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
    }
}
