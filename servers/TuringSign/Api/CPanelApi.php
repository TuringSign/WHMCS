<?php

namespace ModulesGarden\TuringSign\Api;

class CPanelApi extends CPanelClient
{
    public function installSsl(string $domain, string $cert, string $key): ?array
    {
        return $this->post("/json-api/installssl", [
            'domain' => $domain,
            'cert' => $cert,
            'key' => $key,
        ]);
    }
}