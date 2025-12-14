<?php
session_start();
include('db.php');
date_default_timezone_set("Asia/Manila");

// Only accept POST with reportDate
if (!isset($_POST['reportDate']) || empty($_POST['reportDate'])) {
    die("No date selected");
}

$reportDate = $_POST['reportDate'];

$stmt = $pdo->prepare("SELECT * FROM requests WHERE DATE(created_at) = ? ORDER BY created_at ASC");
$stmt->execute([$reportDate]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/vendor/autoload.php';

$pdf = new \FPDF();
$pdf->AddPage();

// Header
$pdf->Image(__DIR__ . '/fatimalogo.jpg', 15, 8, 20);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 6, "Our Lady of Fatima University Antipolo", 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, "Office of the Registrar", 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, "General Queueing Report - Registrar's Office", 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);

// FIXED: Ensure reportDate is valid before strtotime
$dateText = '';
if (!empty($reportDate) && strtotime($reportDate) !== false) {
    $dateText = date("F d, Y", strtotime($reportDate));
} else {
    $dateText = "Invalid Date";
}

$pdf->Cell(0, 10, "Date: " . $dateText, 0, 1, 'C');
$pdf->Ln(5);

// Analytics
$totalRequests = count($rows);
$statusCounts = [];
$docCounts = [];
$issues = [];
$hourRanges = [
    '08:00-10:00 AM'=>0,'10:00-12:00 PM'=>0,'12:00-02:00 PM'=>0,'02:00-04:00 PM'=>0,
    '04:00-06:00 PM'=>0,'06:00-08:00 PM'=>0,'08:00-10:00 PM'=>0,'10:00 PM-12:00 AM'=>0
];

foreach($rows as $row){
    $status = $row['status'] ?? 'Unknown';
    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

    $docs = explode(",", $row['documents'] ?? '');
    foreach($docs as $doc){
        $doc = trim($doc);
        if($doc) $docCounts[$doc] = ($docCounts[$doc] ?? 0) +1;
    }

    if(!empty($row['decline_reason'])) $issues[] = $row['decline_reason'];

    // FIXED: Only call strtotime if processing_start exists and not null
    if(!empty($row['processing_start'])){
        $ts = strtotime($row['processing_start']);
        if($ts!==false){
            $h = (int)date('H',$ts);
            if($h>=8 && $h<10) $hourRanges['08:00-10:00 AM']++;
            elseif($h>=10 && $h<12) $hourRanges['10:00-12:00 PM']++;
            elseif($h>=12 && $h<14) $hourRanges['12:00-02:00 PM']++;
            elseif($h>=14 && $h<16) $hourRanges['02:00-04:00 PM']++;
            elseif($h>=16 && $h<18) $hourRanges['04:00-06:00 PM']++;
            elseif($h>=18 && $h<20) $hourRanges['06:00-08:00 PM']++;
            elseif($h>=20 && $h<22) $hourRanges['08:00-10:00 PM']++;
            else $hourRanges['10:00 PM-12:00 AM']++;
        }
    }
}

arsort($hourRanges);
$peakHoursText = "No activity";
foreach($hourRanges as $range=>$count){
    if($count>0){
        // FIXED: use mb_convert_encoding instead of utf8_decode
        $peakHoursText = mb_convert_encoding("$range - $count request(s)", 'ISO-8859-1', 'UTF-8');
        break;
    }
}

// Write analytics
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,"I. Total Requests Processed",0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,8,"Total Requests: $totalRequests",0,1);
foreach($statusCounts as $status=>$count){
    $pdf->Cell(0,8,"- $status: $count",0,1);
}
$pdf->Ln(3);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,"II. Requests by Document Type",0,1);
$pdf->SetFont('Arial','',11);
if(!empty($docCounts)){
    foreach($docCounts as $doc=>$count){
        $pdf->Cell(0,8,"- $doc: $count",0,1);
    }
}else $pdf->Cell(0,8,"No document requests logged.",0,1);
$pdf->Ln(3);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,"III. Peak Hours",0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,8,$peakHoursText,0,1);
$pdf->Ln(3);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,"IV. Issues / Decline Reasons",0,1);
$pdf->SetFont('Arial','',11);
if(!empty($issues)){
    foreach($issues as $reason){
        // FIXED: replace utf8_decode
        $pdf->MultiCell(0,8,"- ".mb_convert_encoding($reason,'ISO-8859-1','UTF-8'));
    }
}else $pdf->Cell(0,8,"No issues logged.",0,1);
$pdf->Ln(3);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,"V. Summary",0,1);
$pdf->SetFont('Arial','',11);

