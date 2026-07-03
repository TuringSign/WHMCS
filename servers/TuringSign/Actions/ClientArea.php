<?php

namespace ModulesGarden\TuringSign\Actions;

use ModulesGarden\TuringSign\Api\CPanelApi;
use ModulesGarden\TuringSign\Api\PleskApi;
use ModulesGarden\TuringSign\Api\TlsManagerApi;
use ModulesGarden\TuringSign\Exceptions\UserVisibleException;
use ModulesGarden\TuringSign\Helpers\Csr;
use ModulesGarden\TuringSign\Helpers\Lang;
use ModulesGarden\TuringSign\Helpers\PublicSuffixHelper;
use phpseclib3\File\X509;
use WHMCS\CustomField\CustomFieldValue;
use WHMCS\Database\Capsule;
use ZipArchive;

class ClientArea extends AbstractAction
{
    public function execute(): array
    {
        if($this->params['status'] == "Completed")
        {
            Capsule::table('tblhosting')
                ->where('id', '=', $this->params['serviceid'])
                ->update([
                    'domainstatus' => 'Active'
                ]);

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }

        $action = $_GET['ts-action'];

        switch($action)
        {
            case 'downloadCertificate':
                return $this->downloadCertificate();
            case 'stepThree':
                return $this->stepThree();
            case 'stepTwo':
                return $this->stepTwo();
            case 'stepOne':
                return $this->stepOne();
            case 'revokeCertificate':
                return $this->revokeCertificate();
            case 'replaceCertificate':
                return $this->replaceCertificate();
            case 'installCertificate':
                return $this->installCertificate();
            case 'showCertificate':
                return $this->showCertificate();
            case 'sendCertificate':
                return $this->sendCertificate();
            case 'changeValidationMethod':
                return $this->changeValidationMethod();
            case 'getProvinces':
                return $this->getProvinces();
        }

        return $this->index();
    }

    protected function index(): array
    {
        $certificateStatus = null;
        $primaryDomain = null;
        $subjectAlternativeNames = null;
        $certificateType = null;
        $validFrom = null;
        $validTo = null;
        $orderId = null;

        $servicesArray = [];
        $validationInfo = [];

        $sslOrder = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first([
                'id',
                'status',
                'remoteid'
            ]);

        if($sslOrder && $sslOrder->remoteid)
        {
            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);

            $resultGetOrder = $api->getOrder($sslOrder->remoteid);

            $certificateStatus = strtoupper($resultGetOrder['order_status']);
            $primaryDomain = $resultGetOrder['order_certificate_info']['common_name'];
            $subjectAlternativeNames = implode(', ', $resultGetOrder['order_certificate_info']['sans']['dns']);
            $certificateType = $resultGetOrder['ssl_type'];
            $validFrom = $resultGetOrder['order_certificate_info']['valid_from'];
            $validTo = $resultGetOrder['order_certificate_info']['valid_to'];
            $orderId = $resultGetOrder['order_code'];

            $validationInfo = $resultGetOrder['domain_validation_info'] ?? [];

            foreach($validationInfo as $key => $info)
            {
                if($info['status'] != 'requested' && $info['status'] != 'not_requested')
                {
                    unset($validationInfo[$key]);
                }

                if (in_array('*.' . $info['domain_name'], $resultGetOrder['order_certificate_info']['sans']['dns']))
                {
                    $validationInfo[$key]['is_wildcard'] = true;
                }
            }

            if($certificateStatus != 'PENDING')
            {
                $validationInfo = [];
            }

        }

        if($certificateStatus === "ISSUED")
        {
            $services = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
                ->where('tblhosting.domainstatus', '=', 'Active')
                ->where(function ($query) {
                    $query->where('tblproducts.servertype', '=', 'cpanel')
                        ->orWhere('tblproducts.servertype', '=', 'plesk');
                })
                ->where('tblhosting.userid', '=', $this->params['userid'])
                ->get([
                    'tblhosting.id',
                    'tblproducts.name',
                    'tblhosting.domain'
                ]);

            $servicesArray = [];

            foreach ($services as $service)
            {
                $servicesArray[$service->id] = $service->name . ' - ' . $service->domain;
            }
        }

        $alertMessage = $_SESSION['TuringSignMessage'] ?? null;
        unset($_SESSION['TuringSignMessage']);

        $lang = new Lang();

