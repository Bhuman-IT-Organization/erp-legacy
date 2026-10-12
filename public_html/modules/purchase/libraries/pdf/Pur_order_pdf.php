<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/pdf/App_pdf.php');

class Pur_order_pdf extends App_pdf
{
    protected $pur_order;
    protected $po_number = '';

    public function __construct($pur_order)
    {
        $pur_order                = hooks()->apply_filters('request_html_pdf_data', $pur_order);
        $GLOBALS['pur_order_pdf'] = $pur_order;

        parent::__construct();

        $this->pur_order = $pur_order;

        // Extract PO number for footer
        $CI =& get_instance();
        if(isset($CI->uri) && $CI->uri->segment(4)){
            $po = $CI->db->select('pur_order_number')->where('id', $CI->uri->segment(4))->get(db_prefix().'pur_orders')->row();
            if($po) $this->po_number = $po->pur_order_number;
        }

        $this->SetTitle(_l('pur_order'));
        // Set bottom margin to make room for footer
        $this->SetAutoPageBreak(true, 45);
        # Don't remove these lines - important for the PDF layout
        $this->pur_order = $this->fix_editor_html($this->pur_order);
    }

    public function Footer()
    {
        // Created By / Printed By - always above footer text
        $CI =& get_instance();
        $created_by = '';
        $printed_by = get_staff_full_name(get_staff_user_id());
        if(isset($CI->uri) && $CI->uri->segment(4)){
            $po_row = $CI->db->select('addedfrom')->where('id', $CI->uri->segment(4))->get(db_prefix().'pur_orders')->row();
            if($po_row) $created_by = get_staff_full_name($po_row->addedfrom);
        }

        $this->SetY(-38);
        $this->SetFont($this->get_font_name(), '', 8);
        $this->SetTextColor(0, 0, 0);
        $page_width = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $half = $page_width / 2;

        // Left side: Created By and Printed By (two lines)
        $y_pos = $this->GetY();
        $this->Cell($half, 5, 'Created By: ' . $created_by, 0, 1, 'L');
        $this->Cell($half, 5, 'Printed By: ' . $printed_by, 0, 1, 'L');

        // Right side: Computer Generated text (aligned to same Y position)
        $this->SetY($y_pos);
        $this->SetX($this->getMargins()['left'] + $half);
        $this->SetFont($this->get_font_name(), 'I', 8);
        $this->MultiCell($half, 10, 'Computer Generated, No Signature Required', 0, 'R');

        // Document info line
        $this->SetFont($this->get_font_name(), '', 8);
        $this->SetY(-20);
        $page_text = 'Doc:' . $this->po_number . '          Date:' . date('d.m.Y H:i:s') . '          Page: ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
        $this->Cell(0, 8, $page_text, 0, 0, 'C');
    }

    public function prepare()
    {
        $this->set_view_vars('pur_order', $this->pur_order);

        return $this->build();
    }

    protected function type()
    {
        return 'pur_order';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_requestpdf.php';
        $actualPath = APP_MODULES_PATH . '/purchase/views/purchase_order/pur_orderpdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}