<?php

use ModulesGarden\TuringSign\Services\SyncProducts;

require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "init.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

$apiUrl = $argv[1];
$applicationId = $argv[2];
$secretKey = $argv[3];
$groupId = $argv[4];

if(empty($apiUrl) || empty($applicationId) || empty($secretKey) || empty($groupId))
{
    echo "Usage: php syncProducts.php <apiUrl> <applicationId> <secretKey> <groupId>\n";
    exit();
}

try
{
    $syncProducts = new SyncProducts($apiUrl, $applicationId, $secretKey, $groupId);
    $syncProducts->sync();
}
catch(Exception $e)
{
    \logModuleCall('TuringSign', 'SyncProducts', '', $e->getMessage(), $e->getMessage());
    echo "Exception: ", $e->getMessage(), "\n";
    exit();
}