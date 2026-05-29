<link rel="stylesheet" href="./modules/servers/TuringSign/templates/client/assets/css/styles.css">

<style>
    a[menuitemname^="TuringSign_"]
    {
        cursor: pointer;
    }
</style>

<div class="ts-module">
    {if $alertMessage}
        {if $alertMessage['type'] == 'success'}
            <div class="alert alert-success">{$tsLang->get($alertMessage['message'])}</div>
        {elseif $alertMessage['type'] == "error"}
            <div class="alert alert-danger">{$tsLang->get($alertMessage['message'])}</div>
        {/if}
    {/if}

    <div class="panel card panel-default mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$tsLang->get('certificateInformation')}</h3>
        </div>
        <div class="panel-body card-body text-left">
            {if $certificateStatus}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('status')}</div>
                    <div class="col-8">{$tsLang->get($certificateStatus)}</div>
                </div>
            {else}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('status')}</div>
                    <div class="col-8">{$tsLang->get('awaitingConfiguration')}</div>
                </div>
            {/if}
            {if $primaryDomain}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('commonName')}</div>
                    <div class="col-8">{$primaryDomain}</div>
                </div>
            {/if}
            {if $subjectAlternativeNames}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('subjectAlternativeNames')}</div>
                    <div class="col-8">{$subjectAlternativeNames}</div>
                </div>
            {/if}
            {if $certificateType}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('certificateType')}</div>
                    <div class="col-8">{$certificateType}</div>
                </div>
            {/if}
            {if $certificateType}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('validFrom')}</div>
                    <div class="col-8">{$validFrom}</div>
                </div>
            {/if}
            {if $certificateType}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('validTo')}</div>
                    <div class="col-8">{$validTo}</div>
                </div>
            {/if}
            {if $certificateType}
                <div class="row mb-1">
                    <div class="col-4 fw-bold">{$tsLang->get('orderId')}</div>
                    <div class="col-8">{$orderId}</div>
                </div>
            {/if}
        </div>
    </div>

    {foreach $validationInfo as $i => $info}
        {if $info['status'] == 'requested'}
            <div class="panel card panel-default mb-3">
                <div class="panel-heading card-header">
                    <h3 class="panel-title card-title m-0">{$tsLang->get('domainValidation')} - {$info['domain_name']}</h3>
                </div>
                <div class="panel-body card-body">
                    {if $info['validation_method'] == 'txt_dns_record'}
                        <p>{$tsLang->get('dnsTxtRecordInstructions')}</p>
                        <div class="card bg-light mb-3">
                            <div class="card-body p-2 font-monospace text-center">
                                {$info['validation_key']}
                            </div>
                        </div>
                    {elseif $info['validation_method'] == 'dns_cname'}
                        <p>{$tsLang->get('dnsCnameRecordInstructions')}</p>
                        <div class="card bg-light mb-3">
                            <div class="card-body p-2 font-monospace text-center">
                                {$info['validation_key']}
                            </div>
                        </div>
                    {elseif $info['validation_method'] == 'constructed_email'}
                        <p>{$tsLang->get('emailConstructedAddressInstructions')}</p>
                        <div class="card bg-light mb-3">
                            <div class="card-body p-2 font-monospace text-center">
                                {$info['validation_email']}
                            </div>
                        </div>
                    {elseif $info['validation_method'] == 'txt_http_file'}
                        <p>{$tsLang->get('txtHttpFileInstructions')}</p>
                        <div class="card bg-light mb-3">
                            <div class="card-body p-2 font-monospace text-center">
                                {$info['validation_key']}
                            </div>
                        </div>
                    {/if}
                </div>
            </div>
        {/if}

        <div class="panel card panel-default mb-3">
            <div class="panel-heading card-header">
                <h3 class="panel-title card-title m-0">{if $info['status'] == 'requested'}{$tsLang->get('changeValidationMethod')}{else}{$tsLang->get('selectValidationMethod')}{/if} - {$info['domain_name']}</h3>
            </div>
            <div class="panel-body card-body text-left">
                {if $info['status'] == 'not_requested'}
                    <div class="alert alert-warning">{$tsLang->get('noValidationMethodSelected')}</div>
                {/if}
                <form method="POST" action="clientarea.php?action=productdetails&id={$id}&ts-action=changeValidationMethod">
                    <input type="hidden" name="validationDomain" value="{$info['domain_name']}">
                    <div class="form-group">
                        <label class="form-check form-check-inline">
                            <input type="radio" name="validationMethod" value="txtDnsRecord" required>
                            <strong class="name">{$tsLang->get('dnsTxtRecord')}</strong>
                        </label>
                        <div class="tab-content pt-3">
                            <div class="alert alert-secondary">
                                {$tsLang->get('dnsTxtRecordDescription')}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-check form-check-inline">
                            <input type="radio" name="validationMethod" value="cnameDnsRecord">
                            <strong class="name">{$tsLang->get('dnsCnameRecord')}</strong>
                        </label>
                        <div class="tab-content pt-3">
                            <div class="alert alert-secondary">
                                {$tsLang->get('dnsCnameRecordDescription')}
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-check form-check-inline">
                            <input type="radio" name="validationMethod" value="constructedEmail" id="constructedEmail-{$i}">
                            <strong class="name">{$tsLang->get('emailConstructedAddress')}</strong>
                        </label>
                        <div class="tab-content pt-3">
                            <div class="alert alert-secondary">
                                {$tsLang->get('emailConstructedAddressDescription')}
                            </div>

                            <div style="display: flex; justify-content: space-between; padding-right: 3%; padding-left: 3%;">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="constructedEmail" id="constructedEmailAdmin-{$i}" value="admin@">
                                    <label class="form-check-label" for="constructedEmailAdmin-{$i}">admin@{$info['domain_name']}</label>

                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="constructedEmail" id="constructedEmailWebmaster-{$i}" value="webmaster@">
                                    <label class="form-check-label" for="constructedEmailWebmaster-{$i}">webmaster@{$info['domain_name']}</label>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding-right: 3%; padding-left: 3%;">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="constructedEmail" id="constructedEmailHostmaster-{$i}" value="hostmaster@">
                                    <label class="form-check-label" for="constructedEmailHostmaster-{$i}">hostmaster@{$info['domain_name']}</label>

                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="constructedEmail" id="constructedEmailPostmaster-{$i}" value="postmaster@">
                                    <label class="form-check-label" for="constructedEmailPostmaster-{$i}">postmaster@{$info['domain_name']}</label>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding-right: 3%; padding-left: 3%;">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="constructedEmail" id="constructedEmailAdministrator-{$i}" value="administrator@">
                                    <label class="form-check-label" for="constructedEmailAdministrator-{$i}">administrator@{$info['domain_name']}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    {if empty($info['is_wildcard'])}
                        <div class="form-group">
                            <label class="form-check form-check-inline">
                                <input type="radio" name="validationMethod" value="txtHttpFile">
                                <strong class="name">{$tsLang->get('txtHttpFile')}</strong>
                            </label>
                            <div class="tab-content pt-3">
                                <div class="alert alert-secondary">
                                    {$tsLang->get('txtHttpFileDescription')}
                                </div>
                            </div>
                        </div>
                    {/if}
                    <script>
                        $(document).ready(function () {
                            $('#constructedEmailAdmin-{$i},#constructedEmailWebmaster-{$i},#constructedEmailHostmaster-{$i},#constructedEmailPostmaster-{$i},#constructedEmailAdministrator-{$i}').on('click', function () {
                                $('#constructedEmail-{$i}').prop('checked', true).trigger('change');
                            });
                        });
                    </script>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            {if $info['status'] == 'requested'}{$tsLang->get('changeValidationMethod')}{else}{$tsLang->get('selectValidationMethod')}{/if}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/foreach}


    {if $certificateStatus == "ISSUED" && !empty($servicesArray)}
        <div class="panel card panel-default mb-3">
            <div class="panel-heading card-header">
                <h3 class="panel-title card-title m-0">{$tsLang->get('installCertificate')}</h3>
            </div>
            <div class="panel-body card-body">
                <form method="POST" action="clientarea.php?action=productdetails&id={$id}&ts-action=installCertificate">
                    <div class="form-group text-start">
                        <label for="serviceId" class="form-label">
                            {$tsLang->get('service')}
                        </label>
                        <select id="serviceId" name="serviceId" class="form-control" required>
                            {foreach $servicesArray as $key => $service}
                                <option value="{$key}">{$service}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="form-group text-start">
                        <label for="privateKey" class="form-label">
                            {$tsLang->get('privateKey')}
                        </label>

                        <textarea
                                id="privateKey"
                                name="privateKey"
                                class="form-control font-monospace"
                                rows="10"
                                required>-----BEGIN PRIVATE KEY-----&#10;-----END PRIVATE KEY-----</textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            {$tsLang->get('installCertificate')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    {if $certificateStatus == "ISSUED"}
        <div class="panel card panel-default mb-3">
            <div class="panel-heading card-header">
                <h3 class="panel-title card-title m-0">{$tsLang->get('sendCertificate')}</h3>
            </div>
            <div class="panel-body card-body">
                <form method="POST" action="clientarea.php?action=productdetails&id={$id}&ts-action=sendCertificate">
                    <div class="form-group text-start">
                        <label for="email" class="form-label">
                            {$tsLang->get('email')}
                        </label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            {$tsLang->get('sendCertificate')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    <div class="modal fade" id="revokeModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header">
                    <h4 class="modal-title">
                        {$tsLang->get('revokeCertificate')}
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-left">
                    <p>{$tsLang->get('revokeCertificateWarning')}</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="clientarea.php?action=productdetails&id={$id}&ts-action=revokeCertificate">
                        <input type="hidden" name="confirmRevoke" value="true">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            {$tsLang->get('close')}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            {$tsLang->get('revokeCertificate')}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reissueModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header">
                    <h4 class="modal-title">
                        {$tsLang->get('reissueCertificate')}
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-left">
                    <p>{$tsLang->get('reissueCertificateWarning')}</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="clientarea.php?action=productdetails&id={$id}&ts-action=replaceCertificate">
                        <input type="hidden" name="confirmReissue" value="true">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            {$tsLang->get('close')}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            {$tsLang->get('reissueCertificate')}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#Primary_Sidebar-Service_Details_Actions-TuringSign_RevokeCertificate:not(.disabled)').off('click');
            $('#Primary_Sidebar-Service_Details_Actions-TuringSign_RevokeCertificate:not(.disabled)').on('click', function (e) {
                e.preventDefault();

                $('#revokeModal').modal('show');
            });

            $('#Primary_Sidebar-Service_Details_Actions-TuringSign_ReplaceCertificate:not(.disabled)').off('click');
            $('#Primary_Sidebar-Service_Details_Actions-TuringSign_ReplaceCertificate:not(.disabled)').on('click', function (e) {
                e.preventDefault();

                $('#reissueModal').modal('show');
            });

            $('a[menuitemname^="TuringSign_"]').removeAttr('href');
        });
    </script>
</div>