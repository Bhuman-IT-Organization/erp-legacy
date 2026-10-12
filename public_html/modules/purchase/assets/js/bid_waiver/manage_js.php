<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
(function ($) {
    "use strict";

    // Filters are posted to the server side table alongside the DataTables request.
    var bidWaiverServerParams = {
        "from_date":  'input[name="from_date"]',
        "to_date":    'input[name="to_date"]',
        "department": "[name='department[]']",
        "status":     "[name='status[]']"
    };

    // initDataTable(selector, url, notsearchable, notsortable, fnServerParams, defaultOrder)
    // Column 9 is the options column: nothing to search or sort there.
    initDataTable('.table-bid_waivers', window.location.href, [9], [9], bidWaiverServerParams, [7, 'desc']);

    var table = $('.table-bid_waivers');

    $.each(bidWaiverServerParams, function (i, selector) {
        $('select' + selector).on('change', function () {
            table.DataTable().ajax.reload(null, false).columns.adjust();
        });
    });

    $('input[name="from_date"], input[name="to_date"]').on('change', function () {
        table.DataTable().ajax.reload(null, false).columns.adjust();
    });
})(jQuery);
</script>
