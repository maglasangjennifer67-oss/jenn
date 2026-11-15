<?php
/**
 * Delete Complaint Operation (CRUD - Delete)
 */
require_once __DIR__ . '/config/database.php';

// Only allow deletion via POST for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage-complaints.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'Invalid complaint identifier for deletion.');
    header('Location: manage-complaints.php');
    exit;
}

try {
    $pdo = getDbConnection();

    // Fetch tracking code for the confirmation message
    $fetchStmt = $pdo->prepare("SELECT tracking_code FROM complaints WHERE id = ?");
    $fetchStmt->execute([$id]);
    $complaint = $fetchStmt->fetch();

    if ($complaint) {
        $trackingCode = $complaint['tracking_code'];
        
        $deleteStmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
        $deleteStmt->execute([$id]);

        setFlash('success', "Complaint record <strong>{$trackingCode}</strong> has been permanently removed.");
    } else {
        setFlash('warning', "Record not found or already deleted.");
    }
} catch (PDOException $e) {
    setFlash('danger', "Database deletion error: " . $e->getMessage());
}

header('Location: manage-complaints.php');
exit;
