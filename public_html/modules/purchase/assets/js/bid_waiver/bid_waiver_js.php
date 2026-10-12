<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
(function ($) {
    "use strict";

    // ── Section 4: show the waiver reasons only when they apply ─────────────
    // The reasons are why the bid was waived, so they are only asked for when two or
    // more comparable quotations were NOT obtained - answer No. Answering Yes means
    // the purchase was competitively quoted and there is nothing to waive, so the
    // block is hidden. The single place that decides it is the value compared here;
    // the server side initial state in bid_waivers/bid_waiver.php must match it.
    var OPEN_REASONS_WHEN = '0';

    function toggleWaiverReasons() {
        var answer = $('input[name="two_or_more_quotations"]:checked').val();
        $('#bw-waiver-reasons').toggle(answer === OPEN_REASONS_WHEN);
    }

    $(document).on('change', 'input[name="two_or_more_quotations"]', toggleWaiverReasons);

    // ── Section 3: quotation repeater ───────────────────────────────────────
    // Row indexes must stay unique for the whole life of the page. Reusing the row
    // count would collide after a delete: remove row 1 of 2 and the next add would
    // reuse index 1, overwriting the surviving row on submit.
    var nextRowIndex = $('#bw-quotations tbody .bw-quotation-row').length;

    $('#bw-add-quotation').on('click', function () {
        var template = $('#bw-quotation-template').html();

        if (!template) {
            return;
        }

        var markup = template.replace(/__INDEX__/g, nextRowIndex);
        nextRowIndex++;

        var $row = $(markup);
        $('#bw-quotations tbody').append($row);

        // render_select() emits a bootstrap-select, which only styles itself on init.
        // A cloned <select> would otherwise render as an unstyled native dropdown.
        if ($.fn.selectpicker) {
            $row.find('select.selectpicker').selectpicker();
        }
    });

    $(document).on('click', '.bw-remove-quotation', function () {
        $(this).closest('.bw-quotation-row').remove();
    });

    // Set the initial state on load. Existing rows were rendered server side, so only
    // the reasons panel needs syncing.
    toggleWaiverReasons();
})(jQuery);

// ── Attachments ────────────────────────────────────────────────────────────
function bw_preview_file(id, rel_id) {
    "use strict";
    $('#bid_waiver_file_data').empty();
    $('#bid_waiver_file_data').load(admin_url + 'purchase/file_bid_waiver/' + id + '/' + rel_id, function (response, status, xhr) {
        if (status === 'error') {
            alert_float('danger', xhr.statusText);
        }
    });
}

function bw_close_preview() {
    "use strict";
    $('._project_file').modal('hide');
}

function bw_delete_attachment(id) {
    "use strict";
    if (!confirm_delete()) {
        return;
    }

    requestGet('purchase/delete_bid_waiver_attachment/' + id).done(function () {
        $('#bid_waiver_pv_file').find('[data-attachment-id="' + id + '"]').remove();
    }).fail(function (error) {
        alert_float('danger', error.responseText);
    });
}
</script>
