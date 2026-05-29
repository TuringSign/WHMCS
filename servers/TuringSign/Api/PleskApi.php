<?php

namespace ModulesGarden\TuringSign\Api;

class PleskApi extends PleskClient
{
    public function createCertificate($domain, $certName, $cert, $key, $ca)
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<packet>
    <certificate>
        <install>
            <name>{$certName}</name>
            <webspace>{$domain}</webspace>
            <content>
                <csr></csr>
                <pvt><![CDATA[{$key}]]></pvt>
                <cert><![CDATA[{$cert}]]></cert>
                <ca><![CDATA[{$ca}]]></ca>
            </content>
        </install>
    </certificate>
</packet>
XML;

        return $this->request($xml);
    }

    public function enableSslOnDomain($domain, $certName)
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<packet>
    <site>
        <set>
            <filter>
                <name>{$domain}</name>
            </filter>
            <values>
                <hosting>
                    <vrt_hst>
                        <property>
                            <name>ssl</name>
                            <value>true</value>
                        </property>
                        <property>
                            <name>certificate_name</name>
                            <value>{$certName}</value>
                        </property>
                    </vrt_hst>
                </hosting>
            </values>
        </set>
    </site>
</packet>
XML;

        return $this->request($xml);
    }
}