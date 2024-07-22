<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UploadConversionAdjustment;
class GclidController extends Controller
{
    public function adjustGclid(
        Request $request,
        UploadConversionAdjustment $uploadConversionAdjustment
    )
    {
        return $uploadConversionAdjustment->main(
            $request->customerId,
            $request->conversionActionId,
            $request->gclid,
            $request->conversionDateTime,
            $request->adustmentDateTime
        );
    }
}
