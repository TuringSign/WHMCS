<link rel="stylesheet" href="./modules/servers/TuringSign/templates/client/assets/css/styles.css">

<div class="ts-module">
    <div class="alert alert-success text-center">{$tsLang->get('certificateConfigurationSuccess')}</div>

    <div class="text-center">
        <form method="POST" action="clientarea.php?action=productdetails&id={$id}">
            <button type="submit" class="btn btn-primary btn-lg">
                {$tsLang->get('backToServiceDetails')}
            </button>
        </form>
    </div>
</div>
