<?php
require_once '../config/database.php';
error_reporting(E_ALL & ~E_DEPRECATED);
require_once '../lib/TCPDF-main/tcpdf.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid requisition ID.');
}

$requisition_id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.last_name, u.email, u.department
    FROM requisitions r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$requisition_id, $user['id']]);
$requisition = $stmt->fetch();

if (!$requisition) {
    die('Requisition not found or access denied.');
}

$stmt = $pdo->prepare("
    SELECT ri.quantity, i.unit_price, (ri.quantity * i.unit_price) as total_price, i.item_name, i.item_code
    FROM requisition_items ri
    JOIN items i ON ri.item_id = i.id
    WHERE ri.requisition_id = ?
");
$stmt->execute([$requisition_id]);
$items = $stmt->fetchAll();


$total = 0;
foreach ($items as $item) {
    $total += $item['total_price'];
}


$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('KICD Requisition System');
$pdf->SetTitle('Requisition ' . $requisition['requisition_number']);
$pdf->SetSubject('Requisition Details');


$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(15, 15, 15);


$pdf->AddPage();


$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Kenya Institute of Curriculum Development (KICD)', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'REQUISITION FORM', 0, 1, 'L');
$pdf->Ln(5);


$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Section A: Requisition Details', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$fields = [
    'Requisition No.' => $requisition['requisition_number'],
    'Date of Request' => date('d / m / Y', strtotime($requisition['created_at'] ?? 'now')),
    'Department/Section' => $requisition['department'],
    'Requested By (Name)' => $requisition['first_name'] . ' ' . $requisition['last_name'],
    'Contact (Email/Phone)' => $requisition['email'],
];

foreach ($fields as $label => $value) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 7, $label . ':', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, $value, 'B', 1);
}

$pdf->Ln(5);


$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Section B: Items Requested', 0, 1, 'L');


$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(15, 8, 'Item No.', 1, 0, 'C');
$pdf->Cell(60, 8, 'Description of Item', 1, 0, 'C');
$pdf->Cell(20, 8, 'Quantity', 1, 0, 'C');
$pdf->Cell(27, 8, 'Price per Unit', 1, 0, 'C');
$pdf->Cell(23, 8, 'Total cost', 1, 1, 'C');


$pdf->SetFont('helvetica', '', 10);
$item_no = 1;
foreach ($items as $item) {
    $pdf->Cell(15, 8, $item_no++, 1, 0, 'C');
    $pdf->Cell(60, 8, $item['item_name'], 1, 0, 'L');
    $pdf->Cell(20, 8, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(27, 8, 'KSh ' . number_format($item['unit_price'] ?? 0, 2), 1, 0, 'R');
    $pdf->Cell(23, 8, 'KSh ' . number_format($item['total_price'] ?? 0, 2), 1, 1, 'R');
}

$pdf->Ln(5);


$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(40, 8, 'Total Cost: KSH ' . number_format($total ?? 0, 2), 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 8, '', 0, 1);

$pdf->Ln(5);


$pdf->Output('requisition_' . $requisition['requisition_number'] . '.pdf', 'D');
exit();
?>
