<?php

/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Services;

require __DIR__ . '/../../vendor/autoload.php';

use GetOpt\GetOpt;
use App\Utils\GoogleAds\ArgumentParser;
use App\Utils\GoogleAds\ArgumentNames;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsException;
use Google\Ads\GoogleAds\Util\V17\ResourceNames;
use Google\Ads\GoogleAds\V17\Enums\ConversionAdjustmentTypeEnum\ConversionAdjustmentType;
use Google\Ads\GoogleAds\V17\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V17\Services\ConversionAdjustment;
use Google\Ads\GoogleAds\V17\Services\ConversionAdjustmentResult;
use Google\Ads\GoogleAds\V17\Services\GclidDateTimePair;
use Google\Ads\GoogleAds\V17\Services\RestatementValue;
use Google\Ads\GoogleAds\V17\Services\UploadConversionAdjustmentsRequest;
use Google\ApiCore\ApiException;

/**
 * This example imports conversion adjustments for conversions that already exist.
 * To set up a conversion action, run the AddConversionAction.php example.
 */
class UploadConversionAdjustment
{
    private const ADJUSTMENT_TYPE = "RESTATEMENT";
    private const RESTATEMENT_VALUE = 0;

    public function main(
        $customerId,
        $conversionActionId,
        $gclid,
        $conversionDateTime,
        $adjustmentDateTime
    ) {
        // Either pass the required parameters for this example on the command line, or insert them
        // into the constants above.
        $options = (new ArgumentParser())->parseCommandArguments([
            ArgumentNames::CUSTOMER_ID => GetOpt::REQUIRED_ARGUMENT,
            ArgumentNames::CONVERSION_ACTION_ID => GetOpt::REQUIRED_ARGUMENT,
            ArgumentNames::GCLID => GetOpt::REQUIRED_ARGUMENT,
            ArgumentNames::ADJUSTMENT_TYPE => GetOpt::REQUIRED_ARGUMENT,
            ArgumentNames::CONVERSION_DATE_TIME => GetOpt::REQUIRED_ARGUMENT,
            ArgumentNames::ADJUSTMENT_DATE_TIME => GetOpt::REQUIRED_ARGUMENT,
            ArgumentNames::RESTATEMENT_VALUE => GetOpt::OPTIONAL_ARGUMENT
        ]);

        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile('../google_ads_php.ini')->build();

        // Construct a Google Ads client configured from a properties file and the
        // OAuth2 credentials above.
        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->fromFile('../google_ads_php.ini')
            ->withOAuth2Credential($oAuth2Credential)
            // We set this value to true to show how to use GAPIC v2 source code. You can remove the
            // below line if you wish to use the old-style source code. Note that in that case, you
            // probably need to modify some parts of the code below to make it work.
            // For more information, see
            // https://developers.devsite.corp.google.com/google-ads/api/docs/client-libs/php/gapic.
//            ->usingGapicV2Source(true)
            ->build();

        try {
            return $this->runExample(
                $googleAdsClient,
                $options[ArgumentNames::CUSTOMER_ID] ?: $customerId,
                $options[ArgumentNames::CONVERSION_ACTION_ID] ?: $conversionActionId,
                $options[ArgumentNames::GCLID] ?: $gclid,
                $options[ArgumentNames::ADJUSTMENT_TYPE] ?: self::ADJUSTMENT_TYPE,
                $options[ArgumentNames::CONVERSION_DATE_TIME] ?: $conversionDateTime,
                $options[ArgumentNames::ADJUSTMENT_DATE_TIME] ?: $adjustmentDateTime,
                $options[ArgumentNames::RESTATEMENT_VALUE] ?: self::RESTATEMENT_VALUE
            );
        } catch (GoogleAdsException $googleAdsException) {
            $errors = [];
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                /** @var GoogleAdsError $error */
                $errors[] = $error->getMessage();
                return response()->json([
                    'success' => false,
                    'msg' => "Conversion upload has failed. See Google Ads failure details below.",
                    'errors' => $errors
                ]);
            }
        } catch (ApiException $apiException) {
            return response()->json([
                'success' => false,
                'msg' => "Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
                'errors' => $apiException->getMessage()
            ]);
        }
    }

    /**
     * Runs the example.
     *
     * @param GoogleAdsClient $googleAdsClient the Google Ads API client
     * @param int $customerId the customer ID
     * @param int $conversionActionId the ID of the conversion action to upload adjustment to
     * @param string $gclid the GCLID for the conversion
     * @param string $adjustmentType the type of adjustment, e.g. RETRACTION, RESTATEMENT
     * @param string $conversionDateTime the date and time of the conversion.
     *      The format is "yyyy-mm-dd hh:mm:ss+|-hh:mm", e.g. “2019-01-01 12:32:45-08:00”
     * @param string $adjustmentDateTime the date and time of the adjustment.
     *      The format is "yyyy-mm-dd hh:mm:ss+|-hh:mm", e.g. “2019-01-01 12:32:45-08:00”
     * @param float|null $restatementValue the adjusted value for adjustment type RESTATEMENT
     */
    // [START upload_conversion_adjustment]
    public function runExample(
        GoogleAdsClient $googleAdsClient,
        int $customerId,
        int $conversionActionId,
        string $gclid,
        string $adjustmentType,
        string $conversionDateTime,
        string $adjustmentDateTime,
        float $restatementValue
    ) {
        $conversionAdjustmentType = ConversionAdjustmentType::value($adjustmentType);

        // Associates conversion adjustments with the existing conversion action.
        // The GCLID should have been uploaded before with a conversion.
        $conversionAdjustment = new ConversionAdjustment([
            'conversion_action' =>
                ResourceNames::forConversionAction($customerId, $conversionActionId),
            'adjustment_type' => $conversionAdjustmentType,
            'gclid_date_time_pair' => new GclidDateTimePair([
                'gclid' => $gclid,
                'conversion_date_time' => $conversionDateTime
            ]),
            'adjustment_date_time' => $adjustmentDateTime
        ]);

        // Sets adjusted value for adjustment type RESTATEMENT.
        if (
            $restatementValue !== null
            && $conversionAdjustmentType === ConversionAdjustmentType::RESTATEMENT
        ) {
            $conversionAdjustment->setRestatementValue(new RestatementValue([
                'adjusted_value' => $restatementValue
            ]));
        }

        // Issues a request to upload the conversion adjustment.
        $conversionAdjustmentUploadServiceClient =
            $googleAdsClient->getConversionAdjustmentUploadServiceClient();
        $response = $conversionAdjustmentUploadServiceClient->uploadConversionAdjustments(
        // Enables partial failure (must be true).
            UploadConversionAdjustmentsRequest::build($customerId, [$conversionAdjustment], true)
        );

        // Prints the status message if any partial failure error is returned.
        // Note: The details of each partial failure error are not printed here, you can refer to
        // the example HandlePartialFailure.php to learn more.
        if ($response->hasPartialFailureError()) {

            return response()->json([
                'success' => false,
                'msg' => "Partial failures occurred",
                'errors' => $response->getPartialFailureError()->getMessage()
            ]);
        } else {
            // Prints the result if exists.
            /** @var ConversionAdjustmentResult $uploadedConversionAdjustment */
            $uploadedConversionAdjustment = $response->getResults()[0];

            $message = printf(
                "Uploaded conversion adjustment of '%s' for Google Click ID '%s'.",
                $uploadedConversionAdjustment->getConversionAction(),
                $uploadedConversionAdjustment->getGclidDateTimePair()->getGclid(),
            );

            return response()->json([
                'success' => true,
                'msg' => $message,
            ]);
        }
       return $response;
    }
    // [END upload_conversion_adjustment]
}
