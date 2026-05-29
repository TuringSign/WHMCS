<?php

namespace ModulesGarden\TuringSign\Api;

use Exception;

class PleskClient
{
    protected string $baseUrl;

    public function __construct(
        string $baseUrl,
        protected string $username,
        protected string $token,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    protected function request(string $xml)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . '/enterprise/control/agent.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $headers = [
            'Content-Type: text/xml',
            'HTTP_AUTH_LOGIN: ' . $this->username,
            'HTTP_AUTH_PASSWD: ' . $this->token,
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($curl);

        if(curl_errno($curl))
        {
            throw new Exception('API error: ' . curl_error($curl));
        }

        $requestHeaders = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        $request = $requestHeaders . $xml;

        $responseHeaderSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseBody = substr($response, $responseHeaderSize);

        \logModuleCall('TuringSign', '/enterprise/control/agent.php', $request, $response, $response);

        curl_close($curl);

        libxml_use_internal_errors(true);

        $parsed = simplexml_load_string($responseBody);

        if(!$parsed)
        {
            throw new Exception('API request failed');
        }

        return $parsed;
    }
}