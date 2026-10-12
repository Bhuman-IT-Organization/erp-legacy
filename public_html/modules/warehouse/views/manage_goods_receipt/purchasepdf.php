<?php defined('BASEPATH') or exit('No direct script access allowed');

// Write the main content (header + items table)
$html = <<<EOF
<div>
$purchase
</div>
EOF;
$pdf->writeHTML($html, true, false, true, false, '');

// Now write the signature block on the LAST page only
$gr = $GLOBALS['goods_receipt'];

$creator_name = '';
if(isset($gr->addedfrom) && $gr->addedfrom > 0){
	$creator_name = get_staff_full_name($gr->addedfrom);
}
$dept_name = '';
if(isset($gr->department) && is_numeric($gr->department) && $gr->department > 0){
	$ci =& get_instance();
	$ci->db->select('name');
	$ci->db->where('departmentid', $gr->department);
	$_dept = $ci->db->get(db_prefix().'departments')->row();
	if($_dept) $dept_name = $_dept->name;
}
$printed_by = get_staff_full_name(get_staff_user_id());

// Check available space on current page
$page_height = $pdf->getPageHeight();
$bottom_margin = 15; // page number area
$footer_block_height = 40; // signature + doc line
$current_y = $pdf->GetY();
$available = $page_height - $current_y - $bottom_margin;

if($available < $footer_block_height){
	// Not enough space - go to next page and place at bottom
	$pdf->AddPage();
	$sig_y = $page_height - $bottom_margin - $footer_block_height;
	$pdf->SetY($sig_y);
} else {
	// Enough space - place at bottom of THIS page (keeps it on one page)
	$sig_y = $page_height - $bottom_margin - $footer_block_height;
	$pdf->SetY($sig_y);
}

$sig_html = '<table style="width:100%;"><tr>';
$sig_html .= '<td style="width:50%;vertical-align:top;">'.$creator_name.'<br><strong>Warehouse In-Charge</strong><br><i style="font-size:7pt;">system generated, signatures not required</i></td>';
$sig_html .= '<td style="width:50%;vertical-align:top;text-align:right;font-style:italic;">'.$dept_name.'<br><strong>Requesting Department</strong><br>Received by: -----------</td>';
$sig_html .= '</tr></table>';

$pdf->SetFont($pdf->get_font_name(), '', 9);
$pdf->writeHTML($sig_html, true, false, true, false, '');

// Document reference line
$pdf->SetFont($pdf->get_font_name(), '', 8);
$left_margin = $pdf->getMargins()['left'];
$right_margin = $pdf->getMargins()['right'];
$line_y = $pdf->GetY() + 2;
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line($left_margin, $line_y, $pdf->getPageWidth() - $right_margin, $line_y);
$pdf->SetY($line_y);
$doc_line = 'Doc: '.$gr->goods_receipt_code.'    Date: '._d($gr->date_c).'    Printed by: '.$printed_by;
$pdf->Cell(0, 5, $doc_line, 0, 0, 'C');
