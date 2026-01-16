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

check_permission('admin');

$user = get_user_by_id($_SESSION['user_id']);


$stmt = $pdo->query("
    SELECT r.*, u.first_name, u.last_name, u.department
    FROM requisitions r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$requisitions = $stmt->fetchAll();


$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('KICD Requisition System');
$pdf->SetTitle('All Requisitions Report');
$pdf->SetSubject('Requisitions Report');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(15, 15, 15);

$pdf->AddPage();


$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Kenya Institute of Curriculum Development (KICD)', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'ALL REQUISITIONS REPORT', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Generated on: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
$pdf->Ln(5);


$total_requisitions = count($requisitions);
$pending_count = count(array_filter($requisitions, fn($r) => $r['status'] == 'pending'));
$approved_count = count(array_filter($requisitions, fn($r) => $r['status'] == 'approved'));
$rejected_count = count(array_filter($requisitions, fn($r) => $r['status'] == 'rejected'));

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, "Total Requisitions: $total_requisitions", 0, 1, 'L');
$pdf->Cell(0, 6, "Pending: $pending_count | Approved: $approved_count | Rejected: $rejected_count", 0, 1, 'L');
$pdf->Ln(5);


$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 8, 'Req. No.', 1, 0, 'C');
$pdf->Cell(50, 8, 'Title', 1, 0, 'C');
$pdf->Cell(35, 8, 'Requester', 1, 0, 'C');
$pdf->Cell(25, 8, 'Status', 1, 0, 'C');
$pdf->Cell(20, 8, 'Priority', 1, 1, 'C');


$pdf->SetFont('helvetica', '', 9);
foreach ($requisitions as $req) {
    $pdf->Cell(30, 8, $req['requisition_number'], 1, 0, 'L');
    $pdf->Cell(50, 8, substr($req['title'], 0, 30) . (strlen($req['title']) > 30 ? '...' : ''), 1, 0, 'L');
    $pdf->Cell(35, 8, $req['first_name'] . ' ' . $req['last_name'], 1, 0, 'L');
    $pdf->Cell(25, 8, ucfirst($req['status']), 1, 0, 'C');
    $pdf->Cell(20, 8, ucfirst($req['priority']), 1, 1, 'C');
}

$pdf->Output('all_requisitions_report_' . date('Y-m-d') . '.pdf', 'D');
exit();
?>
