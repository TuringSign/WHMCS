<?php

namespace ModulesGarden\TuringSign\Actions;

use WHMCS\Database\Capsule;

class TerminateAccount extends AbstractAction
{
    public function execute(): string
    {
        Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->update([
                'status' => 'Cancelled'
            ]);

        return '';
    }
}