<link rel="stylesheet" href="./modules/servers/TuringSign/templates/client/assets/css/styles.css">

<div class="ts-module">
    {if $alertMessage}
        {if $alertMessage['type'] == 'success'}
            <div class="alert alert-success">{$tsLang->get($alertMessage['message'])}</div>
        {elseif $alertMessage['type'] == "error"}
            <div class="alert alert-danger">{$tsLang->get($alertMessage['message'])}</div>
        {/if}
    {/if}

    <form method="POST" action="clientarea.php?action=productdetails&id={$id}&ts-action=stepTwo">
        <div class="panel card panel-default mb-3">
            <div class="panel-heading card-header">
                <h3 class="panel-title card-title m-0">{$tsLang->get('certificateConfiguration')}</h3>
            </div>
            <div class="panel-body card-body">
                <div class="form-group">
                    <label for="inputCsr">{$tsLang->get('certificateSigningRequest')}</label>
                    <textarea name="csr" id="inputCsr" rows="7" class="form-control" required>-----BEGIN CERTIFICATE REQUEST-----&#10;-----END CERTIFICATE REQUEST-----</textarea>
                </div>
                {if $awaitingRenew}
                    <div class="controls form-check">
                        <label for="usePreviousCsr">
                            <input type="checkbox" class="form-check-input" id="usePreviousCsr" name="usePreviousCsr">
                            Use previous CSR
                        </label>
                    </div>
                {/if}
            </div>
        </div>

        {if $showSubscriberForm}
            <div class="panel card panel-default mb-3">
                <div class="panel-heading card-header">
                    <h3 class="panel-title card-title m-0">{$tsLang->get('certificateHolderDetails')}</h3>
                </div>
                <div class="panel-body card-body">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-md-right" for="inputFirstName">{$tsLang->get('firstName')}</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="firstName" id="inputFirstName" value="{$clientsDetails['firstName']}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-md-right" for="inputLastName">{$tsLang->get('lastName')}</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="lastName" id="inputLastName" value="{$clientsDetails['lastName']}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-md-right" for="inputEmail">{$tsLang->get('email')}</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="email" id="inputEmail" value="{$clientsDetails['email']}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-md-right" for="inputPhoneNumber">{if !$productDv}{$tsLang->get('phoneNumber')}{else}{$tsLang->get('individualPhoneNumber')}{/if}</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="phoneNumber" id="inputPhoneNumber" value="{$clientsDetails['phoneNumber']}" required>
                        </div>
                    </div>
                    {if !$productDv}
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputLegalOrganizationName">{$tsLang->get('legalOrganizationName')}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="legalOrganizationName" id="inputLegalOrganizationName" value="{$clientsDetails['companyName']}">
                            </div>
                        </div>
                    {/if}
                    {if !$productDv}
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputSubscriberType">{$tsLang->get('subscriberType')}</label>
                            <div class="col-sm-8">
                                <select class="custom-select" name="subscriberType" id="inputSubscriberType" required>
                                    <option value="private_organization">{$tsLang->get('privateOrganization')}</option>
                                    <option value="government_entity">{$tsLang->get('governmentEntity')}</option>
                                    <option value="business_entity">{$tsLang->get('businessEntity')}</option>
                                    <option value="non_commercial_entity">{$tsLang->get('nonCommercialEntity')}</option>
                                </select>
                            </div>
                        </div>
                    {/if}
                    {if !$productDv}
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputCountry">{$tsLang->get('country')}</label>
                            <div class="col-sm-8">
                                <select class="custom-select" name="country" id="inputCountry" required>
                                    {foreach $countries as $countryCode => $country}
                                        <option value="{$countryCode}" {if $countryCode == $clientsDetails['country']}selected{/if}>{$country}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputLocality">{$tsLang->get('locality')}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="locality" id="inputLocality" value="{$clientsDetails['locality']}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputProvince">{$tsLang->get('province')}</label>
                            <div class="col-sm-8">
                                <select class="custom-select" name="province" id="inputProvince" required>

                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputAddress">{$tsLang->get('address')}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="address" id="inputAddress" value="{$clientsDetails['address']}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputPostalCode">{$tsLang->get('postalCode')}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="postalCode" id="inputPostalCode" value="{$clientsDetails['postalCode']}">
                            </div>
                        </div>
                    {/if}
                    {if !$productDv}
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label text-md-right" for="inputRegistrationNumber">{$tsLang->get('registrationNumber')}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="registrationNumber" id="inputRegistrationNumber">
                            </div>
                        </div>
                    {/if}
                </div>
            </div>
        {/if}

        <div class="panel card panel-default mb-3">
            <div class="panel-heading card-header">
                <h3 class="panel-title card-title m-0">{$tsLang->get('subscriberAgreement')}</h3>
            </div>
            <div class="panel-body card-body">
                <div class="controls form-check">
                    <label for="subscriberAgreement">
                        <input type="checkbox" class="form-check-input" id="subscriberAgreement" required>
                        By checking this box, I agree to TuringSign's <a href="https://turingsign.com/turingsign_subscriber_agreement">Terms and Conditions</a>.
                    </label>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                {$tsLang->get('continueConfiguration')}
            </button>
        </div>
    </form>

    {if !$productDv}
        <script>
            $(document).ready(function() {
                $('#inputCountry').on('change', function() {
                    const selectedValue = $(this).val();

                    $.ajax({
                        url: 'clientarea.php?action=productdetails&id={$id}&ts-action=getProvinces',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            country: selectedValue
                        },
                        success: function (response) {

                            const provinceSelect = $('#inputProvince');
                            provinceSelect.empty();

                            if (response.status !== 'success') {
                                return;
                            }

                            $.each(response.provinces, function (index, name) {
                                provinceSelect.append(
                                    $('<option>', {
                                        value: name,
                                        text: name
                                    })
                                );
                            });
                        }
                    });
                });

                $('#inputCountry').trigger('change');
            });
        </script>
    {/if}
</div>