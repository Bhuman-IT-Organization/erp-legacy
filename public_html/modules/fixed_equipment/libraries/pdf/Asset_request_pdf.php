<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/pdf/App_pdf.php');

/**
 * Asset Code Request PDF.
 *
 * Mirrors modules/purchase/libraries/pdf/Request_quotation_pdf.php so the asset
 * request renders through the exact same App_pdf pipeline as the RFQ / PR / PO
 * documents. This is what guarantees an identical look and feel: App_pdf applies
 * the configured base font (pdf_font) and size (pdf_font_size), the TCPDF default
 * margins, the 1.53 image scale used for the logo and JPEG quality 100. Building
 * the PDF with a raw TCPDF instance instead produced a different base font and
 * image scale, which is why the text and logo previously looked off.
 */
class Asset_request_pdf extends App_pdf
{
    protected $asset_request;

    public function __construct($asset_request)
    {
        $asset_request                 = hooks()->apply_filters('asset_request_html_pdf_data', $asset_request);
        $GLOBALS['asset_request_pdf']  = $asset_request;

        parent::__construct();

        $this->asset_request = $asset_request;

        $this->SetTitle('asset_request');
        # Don't remove this line - important for the PDF layout
        $this->asset_request = $this->fix_editor_html($this->asset_request);
    }

    public function prepare()
    {
        $this->set_view_vars('asset_request', $this->asset_request);

        return $this->build();
    }

    protected function type()
    {
        return 'asset_request';
    }

    protected function file_path()
    {
        return APP_MODULES_PATH . '/fixed_equipment/views/asset_request_form/asset_requestpdf.php';
    }
}
