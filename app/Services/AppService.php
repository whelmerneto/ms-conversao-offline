<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Classes\OB_Api;

class AppService
{
    private $app;
    private $secretAppKey;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->secretAppKey = base64_encode(date("Ymd") . "b69cc4b49d36aacf45167df3eb116979");
    }

    public function getAppInfo(Request $req)
    {
        $appVersion = "15-08-2023 1.0.0";
        $frameworkVersion = '';

        if (!empty($req->token) && $this->secretAppKey === $req->token) {
            $frameworkVersion = "<br />" . $this->app->version();
        }

        return "MS Conversao Offline API - v " . $appVersion . $frameworkVersion;
    }
}
