<?php

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return app('path') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('registerSentry')) {
    function registerSentry($exception)
    {
        if (app()->bound('sentry')) {
            if (is_string($exception)) {
                $exception = new \Exception($errorMsg);
            }

            app('sentry')->captureException($exception);

            return true;
        }

        return false;
    }
}
if(!function_exists('NewRelicEvent')){
    function NewRelicEvent($EventName,$ArrLog)
    {

        if(!is_array($ArrLog)) {
            return false;
        }

        if(!isset($EventName) || $EventName=="") {
            return false;
        }

        if (extension_loaded('newrelic'))
        {
            @newrelic_capture_params();
            @newrelic_record_custom_event($EventName, $ArrLog);
        }

        return true;
    }
}

if (!function_exists('registerNewRelic')) {
    function registerNewRelic($exception)
    {
        if (extension_loaded('newrelic')) {
            if (is_string($exception)) {
                $exception = new \Exception($exception);
            }

            newrelic_notice_error($exception);
            return true;
        }

        return false;
    }
}

if (!function_exists('newrelic_name_transaction')) {
    function newrelic_name_transaction($exception)
    {
        if (extension_loaded('newrelic')) {
            if (is_string($exception)) {
                $exception = new \Exception($exception);
            }

            newrelic_name_transaction($exception);
            return true;
        }

        return false;
    }
}
