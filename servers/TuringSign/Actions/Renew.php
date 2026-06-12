<?php

namespace ModulesGarden\TuringSign\Actions;

use WHMCS\Database\Capsule;

class Renew extends AbstractAction
{
    public function execute(): string
    {
        $order = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first();

        if(!$order)
        {
            return 'Order not found';
        }

        Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->update([
                'remoteid' => $order->remoteid,
                'completiondate' => '',
                'status' => 'Awaiting Renew'
            ]);

        $this->sendEmail($order->id);

        return '';
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