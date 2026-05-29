<?php

namespace ModulesGarden\TuringSign\Actions;

use ModulesGarden\TuringSign\Api\TlsManagerApi;
use ModulesGarden\TuringSign\Helpers\Lang;
use WHMCS\Module\Server\CustomAction;
use WHMCS\Module\Server\CustomActionCollection;
use WHMCS\Database\Capsule;

class CustomActions extends AbstractAction
{
    protected ?int $orderId = null;
    protected ?string $orderStatus = null;

    protected ?string $remoteId = null;
    protected ?string $certificateStatus = null;

    public function execute(): CustomActionCollection
    {
        $sslOrder = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first([
                'id',
                'status',
                'remoteid'
            ]);

        if($sslOrder)
        {
            $this->orderId = $sslOrder->id;
            $this->orderStatus = $sslOrder->status;
            $this->remoteId = $sslOrder->remoteid;
        }

        if($sslOrder && $sslOrder->remoteid)
        {
            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);

            $resultGetOrder = $api->getOrder($sslOrder->remoteid);

            $this->certificateStatus = $resultGetOrder['order_status'];
        }

        $lang = new Lang();

        $customActionCollection = new CustomActionCollection();

        if($this->orderStatus === "Awaiting Configuration" && $this->params['status'] == "Active" && empty($this->remoteId))
        {
            $customActionCollection->add($this->getConfigureAction($lang->get('configureNow')));
        }

        if($this->orderStatus === "Awaiting Renew" && $this->params['status'] == "Active" && !empty($this->remoteId))
        {
            $customActionCollection->add($this->getConfigureAction($lang->get('renewNow')));
        }

        if(!empty($this->remoteId) && $this->orderStatus != 'Awaiting Renew')
        {
            $customActionCollection->add($this->getShowCertificateAction($lang->get('showCertificate')));
            $customActionCollection->add($this->getDownloadCertificateAction($lang->get('downloadCertificate')));
            $customActionCollection->add($this->getRevokeCertificate($lang->get('revokeCertificate')));
            $customActionCollection->add($this->getReplaceCertificate($lang->get('reissueCertificate')));
        }

        return $customActionCollection;
    }

    protected function getConfigureAction($label): CustomAction
    {
        return CustomAction::factory(
            'TuringSign_Configure',
            $label,
            function (): array {
                return [
                    'success' => true,
                    'redirectTo' => 'clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=stepOne',
                ];
            },
            [],
            [],
            ($this->orderStatus === "Awaiting Configuration" && $this->params['status'] == "Active" && empty($this->remoteId)) || ($this->orderStatus === "Awaiting Renew" && $this->params['status'] == "Active" && !empty($this->remoteId)),
            true
        );
    }

    protected function getDownloadCertificateAction($label): CustomAction
    {
        return CustomAction::factory(
            'TuringSign_DownloadCertificate',
            $label,
            function (): array {
                return [
                    'success' => true,
                    'redirectTo' => 'clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=downloadCertificate',
                ];
            },
            [],
            [],
            $this->certificateStatus === "ISSUED" && $this->params['status'] == "Active",
            false
        );
    }

    protected function getShowCertificateAction($label): CustomAction
    {
        return CustomAction::factory(
            'TuringSign_ShowCertificate',
            $label,
            function (): array {
                return [
                    'success' => true,
                    'redirectTo' => 'clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=showCertificate',
                ];
            },
            [],
            [],
            $this->certificateStatus === "ISSUED" && $this->params['status'] == "Active",
            true
        );
    }

    protected function getRevokeCertificate($label): CustomAction
    {
        return CustomAction::factory(
            'TuringSign_RevokeCertificate',
            $label,
            function (): array {
                return [
                    'success' => true
                ];
            },
            [],
            [],
            $this->certificateStatus === "ISSUED" && $this->params['status'] == "Active",
            true
        );
    }

    protected function getReplaceCertificate($label): CustomAction
    {
        return CustomAction::factory(
            'TuringSign_ReplaceCertificate',
            $label,
            function (): array {
                return [
                    'success' => true,
                ];
            },
            [],
            [],
            $this->certificateStatus === "ISSUED" && $this->params['status'] == "Active",
            true
        );
    }
}