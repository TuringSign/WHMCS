<?php

return [
    // STEP 1
    'certificateConfiguration' => 'Certificate Configuration',
    'certificateSigningRequest' => 'Certificate Signing Request',
    'certificateHolderDetails' => 'Certificate Holder Details',
    'customerType' => 'Customer Type',
    'individualCustomer' => 'Individual Customer',
    'businessCustomer' => 'Business Customer',
    'firstName' => 'First Name',
    'lastName' => 'Last Name',
    'email' => 'Email',
    'individualPhoneNumber' => 'Contact Number',
    'phoneNumber' => 'Organization Contact Number',
    'legalOrganizationName' => 'Legal Organization Name',
    'subscriberType' => 'Subscriber Type',
    'privateOrganization' => 'Private Organization',
    'governmentEntity' => 'Government Entity',
    'businessEntity' => 'Business Entity',
    'nonCommercialEntity' => 'Non Commercial Entity',
    'country' => 'Country',
    'locality' => 'Locality',
    'province' => 'Province',
    'address' => 'Address',
    'postalCode' => 'Postal Code',
    'registrationNumber' => 'Registration Number',
    'subscriberAgreement' => 'Subscriber Agreement',
    'continueConfiguration' => 'Continue Configuration',

    // STEP 2
    'noDomainValidationRequired' => 'All required domains have already been validated. No further action is needed.',
    'domainValidation' => 'Domain Validation',
    'dnsTxtRecord' => 'DNS (TXT Record)',
    'dnsTxtRecordDescription' => 'Add a TXT record to your domain\'s DNS settings to confirm ownership.',
    'dnsCnameRecord' => 'DNS (CNAME record)',
    'dnsCnameRecordDescription' => 'Add a CNAME record to your DNS configuration to verify this domain.',
    'emailConstructedAddress' => 'Email (Constructed address)',
    'emailConstructedAddressDescription' => 'Receive a verification email at one of the selected administrative email addresses.',
    'txtHttpFile' => 'HTTP File Verification',
    'txtHttpFileDescription' => 'Upload a verification file to your website to confirm domain ownership.',
    // 'continueConfiguration' - STEP 1

    // STEP 3
    'certificateConfigurationSuccess' => 'Your configuration has been completed. Domain validation is now being processed.',
    'backToServiceDetails' => 'Back to Service Details',

    // ACTIONS
    'configureNow' => 'Configure Now',
    'renewNow' => 'Renew Now',
    'showCertificate' => 'Show Certificate',
    'downloadCertificate' => 'Download Certificate',
    'revokeCertificate' => 'Revoke Certificate',
    'reissueCertificate' => 'Reissue Certificate',

    // INDEX
    'status' => 'Status',
    'awaitingConfiguration' => 'Awaiting Configuration',
    'ISSUED' => 'ISSUED',
    'PENDING' => 'PENDING',
    'READY' => 'READY',
    'REVOKED' => 'REVOKED',
    'certificateInformation' => 'Certificate Information',
    'commonName' => 'Common Name: ',
    'subjectAlternativeNames' => 'Subject Alternative Names: ',
    'certificateType' => 'Certificate Type: ',
    'validFrom' => 'Valid From: ',
    'validTo' => 'Valid To: ',
    'orderId' => 'Order ID: ',
    // 'domainValidation' - STEP 2
    'dnsTxtRecordInstructions' => 'Create a TXT record for this domain. Set the record name to the domain name and paste the verification code below as the record value.',
    'dnsCnameRecordInstructions' => 'Crate a CNAME record for this domain. Set the record name to the domain name and paste the verification code below as the record target.',
    'emailConstructedAddressInstructions' => 'Open the verification email sne to the selected address and confirm the request by clicking the verification link.',
    'txtHttpFileInstructions' => 'Create a text file containing the verification code below and upload it to the root directory of this domain so it its publicly accessible.',
    'changeValidationMethod' => 'Change Validation Method',
    'selectValidationMethod' => 'Select Validation Method',
    'noValidationMethodSelected' => 'No domain validation method has been selected. Please choose a method to continue.',
    // 'dnsTxtRecord' - STEP 2
    // 'dnsTxtRecordDescription' - STEP 2
    // 'dnsCnameRecord' - STEP 2
    // 'dnsCnameRecordDescription' - STEP 2
    // 'emailConstructedAddress' - STEP 2
    // 'emailConstructedAddressDescription' - STEP 2
    // 'txtHttpFile' - STEP 2
    // 'txtHttpFileDescription' - STEP 2
    'installCertificate' => 'Install Certificate',
    'service' => 'Service',
    'privateKey' => 'Private Key',
    'sendCertificate' => 'Send Certificate',
    // 'email' - STEP 1
    // 'revokeCertificate' - ACTIONS
    'revokeCertificateWarning' => 'Are you sure you want to revoke this certificate? This action cannot be undone.',
    'close' => 'Close',
    // 'reissueCertificate - ACTIONS
    'reissueCertificateWarning' => 'Are you sure you want to reissue this certificate?',

    // SHOW CERTIFICATE
    'serverCertificate' => 'Server Certificate',
    'intermediateCertificate' => 'Intermediate Certificate',
    'rootCertificate' => 'Root Certificate',

    // ALERTS (SUCCESS, ERROR)
    'stepTwoError' => 'An error occurred while processing your certificate request.',
    'invalidValidationMethod' => 'Please select a domain validation method before continuing.',
    'Domains for validation not found' => 'The selected domain validation method is already applied. Please select a different method to update.',
    'totalCertificatesLimitExceeded' => 'The total number of certificates cannot exceed 250.',
    'sslStandardDomainsLimitExceeded' => 'Your CSR contains more standard domains than allowed for this product.',
    'sslWildcardDomainsLimitExceeded' => 'Your CSR contains more wildcard domains than allowed for this product.',
    'csrMissingWildcard' => 'This certificate supports wildcard domains, but the provided CSR does not include any wildcard domain.',
    'stepThreeError' => 'An error occurred while configuring domain validation.',
    'downloadCertificateError' => 'Failed to download certificate.',
    'revokeCertificateSuccess' => 'Certificate revoked successfully.',
    'revokeCertificateError' => 'Failed to revoke certificate.',
    'installCertificateSuccess' => 'Certificate installed successfully.',
    'installCertificateError' => 'Failed to install certificate.',
    'showCertificateError' => 'Failed to load certificate details.',
    'sendCertificateSuccess' => 'Certificate sent successfully.',
    'sendCertificateError' => 'Failed to send certificate.',
    'changeValidationMethodSuccess' => 'Validation method changed successfully.',
    'changeValidationMethodError' => 'Failed to change validation method.',

    // ORDER NUMBER
    'orderNumber' => 'Order Number: ',
];