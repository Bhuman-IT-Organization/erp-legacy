<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
(function ($) {
    "use strict";

    // ── Section 3: item repeater ─────────────────────────────────────────────
    // Row indexes must stay unique for the life of the page. Reusing the row count
    // would collide after a delete: remove row 1 of 2 and the next add would reuse
    // index 1, overwriting the surviving row on submit.
    var nextRowIndex = $('#pca-items tbody .pca-item-row').length;

    function recalcItemsTotal() {
        var total = 0;

        $('#pca-items tbody .pca-item-amount').each(function () {
            var value = parseFloat($(this).val());
            if (!isNaN(value)) {
                total += value;
            }
        });

        $('#pca-items-total').text(total.toFixed(2));
    }

    $('#pca-add-item').on('click', function () {
        var template = $('#pca-item-template').html();

        if (!template) {
            return;
        }

        var $row = $(template.replace(/__INDEX__/g, nextRowIndex));
        nextRowIndex++;

        $('#pca-items tbody').append($row);

        // render_select() emits a bootstrap-select, which only styles itself on init.
        // A cloned <select> would otherwise render as an unstyled native dropdown.
        if ($.fn.selectpicker) {
            $row.find('select.selectpicker').selectpicker();
        }

        recalcItemsTotal();
    });

    $(document).on('click', '.pca-remove-item', function () {
        $(this).closest('.pca-item-row').remove();
        recalcItemsTotal();
    });

    // Delegated so rows added after load are covered too
    $(document).on('input change', '.pca-item-amount', recalcItemsTotal);

    // Existing rows were rendered server side, so total them on load
    recalcItemsTotal();
})(jQuery);

// ── Attachments ────────────────────────────────────────────────────────────
function pca_preview_file(id, rel_id) {
    "use strict";
    $('#pca_file_data').empty();
    $('#pca_file_data').load(admin_url + 'purchase/file_petty_cash_advance/' + id + '/' + rel_id, function (response, status, xhr) {
        if (status === 'error') {
            alert_float('danger', xhr.statusText);
        }
    });
}

function pca_close_preview() {
    "use strict";
    $('._project_file').modal('hide');
}

function pca_delete_attachment(id) {
    "use strict";
    if (!confirm_delete()) {
        return;
    }

    requestGet('purchase/delete_petty_cash_advance_attachment/' + id).done(function () {
        $('#pca_pv_file').find('[data-attachment-id="' + id + '"]').remove();
    }).fail(function (error) {
        alert_float('danger', error.responseText);
    });
}
</script>
