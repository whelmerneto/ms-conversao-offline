<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => '/'], function () use ($router) {
    $router->get('/', 'Config\AppController@getAppInfo');
});

$router->group(['prefix' => 'api/v1', "middleware" => "auth"], function () use ($router) {
    $router->group(['prefix' => '/pedido', "middleware" => "auth"], function () use ($router) {
        $router->post('/publish', 'PublishPedidoVendaController');
        $router->post('/listen',  'ListenPedidoVendaController');
    });

    $router->group(['prefix' => '/gclid', "middleware" => "auth"], function () use ($router) {
        $router->post('/listen',  'ListenGclidController');
    });

    $router->group(['prefix' => '/gclid', "middleware" => "auth"], function () use ($router) {
        $router->post('/adjust',  'GclidController@adjustGclid');
    });
});
