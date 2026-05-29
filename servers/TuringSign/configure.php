<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "init.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

use WHMCS\Database\Capsule;

try
{
    $turingSignSendConfigurationLink = Capsule::table('tblemailtemplates')
        ->where('name', '=', 'TuringSign Send Configuration Link')
        ->first();

    if(!$turingSignSendConfigurationLink)
    {
        Capsule::table('tblemailtemplates')
            ->insert([
                'type' => 'product',
                'name' => 'TuringSign Send Configuration Link',
                'subject' => 'TuringSign Send Configuration Link',
                'message' => 'You can configure your certificate here: {$sslConfigurationLink}.',
                'disabled' => 0,
                'custom' => 1,
                'plaintext' => 0
            ]);
    }

    $turingSignSendCertificate = Capsule::table('tblemailtemplates')
        ->where('name', '=', 'TuringSign Send Certificate')
        ->first();

    if(!$turingSignSendCertificate)
    {
        Capsule::table('tblemailtemplates')
            ->insert([
                'type' => 'product',
                'name' => 'TuringSign Send Certificate',
                'subject' => 'TuringSign Send Certificate',
                'message' => 'You will find your certificate in the attachment.',
                'disabled' => 0,
                'custom' => 1,
                'plaintext' => 0
            ]);
    }

    $customFieldSubscriber = Capsule::table('tblcustomfields')
        ->where('type', '=', 'client')
        ->where('fieldname', 'LIKE', 'turingSignSubscriberId|%')
        ->first();

    $customFieldSubscriberId = $customFieldSubscriber ? $customFieldSubscriber->id : null;

    if(!$customFieldSubscriberId)
    {
        $customFieldSubscriberId = Capsule::table('tblcustomfields')
            ->insertGetId([
                'type' => 'client',
                'relid' => 0,
                'fieldname' => 'turingSignSubscriberId|Turing Sign Subscriber ID',
                'fieldtype' => 'text',
                'adminonly' => 'on',
            ]);
    }

    $customFieldContact = Capsule::table('tblcustomfields')
        ->where('type', '=', 'client')
        ->where('fieldname', 'LIKE', 'turingSignContactId|%')
        ->first();

    $customFieldContactId = $customFieldContact ? $customFieldContact->id : null;

    if(!$customFieldContactId)
    {
        $customFieldContactId = Capsule::table('tblcustomfields')
            ->insertGetId([
                'type' => 'client',
                'relid' => 0,
                'fieldname' => 'turingSignContactId|Turing Sign Contact ID',
                'fieldtype' => 'text',
                'adminonly' => 'on',
            ]);
    }

    $customFieldBusinessSubscriber = Capsule::table('tblcustomfields')
        ->where('type', '=', 'client')
        ->where('fieldname', 'LIKE', 'turingSignBusinessSubscriberId|%')
        ->first();

    $customFieldBusinessSubscriberId = $customFieldBusinessSubscriber ? $customFieldBusinessSubscriber->id : null;

    if(!$customFieldBusinessSubscriberId)
    {
        $customFieldBusinessSubscriberId = Capsule::table('tblcustomfields')
            ->insertGetId([
                'type' => 'client',
                'relid' => 0,
                'fieldname' => 'turingSignBusinessSubscriberId|Turing Sign Business Subscriber ID',
                'fieldtype' => 'text',
                'adminonly' => 'on',
            ]);
    }

    $customFieldBusinessContact = Capsule::table('tblcustomfields')
        ->where('type', '=', 'client')
        ->where('fieldname', 'LIKE', 'turingSignBusinessContactId|%')
        ->first();

    $customFieldBusinessContactId = $customFieldBusinessContact ? $customFieldBusinessContact->id : null;

    if(!$customFieldBusinessContactId)
    {
        $customFieldBusinessContactId = Capsule::table('tblcustomfields')
            ->insertGetId([
                'type' => 'client',
                'relid' => 0,
                'fieldname' => 'turingSignBusinessContactId|Turing Sign Business Contact ID',
                'fieldtype' => 'text',
                'adminonly' => 'on',
            ]);
    }
}
catch(Exception $e)
{
    \logModuleCall('TuringSign', 'Configure', '', $e->getMessage(), $e->getMessage());
    echo "Exception: ", $e->getMessage(), "\n";
    exit();
}