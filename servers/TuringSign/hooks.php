<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

use WHMCS\Database\Capsule;
use WHMCS\Service\Ssl;

add_hook('ClientAreaPage', 5, function () {

    if(empty($_GET['ts-action']) || $_GET['ts-action'] !== 'stepThree')
    {
        return [];
    }

    $serviceId = $_GET['id'];
    $sslOrder = Ssl::with('service', 'service.product', 'client')->where('userid', '=', Auth::client()->id)->where('serviceid', '=', $serviceId)->first();

    if(!$sslOrder || $sslOrder->module != 'TuringSign')
    {
        return [];
    }

    Menu::addContext('service', $sslOrder->service);
    Menu::addContext('orderStatus', $sslOrder->status);
    Menu::addContext('step', 3);

    return [
        'primarySidebar' => Menu::primarySidebar('sslCertificateOrderView'),
        'secondarySidebar' => Menu::secondarySidebar('sslCertificateOrderView'),
    ];
});

add_hook('ClientAreaPage', 5, function () {

    if(empty($_GET['ts-action']) || $_GET['ts-action'] !== 'stepTwo')
    {
        return [];
    }

    $serviceId = $_GET['id'];
    $sslOrder = Ssl::with('service', 'service.product', 'client')->where('userid', '=', Auth::client()->id)->where('serviceid', '=', $serviceId)->first();

    if(!$sslOrder || $sslOrder->module != 'TuringSign')
    {
        return [];
    }

    Menu::addContext('service', $sslOrder->service);
    Menu::addContext('orderStatus', $sslOrder->status);
    Menu::addContext('step', 2);

    return [
        'primarySidebar' => Menu::primarySidebar('sslCertificateOrderView'),
        'secondarySidebar' => Menu::secondarySidebar('sslCertificateOrderView'),
    ];
});

add_hook('ClientAreaPage', 5, function () {

    if(empty($_GET['ts-action']) || $_GET['ts-action'] !== 'stepOne')
    {
        return [];
    }

    $serviceId = $_GET['id'];
    $sslOrder = Ssl::with('service', 'service.product', 'client')->where('userid', '=', Auth::client()->id)->where('serviceid', '=', $serviceId)->first();

    if(!$sslOrder || $sslOrder->module != 'TuringSign')
    {
        return [];
    }

    Menu::addContext('service', $sslOrder->service);
    Menu::addContext('orderStatus', 'Awaiting Configuration');
    Menu::addContext('step', 1);

    return [
        'primarySidebar' => Menu::primarySidebar('sslCertificateOrderView'),
        'secondarySidebar' => Menu::secondarySidebar('sslCertificateOrderView'),
    ];
});

add_hook('ClientAreaPrimarySidebar', 1, function ($primarySidebar) {

    if($_GET['action'] != 'productdetails' || empty($_GET['id']) || $_GET['ts-action'] == 'stepTwo' || $_GET['ts-action'] == 'stepOne' || $_GET['ts-action'] == 'stepThree')
    {
        return;
    }

    $service = Capsule::table('tblhosting')
        ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
        ->where('tblhosting.id', '=', $_GET['id'])
        ->where('tblproducts.servertype', '=', 'TuringSign')
        ->first();

    if(!$service)
    {
        return;
    }

    $serviceDetailsOverview = $primarySidebar->getChild('Service Details Overview');

    if(!$serviceDetailsOverview)
    {
        return;
    }

    $serviceDetailsOverview->removeChild('Information');

    $serviceDetailsOverview->addChild(
        'Information', [
            'name' => 'Information',
            'label' => 'Information',
            'uri' => 'clientarea.php?action=productdetails&id=' . $_GET['id'],
            'order' => 1
        ]
    );
});

