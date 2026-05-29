<?php


namespace ModulesGarden\TuringSign\Actions;

use ModulesGarden\TuringSign\Api\TlsManagerApi;
use WHMCS\Database\Capsule;

class RevokeCertificate extends AbstractAction
{
    public function execute(): null
    {
        $sslOrder = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first();

        if(!$sslOrder || empty($sslOrder->remoteid))
        {
            throw new \Exception('Order not found');
        }

        $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
        $api->revokeCertificate($sslOrder->remoteid);

        return null;
    }
}