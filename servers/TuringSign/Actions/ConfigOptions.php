<?php

namespace ModulesGarden\TuringSign\Actions;

use WHMCS\Database\Capsule;

class ConfigOptions extends AbstractAction
{
    public function execute(): array
    {
        return [
            'API URL' => [
                'Type' => 'text',
                'Size' => '100'
            ],
            'Application ID' => [
                'Type' => 'text',
                'Size' => '100'
            ],
            'Secret Key' => [
                'Type' => 'password',
                'Size' => '100'
            ],
            'TuringSign Product ID' => [
                'Type' => 'text',
                'Size' => '100'
            ],
            'Allow Wildcard' => [
                'Type' => 'yesno',
            ],
            'Validation Type' => [
                'Type' => 'dropdown',
                'Options' => [
                    'DV' => 'DV',
                    'OV' => 'OV',
                    'EV' => 'EV'
                ]
            ]
        ];
    }
}