add_hook('AdminAreaFooterOutput', 1, function ($params) {

    $serviceId = null;

    if(preg_match("/window\.location='\\?userid=\d+&id=(\d+)&action=deladdon/", $params['jscode'], $m))
    {
        $serviceId = (int) $m[1];
    }

    if(!$serviceId)
    {
        return "";
    }

    $service = Capsule::table('tblhosting')
        ->where('id', '=', $serviceId)
        ->first();

    if(!$service)
    {
        return "";
    }

    $invoiceItem = Capsule::table('tblinvoiceitems')
        ->where('type', '=', 'Hosting')
        ->where('relid', '=', $serviceId)
        ->first();

    if(!$invoiceItem)
    {
        return "";
    }

    $product = Capsule::table('tblproducts')
        ->where('id', '=', $service->packageid)
        ->first();

    if(!$product)
    {
        return "";
    }

    $sslOrder = Capsule::table('tblsslorders')
        ->where('serviceid', '=', $serviceId)
        ->first();

    if(!$sslOrder || empty($sslOrder->remoteid))
    {
        return "";
    }

    $invoiceId = $invoiceItem->invoiceid;

    if($_POST['tsRefund'] == "true" && !empty($_POST['transactionId']) && !empty($_POST['amount']) && !empty($_POST['refundType']))
    {
        try
        {
            $api = new ModulesGarden\TuringSign\Api\TlsManagerApi($product->configoption1, $product->configoption2, $product->configoption3);
            $api->refund($sslOrder->remoteid);
        }
        catch(Exception $e)
        {
            \logModuleCall('TuringSign', 'Refund Failed', 'Service ID: ' . $serviceId, $e->getMessage(), $e->getMessage());

            header('Location: clientsservices.php?userid=' . $_GET['userid'] . '&productselect=' . $serviceId . '&tsRefundStatus=error1');
            exit();
        }

        try
        {
            $result = refundInvoicePayment($_POST['transactionId'], (float)$_POST['amount'], $_POST['refundType'] == 'sendtogateway', $_POST['refundType'] == 'addascredit', $_POST['sendEmail'], $_POST['refundTransactionId'], (bool) $_POST['reverse']);

            if($result != 'creditsuccess' && $result != 'manual')
            {
                throw new Exception($result);
            }

            header('Location: clientsservices.php?userid=' . $_GET['userid'] . '&productselect=' . $serviceId . '&tsRefundStatus=success');
            exit();
        }
        catch(Exception $e)
        {
            \logModuleCall('TuringSign', 'Refund Failed', 'Service ID: ' . $serviceId, $e->getMessage(), $e->getMessage());

            header('Location: clientsservices.php?userid=' . $_GET['userid'] . '&productselect=' . $serviceId . '&tsRefundStatus=error2');
            exit();
        }
    }

    $transactions = Capsule::table('tblaccounts')
        ->where('invoiceid', '=', $invoiceId)
        ->where('amountin', '>', 0)
        ->where('type', '=', 'gateway_funds_in')
        ->orderBy('date', 'ASC')
        ->orderBy('id', 'ASC')
        ->get();

    $transactionsOptions = "";

    foreach($transactions as $transaction)
    {
        $transactionsOptions .= <<<HTML
<option value="{$transaction->id}" data-amount="{$transaction->amountin}">{$transaction->date} | {$transaction->transid} | {$transaction->amountin}</option>
HTML;

    }

    $alert = "";

    if($_GET['tsRefundStatus'] == 'success')
    {
        $alert = <<<HTML
<div id="turingSignAlert" class="alert alert-success">
Refund completed successfully.
</div>
<script>
$(document).ready(function(){
    $('#turingSignAlert').insertBefore($('#frm1'));
});
</script>
HTML;

    }

    if($_GET['tsRefundStatus'] == 'error1')
    {
        $alert = <<<HTML
<div id="turingSignAlert" class="alert alert-danger">
API refund request failed. See module logs for detailed error information.
</div>
<script>
$(document).ready(function(){
    $('#turingSignAlert').insertBefore($('#frm1'));
});
</script>
HTML;
    }

    if($_GET['tsRefundStatus'] == 'error2')
    {
        $alert = <<<HTML
<div id="turingSignAlert" class="alert alert-danger">
Internal WHMCS refund execution failed. See module logs for detailed error information.
</div>
<script>
$(document).ready(function(){
    $('#turingSignAlert').insertBefore($('#frm1'));
});
</script>
HTML;
    }

    return <<<HTML
{$alert}
<div class="modal fade" id="refundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="refundForm" action="clientsservices.php?userid={$_GET['userid']}&productselect={$serviceId}">
                <div class="modal-header card-header">
                    <h4 class="modal-title">
                        Refund Certificate
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-left">
                    <input type="hidden" name="tsRefund" value="true">
                    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                        <tbody>
                            <tr>
                                <td width="20%" class="fieldlabel">Transactions</td>
                                <td class="fieldarea">
                                    <select id="transactionId" name="transactionId" class="form-control" style="width: 100%;">
                                        {$transactionsOptions}
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td width="20%" class="fieldlabel">Amount</td>
                                <td class="fieldarea">
                                    <div class="input-group input-300">
                                        <input type="text" name="amount" id="amount" class="form-control" placeholder="0.00">
                                        <span class="input-group-addon">Leave blank for full refund</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td width="20%" class="fieldlabel">Refund Type</td>
                                <td class="fieldarea">
                                    <select id="refundType" name="refundType" class="form-control">
                                        <option value="sendtogateway">
                                            Refund through Gateway (If supported by module)
                                        </option>
                                        <option>Manual Refund Processed Externally</option>
                                        <option value="addascredit">Add to Client's Credit Balance</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="refundTransactionId">
                                <td width="20%" class="fieldlabel">Refund Transaction ID (Manual Refund)</td>
                                <td class="fieldarea">
                                    <input type="text" name="refundTransactionId" size="25" class="form-control">
                                </td>
                            </tr>
                            <tr>
                                <td width="20%" class="fieldlabel">Reverse Payment</td>
                                <td class="fieldarea">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="reverse" value="1">
                                        Undo automated actions triggered by this transaction - where possible.
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td width="20%" class="fieldlabel">Send Email</td>
                                <td class="fieldarea">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="sendEmail" checked="checked">
                                        Check to Send Confirmation Email
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Refund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    function refundCertificateButton()
    {
        const button = $('#btnRefund_Certificate');
        button.removeAttr('onclick');
        
        button.on('click', function() {  
            $('#refundModal').modal('show');
        });
    }
    
    refundCertificateButton();
    
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if(node.id === "modcmdbtns") {
                    refundCertificateButton();
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    $('#refundForm').on('submit', function (e) {
       e.preventDefault();
       
       if($('#refundForm input#amount').val().trim() === ''){
            $('#refundForm input#amount').val($('#refundForm #transactionId option:selected').data('amount'));
       }
       
       $(this).off('submit').submit();
    });
});
</script>
HTML;

});

