<?php
require('tcpdf/tcpdf.php');
require('geshi/geshi.php');
?>
<?php
$tmp_filename = $argv[1];
$title = $argv[2];
$json = $argv[3];
$data = json_decode($json, true);
?>
<?php
//CUSTOM HEADER FOR PDF
class MYPDF extends  TCPDF {
		public function Header() {
			$ormargins = $this->getOriginalMargins();
			$headerfont = $this->getHeaderFont();
			$headerdata = $this->getHeaderData();
			if (($headerdata['logo']) AND ($headerdata['logo'] != K_BLANK_IMAGE)) {
				$this->ImageEps($headerdata['logo'], '', '', $headerdata['logo_width']);
				$imgy = $this->getImageRBY();
			} else {
				$imgy = $this->GetY();
			}
			$cell_height = round(($this->getCellHeightRatio() * $headerfont[2]) / $this->getScaleFactor(), 2);
			// set starting margin for text data cell
			if ($this->getRTL()) {
				$header_x = $ormargins['right'] + ($headerdata['logo_width'] * 1.1);
			} else {
				$header_x = $ormargins['left'] + ($headerdata['logo_width'] * 1.1);
			}
			$this->SetTextColor(0, 0, 0);
			// header title
			$this->SetFont($headerfont[0], 'B', $headerfont[2] + 1);
			$this->SetX($header_x);
			$this->Cell(0, $cell_height, $headerdata['title'], 0, 1, '', 0, '', 0);
			// header string
			$this->SetFont($headerfont[0], $headerfont[1], $headerfont[2]);
			$this->SetX($header_x);
			$this->MultiCell(0, $cell_height, $headerdata['string'], 0, '', 0, 1, '', '', true, 0, false);
			// print an ending header line
			$this->SetLineStyle(array('width' => 0.85 / $this->getScaleFactor(), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			$this->SetY((2.835 / $this->getScaleFactor()) + max($imgy, $this->GetY()));
			if ($this->getRTL()) {
				$this->SetX($ormargins['right']);
			} else {
				$this->SetX($ormargins['left']);
			}
			$this->Cell(0, 0, '', 'T', 0, 'C');
		}
		public function Footer() {
			$cur_y = $this->GetY();
			$ormargins = $this->getOriginalMargins();
			$this->SetTextColor(0, 0, 0);
			//set style for cell border
			$line_width = 0.85 / $this->getScaleFactor();
			$this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//print document barcode
			$barcode = $this->getBarcode();
			if (!empty($barcode)) {
				$this->Ln($line_width);
				$barcode_width = round(($this->getPageWidth() - $ormargins['left'] - $ormargins['right'])/3);
				$this->write1DBarcode($barcode, 'C128B', $this->GetX(), $cur_y + $line_width, $barcode_width, (($this->getFooterMargin() / 3) - $line_width), 0.3, '', '');
			}
			if (empty($this->pagegroups)) {
				$pagenumtxt = $this->l['w_page'].' '.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
			} else {
				$pagenumtxt = $this->l['w_page'].' '.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
			}
			$this->SetY($cur_y);
			
			//print date
			$this->SetX($ormargins['right']);
			$this->Cell(0, 0, date('d.m.Y'), 'T', 0, 'L');
			
			
			//Print page number
			if ($this->getRTL()) {
				$this->SetX($ormargins['right']);
				$this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
			} else {
				$this->SetX($ormargins['left']);
				$this->Cell(0, 0, $pagenumtxt, 'T', 0, 'R');
			}
		}
}
?>
<?php
$pdf = new MYPDF("P", "mm", "A4", true, 'UTF-8', false);
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Boris Fritscher');
$pdf->SetTitle($title);
// remove default header/footer
//$pdf->setPrintHeader(false);
//$pdf->setPrintFooter(false);
// set default header data
//TOOD: CHANGE LOGO
$pdf->SetHeaderData("logo.eps", PDF_HEADER_LOGO_WIDTH, $title . ' Corrigé', "SQL");
// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
// Add new page & use the base PDF as template
$pdf->AddPage();
//test of schema (but how to handle TP with multiple schema?)
//$pdf->Image( IMAGES . 'sql' . DS . 'schema_' . strtolower($assignment_name) . '.png');
//$pdf->lastPage();
//$pdf->AddPage();
$i=0;
foreach($data as $question){
	$i++;
	$pdf->SetFont("Helvetica", "", 10);
	$html = '<h3>SQL &gt; requête ' . $i . ' &gt; solution';
	/*
	if(preg_match('/JOIN/ims', $question['sql'])){
		$html .= " (variante JOIN)";		
	}
	*/
	$html .= '</h3>';
	@$pdf->writeHTML($html, true, false, true, false, '');
	$html = $question['text'] . '<br/>Schéma: ' . $question['db_schema'];
	@$pdf->writeHTML($html, true, false, true, false, '');
	$pdf->SetY($pdf->GetY() + 2, false);
	$geshi = new GeSHi($question['sql'], "sql");
	$html = $geshi->parse_code();
	@$pdf->writeHTML($html, true, false, true, false, '');
	$pdf->SetY($pdf->GetY() + 2, false);	
	
	//data table
	/*
	if(!$hidedata){
		
		$pdf->SetFont("Helvetica", "", 8);
		$html = '<table border="1" cellspacing="0" cellpadding="2"><tr  bgcolor="#cccccc">';
		foreach($question['data']->header as $th){
				$html .= '<th style="font-weight:bold;">'.$th.'</th>';				
		}
		$html .= '</tr>';
			foreach($question['data']->content as $tr){
				$html .= '<tr>';
				foreach($tr as $td){
					$html .= "<td>$td</td>";
				}
				$html .= '</tr>';
			}
		$html .= '</table>';
		$pdf->writeHTML($html, true, false, true, false, '');
	}
	*/
}
$pdf->Output($tmp_filename, 'F');
?>