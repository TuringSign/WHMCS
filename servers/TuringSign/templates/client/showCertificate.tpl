<link rel="stylesheet" href="./modules/servers/TuringSign/templates/client/assets/css/styles.css">

<style>
    pre.ssl-cert
    {
        font-family: Consolas, Monaco, monospace;
        font-size: 13px;
        line-height: 1.45;

        background-color: #020617;
        color: #e5e7eb;

        padding: 12px;
        border-radius: 4px;

        white-space: pre;
        overflow-x: auto;
    }

     a[menuitemname^="TuringSign_"]
     {
         cursor: pointer;
     }
</style>

<div class="ts-module">
    <div class="panel card panel-default mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$tsLang->get('serverCertificate')}</h3>
        </div>
        <div class="panel-body card-body">
            <pre class="ssl-cert"><code>{$cert1}</code></pre>
        </div>
    </div>

    <div class="panel card panel-default mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$tsLang->get('intermediateCertificate')}</h3>
        </div>
        <div class="panel-body card-body">
            <pre class="ssl-cert"><code>{$cert2}</code></pre>
        </div>
    </div>

    <div class="panel card panel-default mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$tsLang->get('rootCertificate')}</h3>
        </div>
        <div class="panel-body card-body">
            <pre class="ssl-cert"><code>{$cert3}</code></pre>
        </div>
    </div>

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