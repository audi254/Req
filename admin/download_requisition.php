<?php
ob_start();
require_once '../config/database.php';
require_once '../lib/TCPDF-main/tcpdf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

check_permission('admin');

if (!isset($_GET['id'])) {
    die('Requisition ID is required');
}

$requisition_id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name, u.department, u.email, u.phone FROM requisitions r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$requisition_id]);
$requisition = $stmt->fetch();

if (!$requisition) {
    die('Requisition not found');
}


$stmt = $pdo->prepare("SELECT ri.*, i.item_code, i.item_name, i.unit_of_measure, i.unit_price FROM requisition_items ri JOIN items i ON ri.item_id = i.id WHERE ri.requisition_id = ?");
$stmt->execute([$requisition_id]);
$items = $stmt->fetchAll();

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('KICD Requisition System');
$pdf->SetAuthor('KICD');
$pdf->SetTitle('Requisition Form - ' . $requisition['requisition_number']);
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(0, 0, 139);
$pdf->Cell(0, 10, 'Kenya Institute of Curriculum Development (KICD)', 0, 1, 'C');
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 10, 'REQUISITION FORM', 0, 1, 'C');
$pdf->Ln(5);


$pdf->SetFont('times', 'B', 12);
$pdf->SetTextColor(0, 0, 139);
$pdf->Cell(0, 8, 'Section A: Requisition Details', 0, 1);
$pdf->SetFont('times', '', 12);
$pdf->SetTextColor(0, 0, 0); 
$pdf->Cell(60, 6, 'Requisition No.:', 0, 0);
$pdf->Cell(0, 6, $requisition['requisition_number'], 0, 1);
$pdf->Cell(60, 6, 'Date of Request:', 0, 0);
$pdf->Cell(0, 6, date('d / m / Y', strtotime($requisition['created_at'] ?? 'now')), 0, 1);
$pdf->Cell(60, 6, 'Department/Section:', 0, 0);
$pdf->Cell(0, 6, $requisition['department'], 0, 1);
$pdf->Cell(60, 6, 'Requested By (Name):', 0, 0);
$pdf->Cell(0, 6, $requisition['first_name'] . ' ' . $requisition['last_name'], 0, 1);
$pdf->Cell(60, 6, 'Contact (Email/Phone):', 0, 0);
$pdf->Cell(0, 6, $requisition['email'], 0, 1);
$pdf->Ln(5);


$pdf->SetFont('times', 'B', 12);
$pdf->SetTextColor(0, 0, 139); 
$pdf->Cell(0, 8, 'Section B: Items Requested', 0, 1);

$pdf->SetFont('times', 'B', 12);
$header = ['Description of Item', 'Quantity', 'Price per Unit', 'Total cost'];
$w = [77, 25, 35, 35];

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
}
$pdf->Ln();

$pdf->SetFont('times', '', 12);
foreach ($items as $item) {
    $pdf->Cell($w[0], 6, $item['item_name'], 1);
    $pdf->Cell($w[1], 6, $item['quantity'], 1, 0, 'C');
    $pdf->Cell($w[2], 6, number_format($item['unit_price'] ?? 0, 2), 1, 0, 'R');
    $pdf->Cell($w[3], 6, number_format(($item['quantity'] * ($item['unit_price'] ?? 0)), 2), 1, 0, 'R');
    $pdf->Ln();
}
$pdf->Ln(5);

$total_cost = 0;
foreach ($items as $item) {
    $total_cost += $item['quantity'] * ($item['unit_price'] ?? 0);
}

$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 8, 'Total Cost: KSH ' . number_format($total_cost, 2), 0, 1, 'L');

$pdf->Ln(5);


$pdf->SetFont('times', 'B', 12);
$pdf->SetTextColor(0, 0, 139); 
$pdf->Cell(0, 8, 'Section C: Final Authorization', 0, 1);
$pdf->SetFont('times', '', 12);
$pdf->SetTextColor(0, 0, 0); 
$pdf->Cell(0, 6, 'Authorized By (Director/Deputy Director):', 0, 1);
$pdf->Ln(10);
$pdf->Cell(50, 6, 'Name: ____________________________', 0, 0);
$pdf->Cell(50, 6, 'Signature: _________________________', 0, 0);
$pdf->Cell(50, 6, 'Date: ____ / ____ / ______', 0, 1);

$pdf->Output('Requisition_' . $requisition['requisition_number'] . '.pdf', 'I');
ob_end_flush();
?>
