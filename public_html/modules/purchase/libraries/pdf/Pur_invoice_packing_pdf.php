<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/pdf/App_pdf.php');

/**
 * Invoice & Packing List PDF.
 *
 * Mirrors Purchase_invoice_pdf so this document renders through the same App_pdf
 * pipeline as every other purchase PDF - identical base font, font size, margins
 * and image scale.
 */
class Pur_invoice_packing_pdf extends App_pdf
{
    protected $pur_invoice;

    public function __construct($pur_invoice)
    {
        $pur_invoice                          = hooks()->apply_filters('pur_invoice_packing_html_pdf_data', $pur_invoice);
        $GLOBALS['pur_invoice_packing_pdf']   = $pur_invoice;

        parent::__construct();

        $this->pur_invoice = $pur_invoice;

        $this->SetTitle('pur_invoice_packing');
        # Don't remove this line - important for the PDF layout
        $this->pur_invoice = $this->fix_editor_html($this->pur_invoice);
    }

    public function prepare()
    {
        $this->set_view_vars('pur_invoice', $this->pur_invoice);

        return $this->build();
    }

    protected function type()
    {
        return 'pur_invoice_packing';
    }

    protected function file_path()
    {
        return APP_MODULES_PATH . '/purchase/views/invoices/pur_invoice_packing_pdf.php';
    }
}
