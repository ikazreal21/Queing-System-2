<?php
session_start();
include('db.php');
date_default_timezone_set("Asia/Manila");

// Get queue number from URL
$queue = $_GET['queue'] ?? null;

if (!$queue) {
    die("Missing required data for queue stub.");
}

// Fetch request info from DB
$stmt = $pdo->prepare("SELECT * FROM requests WHERE queueing_num = ?");
$stmt->execute([$queue]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Queue info not found.");
}

// Include FPDF
require __DIR__ . '/vendor/autoload.php';

class PDF extends FPDF {
    function SetDash($black=null,$white=null){
        if($black!==null)
            $s=sprintf('[%.3F %.3F] 0 d',$black*$this->k,$white*$this->k);
        else
            $s='[] 0 d';
        $this->_out($s);
    }
}

// Custom page size for ticket style
$pdf = new PDF('P','mm',[80,200]);
$pdf->AddPage();

// HEADER
$pdf->Image(__DIR__ . '/fatimalogo.jpg', 30, 6, 20); // logo centered

// --- PUSH ALL CONTENTS DOWN ---
$pdf->SetY(30); // start everything else at Y = 50mm (adjust to push further if needed)

// University & Office
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, "OUR LADY OF FATIMA UNIVERSITY", 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, "Antipolo City - Office of the Registrar", 0, 1, 'C');
$pdf->Ln(4);

// Title
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, "QUEUE STUB / TICKET", 0, 1, 'C');
$pdf->Ln(2);

// Prominent Queue Number
$pdf->SetFont('Arial','B',24);
$pdf->SetTextColor(0, 140, 69); 
$pdf->Cell(0,15,htmlspecialchars($request['queueing_num']),0,1,'C');
$pdf->SetTextColor(0,0,0); 
$pdf->Ln(2);


// Ticket details
$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Name:',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,htmlspecialchars($request['first_name'].' '.$request['last_name']),0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Position:',0,0);
$pdf->SetFont('Arial','',10);
$position = $request['serving_position'] ?? 1;
$pdf->Cell(0,6,(int)$position,0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Status:',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,htmlspecialchars($request['status']),0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Document:',0,0);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,htmlspecialchars($request['documents']));

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Date:',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,date('F d, Y', strtotime($request['created_at'])),0,1);

// Dashed line for tear-off
$pdf->Ln(5);
$y = $pdf->GetY();
$pdf->SetDash(1,2); // 1mm dash, 2mm gap
$pdf->Line(5,$y,75,$y);
$pdf->SetDash(); // reset
$pdf->Ln(5);

// Footer message
$pdf->SetFont('Arial','I',9);
$pdf->MultiCell(0,5,'Please wait for your turn at the counter. Thank you for your patience.',0,'C');

// Output PDF
$pdf->Output('I', 'QueueStub_'.$queue.'.pdf');
?>
