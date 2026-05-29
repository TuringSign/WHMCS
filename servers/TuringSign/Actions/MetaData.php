<?php

namespace ModulesGarden\TuringSign\Actions;

class MetaData extends AbstractAction
{
    public function execute(): array
    {
        return [
            'DisplayName' => 'TuringSign',
            'APIVersion' => 'v1',
            'RequiresServer' => false
        ];
    }
}