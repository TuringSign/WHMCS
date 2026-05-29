<link rel="stylesheet" href="./modules/servers/TuringSign/templates/client/assets/css/styles.css">

<div class="ts-module">
    {if $alertMessage}
        {if $alertMessage['type'] == 'success'}
            <div class="alert alert-success">{$tsLang->get($alertMessage['message'])}</div>
        {elseif $alertMessage['type'] == "error"}
            <div class="alert alert-danger">{$tsLang->get($alertMessage['message'])}</div>
        {/if}
    {/if}

    <form method="POST" action="clientarea.php?action=productdetails&id={$id}&ts-action=stepThree">
        {if empty($validationInfo)}
            <div class="alert alert-success">{$tsLang->get('noDomainValidationRequired')}</div>
        {else}
            {foreach $validationInfo as $i => $info}
                <div class="panel card panel-default mb-3">
                    <div class="panel-heading card-header">
                        <h3 class="panel-title card-title m-0">{$tsLang->get('domainValidation')} - {$info['domain_name']}</h3>
                    </div>
                    <div class="panel-body card-body">
                        <input type="hidden" name="domainsValidations[{$i}][domain]" value="{$info['domain_name']}">
                        <div class="form-group">
                            <label class="form-check form-check-inline">
                                <input type="radio" name="domainsValidations[{$i}][validationMethod]" value="txtDnsRecord" required>
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
                                <input type="radio" name="domainsValidations[{$i}][validationMethod]" value="cnameDnsRecord">
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
                                <input type="radio" name="domainsValidations[{$i}][validationMethod]" id="constructedEmail-{$i}" value="constructedEmail">
                                <strong class="name">{$tsLang->get('emailConstructedAddress')}</strong>
                            </label>
                            <div class="tab-content pt-3">
                                <div class="alert alert-secondary">
                                    {$tsLang->get('emailConstructedAddressDescription')}
                                </div>

                                <div style="display: flex; justify-content: space-between; padding-right: 6%; padding-left: 6%;">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="domainsValidations[{$i}][constructedEmail]" id="constructedEmailAdmin-{$i}" value="admin@">
                                        <label class="form-check-label" for="constructedEmailAdmin-{$i}">admin@{$info['domain_name']}</label>

                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="domainsValidations[{$i}][constructedEmail]" id="constructedEmailWebmaster-{$i}" value="webmaster@">
                                        <label class="form-check-label" for="constructedEmailWebmaster-{$i}">webmaster@{$info['domain_name']}</label>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-right: 6%; padding-left: 6%;">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="domainsValidations[{$i}][constructedEmail]" id="constructedEmailHostmaster-{$i}" value="hostmaster@">
                                        <label class="form-check-label" for="constructedEmailHostmaster-{$i}">hostmaster@{$info['domain_name']}</label>

                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="domainsValidations[{$i}][constructedEmail]" id="constructedEmailPostmaster-{$i}" value="postmaster@">
                                        <label class="form-check-label" for="constructedEmailPostmaster-{$i}">postmaster@{$info['domain_name']}</label>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-right: 6%; padding-left: 6%;">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="domainsValidations[{$i}][constructedEmail]" id="constructedEmailAdministrator-{$i}" value="administrator@">
                                        <label class="form-check-label" for="constructedEmailAdministrator-{$i}">administrator@{$info['domain_name']}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {if empty($info['is_wildcard'])}
                            <div class="form-group">
                                <label class="form-check form-check-inline">
                                    <input type="radio" name="domainsValidations[{$i}][validationMethod]" value="txtHttpFile">
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
                    </div>
                </div>
            {/foreach}
        {/if}
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                {$tsLang->get('continueConfiguration')}
            </button>
        </div>
    </form>
</div>