add_hook('ClientAreaPageProductsServices', 1, function ($params) {

    $services = $params['services'];

    foreach ($services as &$service)
    {
        if ($service['module'] != 'TuringSign')
        {
            continue;
        }

        $orderNumber = Capsule::table('tblorders')
            ->join('tblhosting', 'tblhosting.orderid', '=', 'tblorders.id')
            ->where('tblhosting.id', '=', $service['id'])
            ->first(['ordernum']);

        if ($orderNumber && !empty($orderNumber->ordernum))
        {
            $service['domain'] = 'TS-ORDER:' . $orderNumber->ordernum;
        }
    }

    return [
        'services' => $services,
    ];
});

add_hook('ClientAreaFooterOutput', 1, function ($params) {

    if ($params['filename'] != 'clientarea' || $params['action'] != 'services')
    {
        return "";
    }

    $lang = new \ModulesGarden\TuringSign\Helpers\Lang();

    $orderNumberLang = $lang->get('orderNumber');

    return <<<HTML
<style>
#tableServicesList a[href*="TS-ORDER:"]
{
    visibility: hidden;
}
</style>
<script>
$(document).ready(function () {
    $('#tableServicesList a[href*="TS-ORDER:"]').each(function () {
        let href = $(this).attr('href');
        let order = href.split('TS-ORDER:')[1];
        
        $(this).text('{$orderNumberLang}' + order).removeAttr('href').css('visibility', 'visible');
    });
});
</script>
HTML;

});

add_hook('ShoppingCartValidateProductUpdate', 1, function ($params) {
    $certificates = 0;

    $configOptions = $params['configoption'];

    foreach ($configOptions as $key => $configOption)
    {
        $configOptionDb = Capsule::table('tblproductconfigoptions')
            ->where('id', '=', $key)
            ->where(function ($query) {
                $query->where('optionname', 'LIKE', 'standardDomains|%')
                    ->orWhere('optionname', 'LIKE', 'wildcardDomains|%');
            })
            ->first();

        if (!$configOptionDb)
        {
            continue;
        }

        $certificates += $configOption;
    }

    if ($certificates > 250)
    {
        $lang = new \ModulesGarden\TuringSign\Helpers\Lang();

        return [
            $lang->get('totalCertificatesLimitExceeded')
        ];
    }
});