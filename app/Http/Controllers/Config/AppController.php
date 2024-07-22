<?php

namespace App\Http\Controllers\Config;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AppService as AppService;

class AppController
{
   /**
     * @param AppService $service
     * @return string
     */
    public function getAppInfo(AppService $service, Request $req)
    {
       return $service->getAppInfo($req);
    }
}
