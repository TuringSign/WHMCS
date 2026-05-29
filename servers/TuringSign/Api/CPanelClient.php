<?php

namespace ModulesGarden\TuringSign\Api;

use Exception;
use ModulesGarden\TuringSign\Exceptions\UserVisibleException;

class CPanelClient
{
    protected string $baseUrl;

    public function __construct(
        string $baseUrl,
        protected string $username,
        protected string $token,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    protected function request(string $method, string $endpoint, ?array $content = null): array|string|null
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ];

        $headers[] = 'Authorization: whm ' . $this->username . ':' . $this->token;

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if($content)
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($content));
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

        if(in_array($httpCode, $safeHttpCodes) && is_array($decodedResponse) && $decodedResponse['status'] == 0 && !empty($decodedResponse['statusmsg']))
        {
            throw new UserVisibleException($decodedResponse['statusmsg']);
        }

        if($httpCode < 200 || $httpCode >= 300)
        {
            throw new Exception('API request failed');
        }

        return $decodedResponse;
    }

    protected function get(string $endpoint): array|string|null
    {
        return $this->request('GET', $endpoint);
    }

    protected function post(string $endpoint, ?array $content = null): array|string|null
    {
        return $this->request('POST', $endpoint, $content);
    }
}