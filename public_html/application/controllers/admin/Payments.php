<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Invoices_model $invoices_model
 * @property Payments_model $payments_model
 */
class Payments extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('payments_model');
    }

    public function batch_payment_modal() {
        $this->load->model('invoices_model');
        $data['invoices'] = $this->invoices_model->get_unpaid_invoices();
        $data['customers'] = $this->db->select('userid,' . get_sql_select_client_company())
            ->where_in('userid', collect($data['invoices'])->pluck('clientid')->toArray())
            ->get(db_prefix() . 'clients')->result();
        $this->load->view('admin/payments/batch_payment_modal', $data);
    }

	public function add_batch_payment()
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		if (staff_cant('create', 'payments')) {
			access_denied('Create Payment');
		}

        $is_ajax = $this->input->is_ajax_request();
        $post    = $this->input->post();

        // Handle shared cheque attachment — upload once, pass tmp path to model
        if (!empty($_FILES['shared_cheque_attachment']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['shared_cheque_attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'gif'];
            if (in_array($ext, $allowed)) {
                $post['_shared_cheque_tmp'] = $_FILES['shared_cheque_attachment']['tmp_name'];
                $post['_shared_cheque_ext'] = $ext;
            }
        }

		$totalAdded = $this->payments_model->add_batch_payment($post);

        if ($is_ajax) {
            if ($totalAdded > 0) {
                echo json_encode(['success' => true, 'message' => _l('batch_payment_added_successfully', $totalAdded), 'count' => $totalAdded]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No payments were added. Please check amounts and payment modes.']);
            }
            die;
        }

        if ($totalAdded > 0) {
            set_alert('success', _l('batch_payment_added_successfully', $totalAdded));
            if (staff_can('view', 'payments') || staff_can('view_own', 'payments')) {
                return redirect(admin_url('payments'));
            }
        }
        return redirect(admin_url('invoices'));
	}

    /* In case if user go only on /payments */
    public function index()
    {
        $this->list_payments();
    }

    public function list_payments()
    {
        if (staff_cant('view', 'payments')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('payments');
        }

        $data['title'] = _l('payments');
        $this->load->view('admin/payments/manage', $data);
    }

    public function table($clientid = '')
    {
        if (staff_cant('view', 'payments')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            ajax_access_denied();
        }

        $this->app->get_table_data('payments', [
            'clientid' => $clientid,
        ]);
    }

    /* Update payment data */
    public function payment($id = '')
    {
        if (staff_cant('view', 'payments')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('payments');
        }

        if (!$id) {
            redirect(admin_url('payments'));
        }

        if ($this->input->post()) {
            if (staff_cant('edit', 'payments')) {
                access_denied('Update Payment');
            }
            // Block edit if soft-deleted
            $existing_payment = $this->payments_model->get($id);
            if (!$existing_payment || !empty($existing_payment->is_deleted)) {
                set_alert('danger', 'This payment has been deleted and cannot be edited.');
                redirect(admin_url('payments'));
            }
            $success = $this->payments_model->update($this->input->post(), $id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('payment')));
            }
            redirect(admin_url('payments/payment/' . $id));
        }

        $payment = $this->payments_model->get($id);

        if (!$payment) {
            // Try to fetch even if soft-deleted (for reference display)
            $this->db->select('*,' . db_prefix() . 'invoicepaymentrecords.id as paymentid');
            $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'invoicepaymentrecords.paymentmode', 'left');
            $this->db->where(db_prefix() . 'invoicepaymentrecords.id', $id);
            $payment = $this->db->get(db_prefix() . 'invoicepaymentrecords')->row();
            if (!$payment) {
                show_404();
            }
        }

        // Show deleted notice on view page
        if (!empty($payment->is_deleted)) {
            set_alert('warning', 'This payment has been deleted. It is shown for reference only and cannot be edited.');
        }

        $this->load->model('invoices_model');
        $payment->invoice = $this->invoices_model->get($payment->invoiceid);
        $template_name    = 'invoice_payment_recorded_to_customer';

        $data = prepare_mail_preview_data($template_name, $payment->invoice->clientid);

        $data['payment'] = $payment;
        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true, true);

        $i = 0;
        foreach ($data['payment_modes'] as $mode) {
            if ($mode['active'] == 0 && $data['payment']->paymentmode != $mode['id']) {
                unset($data['payment_modes'][$i]);
            }
            $i++;
        }

        $data['title'] = _l('payment_receipt') . ' - ' . format_invoice_number($data['payment']->invoiceid);
        $this->load->view('admin/payments/payment', $data);
    }

    /**
     * Generate payment pdf
     * @since  Version 1.0.1
     * @param  mixed $id Payment id
     */
    public function pdf($id)
    {
        if (staff_cant('view', 'payments')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('View Payment');
        }

        $payment = $this->payments_model->get($id);

        if (staff_cant('view', 'payments')
            && staff_cant('view_own', 'invoices')
            && !user_can_view_invoice($payment->invoiceid)) {
            access_denied('View Payment');
        }

        $this->load->model('invoices_model');
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);

        try {
            $paymentpdf = payment_pdf($payment);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $paymentpdf->Output(mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid)) . '.pdf', $type);
    }

    /**
     * Send payment manually to customer contacts
     * @since  2.3.2
     * @param  mixed $id payment id
     * @return mixed
     */
    public function send_to_email($id)
    {
        if (staff_cant('view', 'payments')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('Send Payment');
        }

        $payment = $this->payments_model->get($id);

        if (staff_cant('view', 'payments')
            && staff_cant('view_own', 'invoices')
            && !user_can_view_invoice($payment->invoiceid)) {
            access_denied('Send Payment');
        }

        $this->load->model('invoices_model');
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);
        set_mailing_constant();

        $paymentpdf = payment_pdf($payment);
        $filename   = mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid), 'UTF-8') . '.pdf';

        $attach = $paymentpdf->Output($filename, 'S');

        $sent    = false;
        $sent_to = $this->input->post('sent_to');

        if (is_array($sent_to) && count($sent_to) > 0) {
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {
                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template('invoice_payment_recorded_to_customer', (array) $contact, $payment->invoice_data, false, $payment->paymentid);

                    $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $filename,
                            'type'       => 'application/pdf',
                        ]);


                    if (get_option('attach_invoice_to_payment_receipt_email') == 1) {
                        $invoice_number = format_invoice_number($payment->invoiceid);
                        set_mailing_constant();
                        $pdfInvoice           = invoice_pdf($payment->invoice_data);
                        $pdfInvoiceAttachment = $pdfInvoice->Output($invoice_number . '.pdf', 'S');

                        $template->add_attachment([
                            'attachment' => $pdfInvoiceAttachment,
                            'filename'   => str_replace('/', '-', $invoice_number) . '.pdf',
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        $sent = true;
                    }
                }
            }
        }

        // In case client use another language
        load_admin_language();
        set_alert($sent ? 'success' : 'danger', _l($sent ? 'payment_sent_successfully' : 'payment_sent_failed'));

        redirect(admin_url('payments/payment/' . $id));
    }

    /* Delete payment */
    public function delete($id)
    {
        if (staff_cant('delete', 'payments')) {
            access_denied('Delete Payment');
        }
        if (!$id) {
            redirect(admin_url('payments'));
        }
        $response = $this->payments_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment_lowercase')));
        }
        redirect(admin_url('payments'));
    }
}
