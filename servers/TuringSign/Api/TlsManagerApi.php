<?php

namespace ModulesGarden\TuringSign\Api;

class TlsManagerApi extends TlsManagerClient
{
    public function getOrder(string $orderCode): ?array
    {
        return $this->get("/v1/orders/{$orderCode}");
    }

    public function placeOrder(array $content): ?array
    {
        return $this->post("/v1/orders", $content);
    }

    public function replaceOrder(string $orderCode, array $content): ?array
    {
        return $this->post("/v1/orders/{$orderCode}/replace", $content);
    }

    public function renewOrder(string $orderCode, array $content): ?array
    {
        return $this->post("/v1/orders/{$orderCode}/renew", $content);
    }

    public function downloadOrderCertificate(string $orderCode): string|array|null
    {
        return $this->get("/v1/orders/{$orderCode}/certificate/download", true);
    }

    public function revokeCertificate(string $orderCode): ?array
    {
        return $this->post("/v1/orders/{$orderCode}/certificate/revoke", [
            'revocation_reason' => 4,
            'revocation_comment' => 'cancelled by customer'
        ]);
    }

    public function getAvailableProducts(): ?array
    {
        return $this->get("/v1/products");
    }

    public function getProduct($productId): ?array
    {
        return $this->get("/v1/products/{$productId}");
    }

    public function changeValidationMethod(string $orderCode, array $content)
    {
        return $this->post('/v1/orders/' . $orderCode . '/domains/changevalidation', $content);
    }

    public function resendAck(string $orderCode)
    {
        return $this->post("/v1/orders/{$orderCode}/resend-ack");
    }

    public function getSupportedCountries()
    {
        return $this->get('/v1/countries');
    }

    public function getProvinces(string $countryCode)
    {
        return $this->get("/v1/countries/{$countryCode}/provinces");
    }

    public function refund(string $orderCode)
    {
        return $this->post("/v1/orders/{$orderCode}/refund", [
            'comment' => null
        ]);
    }
}