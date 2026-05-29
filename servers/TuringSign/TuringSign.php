<?php

if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}

require_once __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

use ModulesGarden\TuringSign\Actions\MetaData;
use ModulesGarden\TuringSign\Actions\ConfigOptions;
use ModulesGarden\TuringSign\Actions\CreateAccount;
use ModulesGarden\TuringSign\Actions\RevokeCertificate;
use ModulesGarden\TuringSign\Actions\SuspendAccount;
use ModulesGarden\TuringSign\Actions\UnsuspendAccount;
use ModulesGarden\TuringSign\Actions\TerminateAccount;
use ModulesGarden\TuringSign\Actions\ClientArea;
use ModulesGarden\TuringSign\Actions\CustomActions;
use ModulesGarden\TuringSign\Actions\Renew;
use WHMCS\Module\Server\CustomActionCollection;

function TuringSign_MetaData(): array
{
    $action = new MetaData();
    return $action->execute();
}

function TuringSign_ConfigOptions(array $params): array
{
    $action = new ConfigOptions($params);
    return $action->execute();
}

function TuringSign_CreateAccount(array $params): string
{
    try
    {
        $action = new CreateAccount($params);
        $_ = $action->execute();
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }

    return 'success';
}

function TuringSign_SuspendAccount(array $params): string
{
    try
    {
        $action = new SuspendAccount($params);
        $_ = $action->execute();
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }

    return 'success';
}

function TuringSign_UnsuspendAccount(array $params): string
{
    try
    {
        $action = new UnsuspendAccount($params);
        $_ = $action->execute();
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }

    return 'success';
}

function TuringSign_TerminateAccount(array $params): string
{
    try
    {
        $action = new TerminateAccount($params);
        $_ = $action->execute();
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }

    return 'success';
}

function TuringSign_ClientArea(array $params): array
{
    try
    {
        $action = new ClientArea($params);
        return $action->execute();
    }
    catch (Exception $e)
    {
        return [];
    }
}

function TuringSign_CustomActions(array $params): CustomActionCollection
{
    try
    {
        $action = new CustomActions($params);
        return $action->execute();
    }
    catch (Exception $e)
    {
        return new CustomActionCollection();
    }
}

function TuringSign_Renew(array $params): string
{
    try
    {
        $action = new Renew($params);
        $_ = $action->execute();
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }

    return 'success';
}

function TuringSign_AdminCustomButtonArray(array $params): array
{
    return [
        'Revoke Certificate' => 'RevokeCertificate',
        'Resend Approver Email' => 'ResendApproverEmail',
        'Refund Certificate' => 'RefundCertificate',
    ];
}

function TuringSign_ResendApproverEmail(array $params): string
{
    try
    {
        $action = new \ModulesGarden\TuringSign\Actions\ResendApproverEmail($params);
        $_ = $action->execute();
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }

    return 'success';
}

function TuringSign_RevokeCertificate(array $params): string
{
    try
    {
        $action = new RevokeCertificate($params);
        $_ = $action->execute();
    }
    catch (Exception $e)
    {
        return $e->getMessage();
    }

    return 'success';
}