        return [
            'tabOverviewModuleOutputTemplate' => 'templates/client/index',
            'templateVariables' => [
                'certificateStatus' => $certificateStatus,
                'servicesArray' => $servicesArray,
                'validationInfo' => $validationInfo,
                'alertMessage' => $alertMessage,
                'primaryDomain' => $primaryDomain,
                'subjectAlternativeNames' => $subjectAlternativeNames,
                'certificateType' => $certificateType,
                'validFrom' => $validFrom,
                'validTo' => $validTo,
                'orderId' => $orderId,
                'tsLang' => $lang,
                'domain' => $this->params['customfields']['domain']
            ]
        ];
    }

    protected function getProvinces()
    {
        try
        {
            $countryCode = $_POST['country'];

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
            $resultGetOrder = $api->getProvinces($countryCode);

            $provinces = [];

            foreach($resultGetOrder['data'] as $province)
            {
                $provinces[] = $province['name'];
            }

            echo json_encode([
                'status' => 'success',
                'provinces' => $provinces
            ]);
            exit();
        }
        catch (\Exception)
        {
            echo json_encode([
                'status' => 'error'
            ]);
            exit();
        }
    }

    protected function downloadCertificate()
    {
        try
        {
            $sslOrderRemoteId = $this->getSslOrderRemoteId();

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
            $resultGetOrder = $api->downloadOrderCertificate($sslOrderRemoteId);

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $sslOrderRemoteId . '.zip"');
            header('Content-Length: ' . mb_strlen($resultGetOrder, '8bit'));

            echo $resultGetOrder;
            exit();
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(\Exception $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'downloadCertificateError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
    }

    protected function sendCertificate()
    {
        try
        {
            $sslOrderRemoteId = $this->getSslOrderRemoteId();

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
            $resultGetOrder = $api->downloadOrderCertificate($sslOrderRemoteId);

            $email = $_POST['email'];

            $template = \WHMCS\Mail\Template::where('name', '=', 'TuringSign Send Certificate')->first();

            if(!$template)
            {
                throw new \Exception('Template not found');
            }

            $message = new \WHMCS\Mail\Message();

            $message->setSubject($template->subject);
            $message->setBodyAndPlainText($template->message);

            $message->addStringAttachment($sslOrderRemoteId . ".zip", $resultGetOrder);

            $emailer = \WHMCS\Mail\Emailer::factory($message, 1, []);
            $message = $emailer->preview();

            $message->clearRecipients('to');
            $message->addRecipient('to', $email);

            \WHMCS\Module\Mail::factory()->send($message);

            $_SESSION['TuringSignMessage'] = [
                'type' => 'success',
                'message' => 'sendCertificateSuccess'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(\Exception $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'sendCertificateError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
    }

    protected function stepOne(): array
    {
        try
        {
            global $ca;

            $sslOrder = Capsule::table('tblsslorders')
                ->where('serviceid', '=', $this->params['serviceid'])
                ->first();

            if(!$sslOrder || ($sslOrder->status != "Awaiting Configuration" && ($sslOrder->status != 'Awaiting Renew' || empty($sslOrder->remoteid))))
            {
                header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
                exit();
            }

            $awaitingRenew = $sslOrder->status == 'Awaiting Renew';

            $ca->addToBreadcrumb('#', 'Configure SSL Certificate');

            $countriesArray = [];

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
            $countries = $api->getSupportedCountries();

            foreach($countries['data'] as $country)
            {
                $countriesArray[$country['iso_code2']] = $country['name'];
            }

            $alertMessage = $_SESSION['TuringSignMessage'] ?? null;
            unset($_SESSION['TuringSignMessage']);

            $lang = new Lang();

            $productDv = $this->params['configoption6'] == "DV";

            $issetSubscriber = !empty($this->getSubscriberId()) && !empty($this->getContactId());
            $issetBusinessSubscriber = !empty($this->getBusinessSubscriberId()) && !empty($this->getBusinessContactId());

            $showSubscriberForm = true;

            if ($issetBusinessSubscriber)
            {
                $showSubscriberForm = false;
            }
            else if ($productDv && $issetSubscriber)
            {
                $showSubscriberForm = false;
            }

            return [
                'tabOverviewReplacementTemplate' => 'templates/client/stepOne',
                'templateVariables' => [
                    'countries' => $countriesArray,
                    'showSubscriberForm' => $showSubscriberForm,
                    'tsLang' => $lang,
                    'alertMessage' => $alertMessage,
                    'awaitingRenew' => $awaitingRenew,
                    'clientsDetails' => [
                        'firstName' => $this->params['clientsdetails']['firstname'],
                        'lastName' => $this->params['clientsdetails']['lastname'],
                        'email' => $this->params['clientsdetails']['email'],
                        'phoneNumber' => $this->params['clientsdetails']['phonenumber'],
                        'companyName' => $this->params['clientsdetails']['companyname'],
                        'country' => $this->params['clientsdetails']['country'],
                        'address' => trim($this->params['clientsdetails']['address1'] . " " . $this->params['clientsdetails']['address2']),
                        'postalCode' => $this->params['clientsdetails']['postcode'],
                        'locality' => $this->params['clientsdetails']['city'],
                        'province' => $this->params['clientsdetails']['fullstate'],
                    ],
                    'productDv' => $productDv
                ]
            ];
        }
        catch(\Exception $e)
        {
            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
    }

    protected function stepTwo(): array
    {
        try
        {
            global $ca;

            $sslOrder = Capsule::table('tblsslorders')
                ->where('serviceid', '=', $this->params['serviceid'])
                ->first();

            if(!$sslOrder)
            {
                header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
                exit();
            }

            if($sslOrder->status != 'Awaiting Configuration' && empty($sslOrder->remoteid))
            {
                header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
                exit();
            }

            $ca->addToBreadcrumb('#', 'Configure SSL Certificate');

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);

            $remoteId = null;

            if($sslOrder->status == "Awaiting Configuration" && empty($sslOrder->remoteid))
            {
                $csrContent = $_POST['csr'];

                $domains = Csr::getDomainsFromCsr($csrContent);

                $dnsNames = [];

                foreach ($domains as $domain)
                {
                    $d = strtolower(trim($domain));

                    if ($d === '')
                    {
                        continue;
                    }

                    $dnsNames[] = $d;

                    if (str_starts_with($d, '*.'))
                    {
                        $apex = substr($d, 2);
                        $dnsNames[] = $apex;
                        continue;
                    }

                    if (str_starts_with($d, 'www.'))
                    {
                        $apex = substr($d, 4);

                        if(PublicSuffixHelper::isSubdomain($apex))//check if $apex is not subdomain
                        {
                            continue;
                        }

                        $dnsNames[] = $apex;
                    }
                    elseif(!PublicSuffixHelper::isSubdomain($d))
                    {
                        //add www only if $d is not subdomain
                        $dnsNames[] = 'www.' . $d;
                    }
                }

                $dnsNames = array_values(array_unique($dnsNames));

                $standardDomains = $this->params['configoptions']['standardDomains'];
                $wildcardDomains = $this->params['configoptions']['wildcardDomains'];

                $uniqueDomains = [];
                $standardDomainsCount = 0;
                $wildcardDomainsCount = 0;

                foreach($dnsNames as $dnsName)
                {
                    $d = strtolower(trim($dnsName));

                    if($d === '')
                    {
                        continue;
                    }

                    $isWildcard = false;

                    if(str_starts_with($d, '*.'))
                    {
                        $isWildcard = true;
                        $d = substr($d, 2);
                    } else if (str_starts_with($d, 'www.'))
                    {
                        $d = substr($d, 4);
                    }

                    if(isset($uniqueDomains[$d]))
                    {
                        if ($isWildcard)
                        {
                            $wildcardDomainsCount++;
                            $standardDomainsCount--;
                        }

                        continue;
                    }

                    $uniqueDomains[$d] = true;

                    if($isWildcard)
                    {
                        $wildcardDomainsCount++;
                    }
                    else
                    {
                        $standardDomainsCount++;
                    }
                }

                if($wildcardDomains !== null && ((int) $wildcardDomains) > 0 && $wildcardDomainsCount == 0)
                {
                    throw new UserVisibleException('csrMissingWildcard');
                }

                if($standardDomains !== null && $standardDomainsCount > (int) $standardDomains)
                {
                    throw new UserVisibleException('sslStandardDomainsLimitExceeded');
                }

                if($wildcardDomains !== null && $wildcardDomainsCount > (int) $wildcardDomains)
                {
                    throw new UserVisibleException('sslWildcardDomainsLimitExceeded');
                }

                $lengthInDays = match($this->params['model']->billingcycle) {
                    'Biennially' => 365 * 2,
                    'Triennially' => 365 * 3,
                    default => 365
                };

                $productDv = $this->params['configoption6'] == "DV";

                $subscriberId = $this->getSubscriberId();
                $contactId = $this->getContactId();

                $businessSubscriberId = $this->getBusinessSubscriberId();
                $businessContactId = $this->getBusinessContactId();

                $subscriber = [];
                $business = false;

                if (!empty($businessSubscriberId) && !empty($businessContactId))
                {
                    $business = true;

                    $subscriber = [
                        'existing_subscriber_id' => $businessSubscriberId,
                        'contact' => [
                            'existing_contact_id' => $businessContactId,
                        ]
                    ];
                }
                else if ($productDv && !empty($subscriberId) && !empty($contactId))
                {
                    $business = false;

                    $subscriber = [
                        'existing_subscriber_id' => $subscriberId,
                        'contact' => [
                            'existing_contact_id' => $contactId,
                        ]
                    ];
                }
                else if ($productDv)
                {
                    $business = false;

                    $firstName = $_POST['firstName'];
                    $lastName = $_POST['lastName'];
                    $email = $_POST['email'];
                    $phone = $_POST['phoneNumber'];
                    $legalOrganizationName = $this->params['clientsdetails']['email'];
                    $subscriberType = 'private_organization';
                    $country = 'CH';
                    $locality = 'Prilly';
                    $province  = 'Vaud';
                    $address = 'Route des Flumeaux 42-48';
                    $postalCode = '1008';

                    $subscriber = [
                        'new_subscriber' => [
                            'legal_organization_name' => $legalOrganizationName,
                            'subscriber_type' => $subscriberType,
                            'country_iso_code2' => $country,
                            'locality' => $locality,
                            'province' => $province,
                            'phone_number' => $phone,
                            'address' => $address,
                            'postal_code' => $postalCode,
                        ],
                        'contact' => [
                            'new_contact' => [
                                'contact_types' => [
                                    'organization',
                                    'technical',
                                    'billing'
                                ],
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                                'email' => $email,
                                'phone' => $phone,
                                'country_iso_code2' => $country,
                                'address' => $address,
                            ]
                        ]
                    ];
                }
                else
                {
                    $business = true;

                    $firstName = $_POST['firstName'];
                    $lastName = $_POST['lastName'];
                    $email = $_POST['email'];
                    $phone = $_POST['phoneNumber'];
                    $legalOrganizationName = $_POST['legalOrganizationName'];
                    $subscriberType = $_POST['subscriberType'];
                    $country = $_POST['country'];
                    $locality = $_POST['locality'];
                    $province  = $_POST['province'];
                    $address = $_POST['address'];
                    $postalCode = $_POST['postalCode'];
                    $registrationNumber = $_POST['registrationNumber'];

                    $subscriber = [
                        'new_subscriber' => [
                            'legal_organization_name' => $legalOrganizationName,
                            'subscriber_type' => $subscriberType,
                            'country_iso_code2' => $country,
                            'locality' => $locality,
                            'province' => $province,
                            'phone_number' => $phone,
                            'address' => $address,
                            'postal_code' => $postalCode,
                            'registration_number' => $registrationNumber,
                        ],
                        'contact' => [
                            'new_contact' => [
                                'contact_types' => [
                                    'organization',
                                    'technical',
                                    'billing'
                                ],
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                                'email' => $email,
                                'phone' => $phone,
                                'country_iso_code2' => $country,
                                'address' => $address,
                            ]
                        ]
                    ];
                }

                $request = [
                    'product_id' => (int) $this->params['configoption4'],
                    'certificate' => [
                        'csr' => $csrContent,
                        'validity' => [
                            'type' => 'length',
                            'length_in_days' => $lengthInDays,
                            'expiration_date' => null,
                        ],
                        'dns_names' => $dnsNames
                    ],
                    'subscriber' => $subscriber
                ];

                $resultPlaceOrder = $api->placeOrder($request);

                Capsule::table('tblsslorders')
                    ->where('serviceid', '=', $this->params['serviceid'])
                    ->update([
                        'configdata' => json_encode([
                            'csr' => $csrContent,
                        ]),
                        'remoteid' => $resultPlaceOrder['order_code'],
                        'status' => 'Configuration Submitted'
                    ]);

                if ($business)
                {
                    $this->saveBusinessSubscriberId($resultPlaceOrder['subscriber']['id']);
                    $this->saveBusinessContactId($resultPlaceOrder['order_contact']['id']);
                }
                else
                {
                    $this->saveSubscriberId($resultPlaceOrder['subscriber']['id']);
                    $this->saveContactId($resultPlaceOrder['order_contact']['id']);
                }

                $remoteId = $resultPlaceOrder['order_code'];
            }
            else if($sslOrder->status == "Awaiting Configuration")
            {
                $csrContent = $_POST['csr'];

                $domains = Csr::getDomainsFromCsr($csrContent);

                $dnsNames = [];

                foreach ($domains as $domain)
                {
                    $d = strtolower(trim($domain));

                    if ($d === '')
                    {
                        continue;
                    }

                    $dnsNames[] = $d;

                    if (str_starts_with($d, '*.'))
                    {
                        $apex = substr($d, 2);
                        $dnsNames[] = $apex;
                        continue;
                    }

                    if (str_starts_with($d, 'www.'))
                    {
                        $apex = substr($d, 4);

                        if(PublicSuffixHelper::isSubdomain($apex))//check if $apex is not subdomain
                        {
                            continue;
                        }

                        $dnsNames[] = $apex;
                    }
                    elseif(!PublicSuffixHelper::isSubdomain($d))
                    {
                        //add www only if $d is not subdomain
                        $dnsNames[] = 'www.' . $d;
                    }
                }

                $dnsNames = array_values(array_unique($dnsNames));

                $standardDomains = $this->params['configoptions']['standardDomains'];
                $wildcardDomains = $this->params['configoptions']['wildcardDomains'];

                $uniqueDomains = [];
                $standardDomainsCount = 0;
                $wildcardDomainsCount = 0;

                foreach($dnsNames as $dnsName)
                {
                    $d = strtolower(trim($dnsName));

                    if($d === '')
                    {
                        continue;
                    }

                    $isWildcard = false;

                    if(str_starts_with($d, '*.'))
                    {
                        $isWildcard = true;
                        $d = substr($d, 2);
                    }

                    if(str_starts_with($d, 'www.'))
                    {
                        $base = substr($d, 4);

                        if(!PublicSuffixHelper::isSubdomain($base))
                        {
                            $d = $base;
                        }
                    }

                    if(isset($uniqueDomains[$d]))
                    {
                        if ($isWildcard)
                        {
                            $wildcardDomainsCount++;
                            $standardDomainsCount--;
                        }

                        continue;
                    }

                    $uniqueDomains[$d] = true;

                    if($isWildcard)
                    {
                        $wildcardDomainsCount++;
                    }
                    else
                    {
                        $standardDomainsCount++;
                    }
                }

                if($wildcardDomains !== null && ((int) $wildcardDomains) > 0 && $wildcardDomainsCount == 0)
                {
                    throw new UserVisibleException('csrMissingWildcard');
                }

                if($standardDomains !== null && $standardDomainsCount > (int) $standardDomains)
                {
                    throw new UserVisibleException('sslStandardDomainsLimitExceeded');
                }

                if($wildcardDomains !== null && $wildcardDomainsCount > (int) $wildcardDomains)
                {
                    throw new UserVisibleException('sslWildcardDomainsLimitExceeded');
                }

                $request = [
                    'certificate' => [
                        'csr' => $csrContent,
                        'dns_names' => $dnsNames
                    ],
                ];

                $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);

                $resultReplaceOrder = $api->replaceOrder($sslOrder->remoteid, $request);

                Capsule::table('tblsslorders')
                    ->where('serviceid', '=', $this->params['serviceid'])
                    ->update([
                        'configdata' => json_encode([
                            'csr' => $csrContent,
                        ]),
                        'remoteid' => $resultReplaceOrder['order_code'],
                        'status' => 'Configuration Submitted'
                    ]);

                $remoteId = $resultReplaceOrder['order_code'];
            }
            else if ($sslOrder->status == "Awaiting Renew")
            {
                $csrContent = $_POST['csr'];

                if ($_POST['usePreviousCsr'] == "on")
                {
                    $csrContent = json_decode($sslOrder->configdata, true)['csr'];
                }

                $domains = Csr::getDomainsFromCsr($csrContent);

                $dnsNames = [];

                foreach ($domains as $domain)
                {
                    $d = strtolower(trim($domain));

                    if ($d === '')
                    {
                        continue;
                    }

                    $dnsNames[] = $d;

                    if (str_starts_with($d, '*.'))
                    {
                        $apex = substr($d, 2);
                        $dnsNames[] = $apex;
                        continue;
                    }

                    if (str_starts_with($d, 'www.'))
                    {
                        $apex = substr($d, 4);

                        if(PublicSuffixHelper::isSubdomain($apex))//check if $apex is not subdomain
                        {
                            continue;
                        }

                        $dnsNames[] = $apex;
                    }
                    elseif(!PublicSuffixHelper::isSubdomain($d))
                    {
                        //add www only if $d is not subdomain
                        $dnsNames[] = 'www.' . $d;
                    }
                }

                $dnsNames = array_values(array_unique($dnsNames));

                $standardDomains = $this->params['configoptions']['standardDomains'];
                $wildcardDomains = $this->params['configoptions']['wildcardDomains'];

                $uniqueDomains = [];
                $standardDomainsCount = 0;
                $wildcardDomainsCount = 0;

                foreach($dnsNames as $dnsName)
                {
                    $d = strtolower(trim($dnsName));

                    if($d === '')
                    {
                        continue;
                    }

                    $isWildcard = false;

                    if(str_starts_with($d, '*.'))
                    {
                        $isWildcard = true;
                        $d = substr($d, 2);
                    }

                    if(str_starts_with($d, 'www.'))
                    {
                        $base = substr($d, 4);

                        if(!PublicSuffixHelper::isSubdomain($base))
                        {
                            $d = $base;
                        }
                    }

                    if(isset($uniqueDomains[$d]))
                    {
                        if ($isWildcard)
                        {
                            $wildcardDomainsCount++;
                            $standardDomainsCount--;
                        }

                        continue;
                    }

                    $uniqueDomains[$d] = true;

                    if($isWildcard)
                    {
                        $wildcardDomainsCount++;
                    }
                    else
                    {
                        $standardDomainsCount++;
                    }
                }

                if($wildcardDomains !== null && ((int) $wildcardDomains) > 0 && $wildcardDomainsCount == 0)
                {
                    throw new UserVisibleException('csrMissingWildcard');
                }

                if($standardDomains !== null && $standardDomainsCount > (int) $standardDomains)
                {
                    throw new UserVisibleException('sslStandardDomainsLimitExceeded');
                }

                if($wildcardDomains !== null && $wildcardDomainsCount > (int) $wildcardDomains)
                {
                    throw new UserVisibleException('sslWildcardDomainsLimitExceeded');
                }

                $lengthInDays = match($this->params['model']->billingcycle) {
                    'Biennially' => 365 * 2,
                    'Triennially' => 365 * 3,
                    default => 365
                };

                $request = [
                    'certificate' => [
                        'csr' => $csrContent,
                        'validity' => [
                            'type' => 'length',
                            'length_in_days' => $lengthInDays,
                            'expiration_date' => null,
                        ],
                    ],
                ];

                $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);

                $resultReplaceOrder = $api->renewOrder($sslOrder->remoteid, $request);

                Capsule::table('tblsslorders')
                    ->where('serviceid', '=', $this->params['serviceid'])
                    ->update([
                        'configdata' => json_encode([
                            'csr' => $csrContent,
                        ]),
                        'remoteid' => $resultReplaceOrder['order_code'],
                        'status' => 'Configuration Submitted'
                    ]);

                $remoteId = $resultReplaceOrder['order_code'];
            }

            if(!$remoteId)
            {
                $remoteId = $sslOrder->remoteid;
            }

            $resultGetOrder = $api->getOrder($remoteId);

            $validationInfo = $resultGetOrder['domain_validation_info'] ?? [];

            foreach($validationInfo as $key => $info)
            {
                if($info['status'] != 'requested' && $info['status'] != 'not_requested')
                {
                    unset($validationInfo[$key]);
                }

                if (in_array('*.' . $info['domain_name'], $resultGetOrder['order_certificate_info']['sans']['dns']))
                {
                    $validationInfo[$key]['is_wildcard'] = true;
                }
            }

            if(strtoupper($resultGetOrder['order_status']) != 'PENDING')
            {
                $validationInfo = [];
            }

            $alertMessage = $_SESSION['TuringSignMessage'] ?? null;
            unset($_SESSION['TuringSignMessage']);

            $lang = new Lang();

            return [
                'tabOverviewReplacementTemplate' => 'templates/client/stepTwo',
                'templateVariables' => [
                    'tsLang' => $lang,
                    'validationInfo' => $validationInfo,
                    'alertMessage' => $alertMessage
                ]
            ];
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=stepOne');
            exit();
        }
        catch(\Exception $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'stepTwoError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=stepOne');
            exit();
        }
    }

    protected function stepThree(): array
    {
        try
        {
            global $ca;

            $sslOrder = Capsule::table('tblsslorders')
                ->where('serviceid', '=', $this->params['serviceid'])
                ->first();

            if(!$sslOrder || empty($sslOrder->remoteid))
            {
                header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
                exit();
            }

            $ca->addToBreadcrumb('#', 'Configure SSL Certificate');

            $domainsValidationsPost = $_POST['domainsValidations'] ?? [];

            $domainsValidations = [];

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);

            foreach($domainsValidationsPost as $domainValidation)
            {
                $domain = $domainValidation['domain'];

                if(empty($domain))
                {
                    continue;
                }

                $validationMethod = match($domainValidation['validationMethod']) {
                    'cnameDnsRecord' => 'dns_cname',
                    'constructedEmail' => 'constructed_email',
                    'txtDnsRecord' => 'txt_dns_record',
                    'txtHttpFile' => 'txt_http_file',
                    default => null
                };

                if(!$validationMethod)
                {
                    throw new UserVisibleException('invalidValidationMethod');
                }

                $email = $domainValidation['constructedEmail'] ?? null;

                if($email != 'admin@' && $email != 'webmaster@' && $email != 'hostmaster@' && $email != 'postmaster@' && $email != 'administrator@')
                {
                    $email = null;
                }

                if($validationMethod == 'constructed_email')
                {
                    $email = $email . $domain;
                }
                else
                {
                    $email = null;
                }

                $domainsValidations[] = [
                    'name' => $domain,
                    'method' => $validationMethod,
                    'email' => $email,
                ];

                if ($validationMethod == 'txt_http_file')
                {
                    $resultGetOrder = $api->getOrder($sslOrder->remoteid);

                    $dns = $resultGetOrder['order_certificate_info']['sans']['dns'];

                    foreach ($dns as $host) {
                        if ($host != $domain && str_ends_with($host, '.' . $domain))
                        {
                            $domainsValidations[] = [
                                'name' => $host,
                                'method' => $validationMethod,
                                'email' => $email
                            ];
                        }
                    }
                }
            }

            if(!empty($domainsValidations))
            {
                $api->changeValidationMethod($sslOrder->remoteid, [
                    'domain_validations' => $domainsValidations
                ]);
            }

            $lang = new Lang();

            return [
                'tabOverviewReplacementTemplate' => 'templates/client/stepThree',
                'templateVariables' => [
                    'tsLang' => $lang
                ]
            ];
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=stepTwo');
            exit();
        }
        catch(\Exception $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'stepThreeError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=stepTwo');
            exit();
        }
    }

    protected function revokeCertificate()
    {
        try
        {
            if(empty($_POST['confirmRevoke']) || $_POST['confirmRevoke'] !== 'true')
            {
                throw new \Exception('Invalid confirmation');
            }

            $sslOrderRemoteId = $this->getSslOrderRemoteId();

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
            $api->revokeCertificate($sslOrderRemoteId);

            $_SESSION['TuringSignMessage'] = [
                'type' => 'success',
                'message' => 'revokeCertificateSuccess'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(\Exception $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'revokeCertificateError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
    }

    protected function changeValidationMethod()
    {
        try
        {
            $sslOrderRemoteId = $this->getSslOrderRemoteId();

            $validationDomain = $_POST['validationDomain'] ?? null;

            $validationMethod = match($_POST['validationMethod']) {
                'cnameDnsRecord' => 'dns_cname',
                'constructedEmail' => 'constructed_email',
                'txtDnsRecord' => 'txt_dns_record',
                'txtHttpFile' => 'txt_http_file',
                default => null
            };

            if(!$validationMethod)
            {
                throw new UserVisibleException('invalidValidationMethod');
            }

            $email = $_POST['constructedEmail'] ?? null;

            if($email != 'admin@' && $email != 'webmaster@' && $email != 'hostmaster@' && $email != 'postmaster@' && $email != 'administrator@')
            {
                $email = null;
            }

            if($validationMethod != 'constructed_email')
            {
                $email = null;
            }
            else
            {
                $email = $email . $validationDomain;
            }

            $domainValidations = [
                [
                    'name' => $validationDomain,
                    'method' => $validationMethod,
                    'email' => $email
                ]
            ];

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);

            if ($validationMethod == 'txt_http_file')
            {
                $resultGetOrder = $api->getOrder($sslOrderRemoteId);

                $dns = $resultGetOrder['order_certificate_info']['sans']['dns'];

                foreach ($dns as $host) {
                    if ($host != $validationDomain && str_ends_with($host, '.' . $validationDomain))
                    {
                        $domainValidations[] = [
                            'name' => $host,
                            'method' => $validationMethod,
                            'email' => $email
                        ];
                    }
                }
            }

            $api->changeValidationMethod($sslOrderRemoteId, [
                'domain_validations' => $domainValidations
            ]);

            $_SESSION['TuringSignMessage'] = [
                'type' => 'success',
                'message' => 'changeValidationMethodSuccess'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(\Exception $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'changeValidationMethodError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
    }

    protected function replaceCertificate(): array
    {
        $sslOrder = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first();

        if(!$sslOrder || empty($sslOrder->remoteid))
        {
            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }

        Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->update([
                'configdata' => '',
                'status' => 'Awaiting Configuration'
            ]);

        header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid'] . '&ts-action=stepOne');
        exit();
    }

    protected function installCertificate()
    {
        try
        {
            $serviceId = $_POST['serviceId'];
            $privateKey = $_POST['privateKey'];

            $sslOrderRemoteId = $this->getSslOrderRemoteId();

            $data = null;

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
            $resultDownloadCertificate = $api->downloadOrderCertificate($sslOrderRemoteId);

            $tmp = tmpfile();

            fwrite($tmp, $resultDownloadCertificate);

            $meta = stream_get_meta_data($tmp);
            $path = $meta['uri'];

            $zip = new ZipArchive();
            $zip->open($path);

            for($i = 0; $i < $zip->numFiles; $i++)
            {
                $name = $zip->getNameIndex($i);

                if(strtolower($name) === 'pem/fullchain.pem')
                {
                    $data = $zip->getFromIndex($i);

                    break;
                }
            }

            $zip->close();
            fclose($tmp);

            $service = Capsule::table('tblhosting')
                ->where('id', '=', $serviceId)
                ->first();

            $loggedWhmcsClientId = (int)$_SESSION['uid'];

            if(!$loggedWhmcsClientId || $service->userid != $loggedWhmcsClientId)
            {
                throw new \Exception('invalidServiceOwner');
            }

            $server = Capsule::table('tblservers')
                ->where('id', '=', $service->server)
                ->first();

            if($server->type == 'plesk')
            {
                $baseUrl = sprintf("%s://%s:%s", $server->secure == "on" ? "https" : "http", $server->ipaddress, $server->port ?? "8443");

                preg_match_all(
                    '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s',
                    $data,
                    $matches
                );

                $pleskApi = new PleskApi($baseUrl, $server->username, decrypt($server->password));

                $certName = $service->domain . '_' . bin2hex(random_bytes(5));

                $response = $pleskApi->createCertificate($service->domain, $certName, $matches[0][0], $privateKey, $matches[0][1]);

                if($response?->certificate?->install?->result?->status == "error" && !empty($response?->certificate?->install?->result?->errtext))
                {
                    throw new UserVisibleException($response?->certificate?->install?->result?->errtext);
                }

                if(((string) ($response->xpath('//status')[0] ?? '')) != 'ok')
                {
                    throw new \Exception("Certificate creation failed");
                }

                $response = $pleskApi->enableSslOnDomain($service->domain, $certName);

                if($response?->site?->set?->result?->status == "error" && !empty($response?->site?->set?->result?->errtext))
                {
                    throw new UserVisibleException($response?->site?->set?->result?->errtext);
                }

                if(((string) ($response->xpath('//status')[0] ?? '')) != 'ok')
                {
                    throw new \Exception('Certificate creation failed');
                }
            }
            else if($server->type == 'cpanel')
            {
                $baseUrl = sprintf("%s://%s:%s", $server->secure == "on" ? "https" : "http", $server->ipaddress, $server->port ?? "2087");

                $cPanelApi = new CPanelApi($baseUrl, $server->username, $server->accesshash);
                $cPanelApi->installSsl($service->domain, $data, $privateKey);
            }
            else
            {
                throw new \Exception('invalidServerType');
            }

            $_SESSION['TuringSignMessage'] = [
                'type' => 'success',
                'message' => 'installCertificateSuccess'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch(\Throwable $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'installCertificateError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
    }

    protected function showCertificate()
    {
        try
        {
            $sslOrderRemoteId = $this->getSslOrderRemoteId();

            $api = new TlsManagerApi($this->params['configoption1'], $this->params['configoption2'], $this->params['configoption3']);
            $resultDownloadCertificate = $api->downloadOrderCertificate($sslOrderRemoteId);

            $data = null;

            $tmp = tmpfile();

            fwrite($tmp, $resultDownloadCertificate);

            $meta = stream_get_meta_data($tmp);
            $path = $meta['uri'];

            $zip = new ZipArchive();
            $zip->open($path);

            for($i = 0; $i < $zip->numFiles; $i++)
            {
                $name = $zip->getNameIndex($i);

                if(strtolower($name) === 'pem/fullchain.pem')
                {
                    $data = $zip->getFromIndex($i);

                    break;
                }
            }

            $zip->close();
            fclose($tmp);

            preg_match_all(
                '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s',
                $data,
                $matches
            );

            $lang = new Lang();

            return [
                'tabOverviewReplacementTemplate' => 'templates/client/showCertificate',
                'templateVariables' => [
                    'cert1' => $matches[0][0],
                    'cert2' => $matches[0][1],
                    'cert3' => $matches[0][2],
                    'tsLang' => $lang
                ]
            ];
        }
        catch(UserVisibleException $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
        catch (\Exception $e)
        {
            $_SESSION['TuringSignMessage'] = [
                'type' => 'error',
                'message' => 'showCertificateError'
            ];

            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }
    }

    protected function getSslOrderRemoteId()
    {
        $sslOrder = Capsule::table('tblsslorders')
            ->where('serviceid', '=', $this->params['serviceid'])
            ->first();

        if(!$sslOrder || empty($sslOrder->remoteid))
        {
            header('Location: clientarea.php?action=productdetails&id=' . $this->params['serviceid']);
            exit();
        }

        return $sslOrder->remoteid;
    }

    protected function saveSubscriberId($subscriberId)
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignSubscriberId|%')
            ->first();

        $customFieldId = $customField ? $customField->id : null;

        if(!$customFieldId)
        {
            $customFieldId = Capsule::table('tblcustomfields')
                ->insertGetId([
                    'type' => 'client',
                    'relid' => 0,
                    'fieldname' => 'turingSignSubscriberId|Turing Sign Subscriber ID',
                    'fieldtype' => 'text',
                    'adminonly' => 'on',
                ]);
        }

        $customFieldValue = CustomFieldValue::firstOrNew([
            'fieldid' => $customFieldId,
            'relid' => $this->params['userid']
        ]);

        $customFieldValue->value = $subscriberId;
        $customFieldValue->save();
    }

    protected function saveBusinessSubscriberId($subscriberId)
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignBusinessSubscriberId|%')
            ->first();

        $customFieldId = $customField ? $customField->id : null;

        if(!$customFieldId)
        {
            $customFieldId = Capsule::table('tblcustomfields')
                ->insertGetId([
                    'type' => 'client',
                    'relid' => 0,
                    'fieldname' => 'turingSignBusinessSubscriberId|Turing Sign Business Subscriber ID',
                    'fieldtype' => 'text',
                    'adminonly' => 'on',
                ]);
        }

        $customFieldValue = CustomFieldValue::firstOrNew([
            'fieldid' => $customFieldId,
            'relid' => $this->params['userid']
        ]);

        $customFieldValue->value = $subscriberId;
        $customFieldValue->save();
    }

    protected function saveContactId($contactId)
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignContactId|%')
            ->first();

        $customFieldId = $customField ? $customField->id : null;

        if(!$customFieldId)
        {
            $customFieldId = Capsule::table('tblcustomfields')
                ->insertGetId([
                    'type' => 'client',
                    'relid' => 0,
                    'fieldname' => 'turingSignContactId|Turing Sign Contact ID',
                    'fieldtype' => 'text',
                    'adminonly' => 'on',
                ]);
        }


        $customFieldValue = CustomFieldValue::firstOrNew([
            'fieldid' => $customFieldId,
            'relid' => $this->params['userid']
        ]);

        $customFieldValue->value = $contactId;
        $customFieldValue->save();
    }

    protected function saveBusinessContactId($contactId)
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignBusinessContactId|%')
            ->first();

        $customFieldId = $customField ? $customField->id : null;

        if(!$customFieldId)
        {
            $customFieldId = Capsule::table('tblcustomfields')
                ->insertGetId([
                    'type' => 'client',
                    'relid' => 0,
                    'fieldname' => 'turingSignBusinessContactId|Turing Sign Business Contact ID',
                    'fieldtype' => 'text',
                    'adminonly' => 'on',
                ]);
        }


        $customFieldValue = CustomFieldValue::firstOrNew([
            'fieldid' => $customFieldId,
            'relid' => $this->params['userid']
        ]);

        $customFieldValue->value = $contactId;
        $customFieldValue->save();
    }

    protected function getSubscriberId()
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignSubscriberId|%')
            ->first();

        if(!$customField)
        {
            return null;
        }

        $customFieldValue = Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', '=', $customField->id)
            ->where('relid', '=', $this->params['userid'])
            ->first();

        return $customFieldValue ? $customFieldValue->value : null;
    }

    protected function getBusinessSubscriberId()
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignBusinessSubscriberId|%')
            ->first();

        if(!$customField)
        {
            return null;
        }

        $customFieldValue = Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', '=', $customField->id)
            ->where('relid', '=', $this->params['userid'])
            ->first();

        return $customFieldValue ? $customFieldValue->value : null;
    }

    protected function getContactId()
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignContactId|%')
            ->first();

        if(!$customField)
        {
            return null;
        }

        $customFieldValue = Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', '=', $customField->id)
            ->where('relid', '=', $this->params['userid'])
            ->first();

        return $customFieldValue ? $customFieldValue->value : null;
    }

    protected function getBusinessContactId()
    {
        $customField = Capsule::table('tblcustomfields')
            ->where('type', '=', 'client')
            ->where('fieldname', 'LIKE', 'turingSignBusinessContactId|%')
            ->first();

        if(!$customField)
        {
            return null;
        }

        $customFieldValue = Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', '=', $customField->id)
            ->where('relid', '=', $this->params['userid'])
            ->first();

        return $customFieldValue ? $customFieldValue->value : null;
    }
}