<?php defined('BASEPATH') or exit('No direct script access allowed');

// Who the form was raised for. The approver chain notifies this person of the outcome
// and resolves direct_manager / head_of_department steps against them, so the
// requestor is used with created_by as the fallback when it was left empty.
$bw_addedfrom = !empty($bid_waiver->requestor) ? $bid_waiver->requestor : $bid_waiver->created_by;
?>
<script>
(function ($) {
    "use strict";

    // ── Approver notifications ───────────────────────────────────────────────
    // send_request_approve() and approve_request() only stash the payload in the
    // session; the mail is actually sent by this call on the next page load. That is
    // the same indirect route every other purchase document uses, and without it
    // nobody is emailed.
    <?php if (isset($send_mail_approve)) { ?>
    var data_send_mail = <?php echo json_encode($send_mail_approve); ?>;
    data_send_mail.rel_id   = <?php echo (int) $bid_waiver->id; ?>;
    data_send_mail.rel_type = 'pur_bid_waiver';
    data_send_mail.addedfrom = <?php echo (int) $bw_addedfrom; ?>;
    data_send_mail.csrf_token_name = $('input[id="csrf_token_name"]').val();
    $.post(admin_url + 'purchase/send_mail', data_send_mail);
    <?php } ?>

    // ── Section 6: record an approval or rejection ───────────────────────────
    $(document).on('click', '.bw-approval-action', function () {
        var $button = $(this);
        var $cell   = $button.closest('td');
        var id      = $('#bw-approvals').data('bid-waiver-id');

        if (!id) {
            return;
        }

        // Guard against a double click posting twice. The unique key on
        // (bid_waiver_id, role) would collapse them anyway, but this avoids the
        // second request entirely.
        if ($button.prop('disabled')) {
            return;
        }

        $cell.find('.bw-approval-action').prop('disabled', true);

        $.post(admin_url + 'purchase/bid_waiver_approve', {
            bid_waiver_id: id,
            role:   $button.data('role'),
            status: $button.data('status'),
            note:   $cell.find('.bw-approval-note').val() || ''
        }).done(function (response) {
            var payload = typeof response === 'string' ? JSON.parse(response) : response;

            if (payload.success) {
                alert_float('success', payload.message);
                // Reloaded rather than patched in place: recording one decision can
                // change the whole form's status and whether the CEO box applies.
                window.location.reload();
                return;
            }

            alert_float('danger', payload.message);
            $cell.find('.bw-approval-action').prop('disabled', false);
        }).fail(function (error) {
            alert_float('danger', error.statusText);
            $cell.find('.bw-approval-action').prop('disabled', false);
        });
    });
})(jQuery);

// ── Approver chain ─────────────────────────────────────────────────────────
// Posts to the same purchase/send_request_approve and purchase/approve_request
// endpoints a Purchase Request and a Purchase Order use, with rel_type
// 'pur_bid_waiver'. approve_request() decides on the server whether the caller is the
// approver the chain is waiting on, so these functions carry no authority of their
// own - hiding the buttons is presentation, the endpoint is the gate.

function bw_send_request_approve() {
    "use strict";

    var data = {};
    data.rel_id    = <?php echo (int) $bid_waiver->id; ?>;
    data.rel_type  = 'pur_bid_waiver';
    data.addedfrom = <?php echo (int) $bw_addedfrom; ?>;

    $('body').append('<div class="dt-loader"></div>');

    $.post(admin_url + 'purchase/send_request_approve', data).done(function (response) {
        response = typeof response === 'string' ? JSON.parse(response) : response;
        $('body').find('.dt-loader').remove();

        if (response.success === true || response.success == 'true') {
            alert_float('success', response.message);
        } else {
            alert_float('warning', response.message);
        }

        window.location.reload();
    });
}

function bw_approve_request() {
    "use strict";
    bw_change_approval_status(2);
}

function bw_deny_request() {
    "use strict";
    bw_change_approval_status(3);
}

function bw_change_approval_status(status) {
    "use strict";

    var data = {};
    data.rel_id   = <?php echo (int) $bid_waiver->id; ?>;
    data.rel_type = 'pur_bid_waiver';
    data.approve  = status;
    data.note     = $('textarea[name="reason"]').val() || '';

    $('body').append('<div class="dt-loader"></div>');

    $.post(admin_url + 'purchase/approve_request/' + data.rel_id, data).done(function (response) {
        response = typeof response === 'string' ? JSON.parse(response) : response;
        $('body').find('.dt-loader').remove();

        if (response.success === true || response.success == 'true') {
            alert_float('success', response.message);
            window.location.reload();
            return;
        }

        // A refusal means the chain is not waiting on this user. Reloaded anyway so the
        // page redraws with whoever it is actually waiting on.
        alert_float('warning', response.message || '<?php echo _l('access_denied'); ?>');
        window.location.reload();
    }).fail(function (error) {
        $('body').find('.dt-loader').remove();
        alert_float('danger', error.statusText);
    });
}

// ── Attachments: shared with the edit form ─────────────────────────────────
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
