<?php

namespace ModulesGarden\TuringSign\Actions;

use ModulesGarden\TuringSign\Api\TlsManagerApi;
use WHMCS\Database\Capsule;

class ResendApproverEmail extends AbstractAction
{
    public function execute(): null
    {
        $order = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first();

        if(!$order || empty($order->remoteid))
        {
            return 'Order not found';
        }

        $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
        $api->resendAck($order->remoteid);

        return null;
    }
}