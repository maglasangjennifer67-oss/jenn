<?php
/**
 * Export Complaints to CSV File
 */
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT id, tracking_code, complainant_name, email, phone, category, priority, subject, description, status, admin_remarks, created_at, updated_at FROM complaints ORDER BY id DESC");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "complaints_export_" . date('Ymd_His') . ".csv";

    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Add BOM for Microsoft Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Column Headers
    fputcsv($output, [
        'ID',
        'Tracking Code',
        'Complainant Name',
        'Email',
        'Phone',
        'Category',
        'Priority',
        'Subject',
        'Description',
        'Status',
        'Admin Remarks',
        'Date Created',
        'Date Updated'
    ]);

    foreach ($records as $row) {
        fputcsv($output, [
            $row['id'],
            $row['tracking_code'],
            $row['complainant_name'],
            $row['email'],
            $row['phone'],
            $row['category'],
            $row['priority'],
            $row['subject'],
            $row['description'],
            $row['status'],
            $row['admin_remarks'],
            $row['created_at'],
            $row['updated_at']
        ]);
    }

    fclose($output);
    exit;
} catch (Exception $e) {
    setFlash('danger', 'Failed to export CSV: ' . $e->getMessage());
    header('Location: manage-complaints.php');
    exit;
}
