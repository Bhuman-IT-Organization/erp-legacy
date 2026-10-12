<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
(function ($) {
    "use strict";

    // ── Section 4: record an approval or rejection ───────────────────────────
    $(document).on('click', '.pca-approval-action', function () {
        var $button = $(this);
        var $cell   = $button.closest('td');
        var id      = $('#pca-approvals').data('advance-id');

        if (!id || $button.prop('disabled')) {
            return;
        }

        // Guard against a double click posting twice. The unique key on
        // (advance_id, role) would collapse them anyway, but this avoids the second
        // request entirely.
        $cell.find('.pca-approval-action').prop('disabled', true);

        $.post(admin_url + 'purchase/petty_cash_advance_approve', {
            advance_id: id,
            role:   $button.data('role'),
            status: $button.data('status'),
            note:   $cell.find('.pca-approval-note').val() || ''
        }).done(function (response) {
            var payload = typeof response === 'string' ? JSON.parse(response) : response;

            if (payload.success) {
                alert_float('success', payload.message);
                // Reloaded rather than patched in place: one decision can change the
                // whole request's status and the Approved By box in the header.
                window.location.reload();
                return;
            }

            alert_float('danger', payload.message);
            $cell.find('.pca-approval-action').prop('disabled', false);
        }).fail(function (error) {
            alert_float('danger', error.statusText);
            $cell.find('.pca-approval-action').prop('disabled', false);
        });
    });
})(jQuery);

// ── Attachments: shared with the edit form ─────────────────────────────────
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
