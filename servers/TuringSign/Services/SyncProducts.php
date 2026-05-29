<?php

namespace ModulesGarden\TuringSign\Services;

use ModulesGarden\TuringSign\Api\TlsManagerApi;
use WHMCS\Database\Capsule;

class SyncProducts
{
    protected $api = null;

    public function __construct(
        protected string $apiUrl,
        protected string $applicationId,
        protected string $secretKey,
        protected int $groupId
    )
    {
        $this->api = new TlsManagerApi($this->apiUrl, $this->applicationId, $this->secretKey);
    }

    public function sync()
    {
        $products = $this->api->getAvailableProducts();

        foreach($products['data'] as $product)
        {
            try
            {
                $this->syncProduct($product['id']);
            }
            catch(\Exception $e)
            {
                \logModuleCall('TuringSign', 'SyncProducts', 'Product ID: ' . $product['id'], $e->getMessage(), $e->getMessage());
            }
        }
    }

    public function syncProduct($productId)
    {
        $whmcsProductId = $this->getWhmcsProductId($productId);

        if($whmcsProductId)
        {
            $this->updateProduct($productId, $whmcsProductId);
        }
        else
        {
            $this->createProduct($productId);
        }
    }

    protected function getWhmcsProductId($productId)
    {
        $product = Capsule::table('tblproducts')
            ->where('servertype', '=', 'TuringSign')
            ->where('configoption4', '=', $productId)
            ->first();

        return $product ? $product->id : null;
    }

    protected function createProduct($productId)
    {
        $productInfo = $this->api->getProduct($productId);

        $addProductResult = localAPI('AddProduct', [
            'name' => $productInfo['name'],
            'gid' => $this->groupId,
            'type' => 'other',
            'paytype' => 'recurring',
            'showdomainoptions' => false,
            'module' => 'TuringSign',
            'configoption1' => $this->apiUrl,
            'configoption2' => $this->applicationId,
            'configoption3' => $this->secretKey,
            'configoption4' => $productId,
            'configoption5' => $productInfo['allow_wildcard'] ? 'on' : '',
            'configoption6' => $productInfo['validation_type']
        ]);

        if($addProductResult['result'] != 'success')
        {
            throw new \Exception('Local API: ' . $addProductResult['message']);
        }
    }

    protected function updateProduct($productId, $whmcsProductId)
    {
        $productInfo = $this->api->getProduct($productId);

        Capsule::table('tblproducts')
            ->where('id', '=', $whmcsProductId)
            ->update([
                'name' => $productInfo['name'],
                'configoption1' => $this->apiUrl,
                'configoption2' => $this->applicationId,
                'configoption3' => $this->secretKey,
                'configoption5' => $productInfo['allow_wildcard'] ? 'on' : '',
                'configoption6' => $productInfo['validation_type']
            ]);
    }
}