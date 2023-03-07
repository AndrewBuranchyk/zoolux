<?php

namespace App\Services\ExternalApiRequests;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Setting;

class FreekassaRequests implements ExternalApiRequestsInterface
{
    public string $error;
    public Response|bool $response;
    public array $settings;
    public int $requestID;

    function __construct() {
        foreach (Setting::where('code', 'like', 'freekassa%')->get() as $item) {
            $this->settings[$item['code']] = $item['value'];
        }
    }

    /**
     * Get API token for making requests
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->settings['freekassa-api-key'] ?? '';
    }

    /**
     * Get API URL for making requests
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->settings['freekassa-api-url'] ?? '';
    }

    /**
     * Make request to Telegram
     *
     * @param array $requestParams
     * @param array $data
     * @return Response|bool
     */
    public function makeRequest(array $requestParams, array $data): Response|bool
    {
        $this->error = '';

        if (empty($requestParams['method_name'])) {
            $this->error = 'Empty method_name';
            return FALSE;
        }

        $data['shopId'] = $this->settings['freekassa-merchant-id'] ?? '';
        $nonce = microtime(true)*10000 . '000';
        $data['nonce'] = $this->requestID = (int) $nonce;

        $data['signature'] = $this->makeSignature($data);

        $url = $this->getApiUrl() . $requestParams['method_name'];

        $this->response = Http::post($url, $data);   //dd()->

        if ($this->response->failed()) {
            // Determine if the response has a 400 level status code (clientError())
            // or a 500 level status code (serverError)
            if ($this->response->clientError() || $this->response->serverError()) {
                $this->error = (!empty($this->response['message']))?
                    $this->response['message'] : $this->response->status() . ' error';
            }
        } else {
            if (($this->response['type'] ?? '') == 'error') {
                $this->error = $this->response['message'] ?? '';
            }
        }

        if (!empty($this->error)) {
            logger([
                'response_type' => $this->response['type'] ?? '',
                'response_message' => $this->error,
                'request_url' => $url,
                'request_data' => $data
            ]);
        }

        $this->makeErrorLocalization();

        return $this->response;
    }

    /**
     * Make localization for error message
     *
     */
    public function makeErrorLocalization()
    {
        if (Str::contains($this->error, 'Merchant not activated')) {
            $this->error = __('finances.merchant_not_activated');
        }
    }

    /**
     * Make payment URL
     *
     * @param array $data
     * @return string
     */
    public function makePaymentUrl(array $data): string
    {
        return ($this->settings['freekassa-form-url'] ?? '')
            . '?m=' . ($this->settings['freekassa-merchant-id'] ?? '')
            . '&currency=' . ($this->settings['freekassa-currency-id'] ?? '')
            . '&o=' . $data['merchantOrderId']
            . '&oa=' . ($data['amount'] ?? '')
            . '&lang=ru'
            . '&s=' . md5(
                ($this->settings['freekassa-merchant-id'] ?? '')
                . ':' . ($data['amount'] ?? '')
                . ':' . ($this->settings['freekassa-merchant-secret'] ?? '')
                . ':' . ($this->settings['freekassa-currency-id'] ?? '')
                . ':' . $data['merchantOrderId']
            );
    }

    /**
     * Make signature for request
     *
     * @param array $data
     * @return string
     */
    public function makeSignature(array $data): string
    {
        ksort($data);

        return hash_hmac(
            'sha256',
            implode('|', $data),
            $this->getToken()
        );
    }
}
