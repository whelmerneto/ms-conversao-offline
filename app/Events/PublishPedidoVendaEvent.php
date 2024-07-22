<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class PublishPedidoVendaEvent extends Event
{

    use SerializesModels;

    public $payload;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }
}
