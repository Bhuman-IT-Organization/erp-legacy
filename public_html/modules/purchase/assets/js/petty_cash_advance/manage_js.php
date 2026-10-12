<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
(function ($) {
    "use strict";

    // Filters posted to the server side table alongside the DataTables request
    var pcaServerParams = {
        "from_date":  'input[name="from_date"]',
        "to_date":    'input[name="to_date"]',
        "department": "[name='department[]']",
        "priority":   "[name='priority[]']",
        "status":     "[name='status[]']"
    };

    // initDataTable(selector, url, notsearchable, notsortable, fnServerParams, defaultOrder)
    // Column 9 is Options: nothing to search or sort there.
    initDataTable('.table-petty_cash_advances', window.location.href, [9], [9], pcaServerParams, [7, 'desc']);

    var table = $('.table-petty_cash_advances');

    $.each(pcaServerParams, function (i, selector) {
        $('select' + selector).on('change', function () {
            table.DataTable().ajax.reload(null, false).columns.adjust();
        });
    });

    $('input[name="from_date"], input[name="to_date"]').on('change', function () {
        table.DataTable().ajax.reload(null, false).columns.adjust();
    });
})(jQuery);
</script>
