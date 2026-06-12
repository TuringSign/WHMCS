<?php

namespace ModulesGarden\TuringSign\Actions;

use ModulesGarden\TuringSign\Api\TlsManagerApi;
use WHMCS\Database\Capsule;
use Exception;

class CreateAccount extends AbstractAction
{
    public function execute(): string
    {
        if($this->certificateAlreadyExists())
        {
            throw new Exception('Certificate already exists');
        }

        $this->deleteSslOrder();
        $sslOrderId = $this->createSslOrderGetId();

        $this->sendEmail($sslOrderId);

        return '';
    }

    protected function certificateAlreadyExists(): bool
    {
        $sslOrder = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first([
                'remoteid'
            ]);

        return !empty($sslOrder?->remoteid);
    }

    protected function deleteSslOrder(): void
    {
        Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->delete();
    }

    protected function createSslOrderGetId(): int
    {
        return Capsule::table('tblsslorders')
            ->insertGetId([
                'userid' => $this->params['userid'],
                'serviceid' => $this->params['serviceid'],
                'module' => 'TuringSign',
                'status' => 'Awaiting Configuration'
            ]);
    }

    protected function sendEmail(int $sslOrderId): void
    {
        $sslConfigurationLink = $this->getSslConfigurationLink($sslOrderId);

        localAPI('SendEmail', [
            'messagename' => 'TuringSign Send Configuration Link',
            'id' => $this->params['serviceid'],
            'customvars' => base64_encode(serialize([
                'sslConfigurationLink' => $sslConfigurationLink
            ]))
        ]);
    }

    protected function getSslConfigurationLink(int $sslOrderId): string
    {
        $systemUrl = $this->getSystemUrl();

        return $systemUrl . '/clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=stepOne';
    }

    protected function getSystemUrl(): string
    {
        $systemUrlConfiguration = Capsule::table('tblconfiguration')
            ->where('setting', '=', 'SystemURL')
            ->first([
                'value'
            ]);

        return rtrim($systemUrlConfiguration->value, '/');
    }
}