$summaryText = "This report summarizes all requests filed and processed by the Registrar's Office on $dateText. It includes breakdowns of request statuses, document types, activity times, and issues encountered.";
$pdf->MultiCell(0,8,mb_convert_encoding($summaryText,'ISO-8859-1','UTF-8'));

// ====== DETAILED TABLE ======
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"Detailed Request List",0,1,'C');
$pdf->Ln(3);

// Table header
$pdf->SetFont('Arial','B',10);
$colWidths = [10, 40, 25, 25, 60, 30];
$headers = ['#','Name','Student No.','Section','Documents','Status'];
foreach($headers as $i=>$header){
    $pdf->Cell($colWidths[$i],10,$header,1,0,'C');
}
$pdf->Ln();

$pdf->SetFont('Arial','',9);
$lineHeight = 5;
$i=1;

foreach($rows as $row){
    $name = trim(($row['first_name'] ?? '')." ".($row['last_name'] ?? ''));
    $studentNo = $row['student_number'] ?? '-';
    $section = $row['section'] ?? '-';
    $documents = $row['documents'] ?? '-';
    $status = $row['status'] ?? '-';

    // calculate row height
    $lines = [
        NbLines($pdf,$colWidths[0],$i,$lineHeight),
        NbLines($pdf,$colWidths[1],$name,$lineHeight),
        NbLines($pdf,$colWidths[2],$studentNo,$lineHeight),
        NbLines($pdf,$colWidths[3],$section,$lineHeight),
        NbLines($pdf,$colWidths[4],$documents,$lineHeight),
        NbLines($pdf,$colWidths[5],$status,$lineHeight)
    ];
    $rowHeight = $lineHeight*max($lines);

    if($pdf->GetY()+$rowHeight>270) $pdf->AddPage();

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Draw cells
    $pdf->Rect($x,$y,$colWidths[0],$rowHeight);
    $pdf->Rect($x+$colWidths[0],$y,$colWidths[1],$rowHeight);
    $pdf->Rect($x+$colWidths[0]+$colWidths[1],$y,$colWidths[2],$rowHeight);
    $pdf->Rect($x+$colWidths[0]+$colWidths[1]+$colWidths[2],$y,$colWidths[3],$rowHeight);
    $pdf->Rect($x+$colWidths[0]+$colWidths[1]+$colWidths[2]+$colWidths[3],$y,$colWidths[4],$rowHeight);
    $pdf->Rect($x+$colWidths[0]+$colWidths[1]+$colWidths[2]+$colWidths[3]+$colWidths[4],$y,$colWidths[5],$rowHeight);

    // Print content
    $pdf->SetXY($x,$y);
    $pdf->MultiCell($colWidths[0],$lineHeight,$i++,0,'C');
    $pdf->SetXY($x+$colWidths[0],$y);
    $pdf->MultiCell($colWidths[1],$lineHeight,$name,0);
    $pdf->SetXY($x+$colWidths[0]+$colWidths[1],$y);
    $pdf->MultiCell($colWidths[2],$lineHeight,$studentNo,0);
    $pdf->SetXY($x+$colWidths[0]+$colWidths[1]+$colWidths[2],$y);
    $pdf->MultiCell($colWidths[3],$lineHeight,$section,0);
    $pdf->SetXY($x+$colWidths[0]+$colWidths[1]+$colWidths[2]+$colWidths[3],$y);
    $pdf->MultiCell($colWidths[4],$lineHeight,$documents,0);
    $pdf->SetXY($x+$colWidths[0]+$colWidths[1]+$colWidths[2]+$colWidths[3]+$colWidths[4],$y);
    $pdf->MultiCell($colWidths[5],$lineHeight,$status,0);

    $pdf->SetXY($x,$y+$rowHeight);
}

// Output PDF
$pdf->Output("I","Registrar_Report_".$reportDate.".pdf");

// Helper
function NbLines($pdf,$w,$txt,$lineHeight=5){
    if(!$txt) return 1;
    $words = explode(' ',$txt);
    $lines=1;
    $width=0;
    foreach($words as $word){
        $wordWidth=$pdf->GetStringWidth($word.' ');
        if($width+$wordWidth>$w){
            $lines++;
            $width=$wordWidth;
        }else $width+=$wordWidth;
    }
    return $lines;
}
?>