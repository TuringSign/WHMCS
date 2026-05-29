<?php

namespace ModulesGarden\TuringSign\Api;

use Exception;
use ModulesGarden\TuringSign\Exceptions\UserVisibleException;

class TlsManagerClient
{
    protected string $baseUrl;
    protected ?string $accessToken = null;

    public function __construct(
            string $baseUrl,
        protected string $appId,
        protected string $secretKey,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    protected function request(string $method, string $endpoint, ?array $content = null, bool $requiresAccessToken = true, bool $raw = false): array|string|null
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        $headers = $raw ? [
            'Accept: application/json',
        ] : [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($requiresAccessToken)
        {
            $this->obtainAccessToken();

            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if($content)
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($content));
        }

        $response = curl_exec($curl);

        if(curl_errno($curl))
        {
            throw new Exception('API error: ' . curl_error($curl));
        }

        $requestHeaders = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        $request = $requestHeaders . ($content ? json_encode($content, JSON_PRETTY_PRINT) : '');

        $responseHeaderSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseBody = substr($response, $responseHeaderSize);

        \logModuleCall('TuringSign', $endpoint, $request, $response, $response);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $safeHttpCodes = [
            200,
            400,
            404,
            409,
            410,
            422
        ];

        $decodedResponse = json_decode($responseBody, true);

        if(in_array($httpCode, $safeHttpCodes) && is_array($decodedResponse) && $decodedResponse['response_status'] == 'failed')
        {
            if(!empty($decodedResponse['detail']))
            {
                throw new UserVisibleException($decodedResponse['detail']);
            }

            if(!empty($decodedResponse['errors']) && is_array($decodedResponse['errors']))
            {
                $errors = array_values($decodedResponse['errors'])[0];

                if(is_array($errors) && !empty($errors[0]))
                {
                    throw new UserVisibleException($errors[0]);
                }
            }
        }


        if($httpCode < 200 || $httpCode >= 300)
        {
            throw new Exception('API request failed');
        }

        if ($raw)
        {
            return $responseBody;
        }

        if(!$decodedResponse)
        {
            throw new Exception('API request failed');
        }

        return $decodedResponse;
    }

    protected function obtainAccessToken(): void
    {
        if($this->accessToken)
        {
            return;
        }

        $response = $this->request('POST', '/v1/auth/login', [
            'app_id' => $this->appId,
            'secret' => $this->secretKey,
        ], false);

        $this->accessToken = $response['access_token'];
    }

    protected function get(string $endpoint, bool $raw = false): array|string|null
    {
        return $this->request('GET', $endpoint, null, true, $raw);
    }

    protected function post(string $endpoint, ?array $content = null, bool $raw = false): array|string|null
    {
        return $this->request('POST', $endpoint, $content, true, $raw);
    }
}