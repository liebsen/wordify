<?php 

/*
 * This file is part of the TBOC Refocus project
 *
 * Copyright (c) 2018 Martin Frith
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://bitbucket.com/marsvieyra/sandbox
 *
 */

date_default_timezone_set("EST");

require __DIR__ . "/../vendor/autoload.php";

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(40,10,"This is only a test");
header("Content-type:application/pdf");
//header("Content-Disposition:attachment;filename='example_001.pdf'");
/*
header('Content-Description: File Transfer'); 
header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment; filename="example_001.pdf"'); 
header('Content-Transfer-Encoding: binary'); */

$pdf->Output(null, 'refocus.pdf');
