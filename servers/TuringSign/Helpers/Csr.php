<?php

namespace ModulesGarden\TuringSign\Helpers;

use phpseclib3\File\X509;

class Csr
{
    public static function getDomainsFromCsr($csrContent)
    {
        $x509 = new X509();

        $csr = $x509->loadCSR($csrContent);

        if(!$csr)
        {
            return [];
        }

        $subject = $csr['certificationRequestInfo']['subject'];

        $cn = null;

        foreach($subject['rdnSequence'] as $rdn)
        {
            foreach($rdn as $attr)
            {
                if($attr['type'] == 'id-at-commonName')
                {
                    $cn = $attr['value']['utf8String'] ?? null;
                }
            }
        }

        $sans = [];

        $attributes = $csr['certificationRequestInfo']['attributes'] ?? [];

        foreach($attributes as $attribute)
        {
            if($attribute['type'] == 'pkcs-9-at-extensionRequest')
            {
                $extValues = $attribute['value'][0];

                foreach($extValues as $extValue)
                {
                    if($extValue['extnId'] == 'id-ce-subjectAltName')
                    {
                        foreach($extValue['extnValue'] as $san)
                        {
                            foreach($san as $type => $value)
                            {
                                if($type == 'dNSName')
                                {
                                    $sans[] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        $domains = [];

        foreach($sans as $san)
        {
            if(!empty($san))
            {
                $domains[] = strtolower(trim($san));
            }
        }

        if(!empty($cn))
        {
            $domains[] = strtolower(trim($cn));
        }

        $domains = array_unique($domains);
        $domains = array_values($domains);

        return $domains;
    }
}