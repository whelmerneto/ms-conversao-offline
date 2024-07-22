<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leads extends Model
{

    protected $connection = 'sku';
    protected $table = 'sku.tb_leads';
    public $timestamps = false;

    protected $fillable = [
        'sender',
        'pessoa_id',
        'pessoa_nome',
        'pessoa_cpf',
        'pessoa_email',
        'pessoa_telefone',
        'veiculo_id',
        'lead_titulo',
        'lead_mensagem',
        'lead_portal',
        'lead_canal',
        'lead_financiar',
        'lead_seminovos_opt_int',
        'gravar',
        'response',
        'status',
        'anotations',
        'pedido_id',
        'anotations',
        'dtcadastro',
        'dtretirada',
        'gclid_value',
        'gclid_expiration'
    ];

    protected $casts = [
//      'anotations' => 'json'
    ];

    protected $dates = [
      'dtcadastro'
    ];

}
