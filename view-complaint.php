<?php
$pageTitle = "Complaint Details";
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'Invalid complaint ID requested.');
    header('Location: manage-complaints.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        setFlash('danger', 'Complaint record not found.');
        header('Location: manage-complaints.php');
        exit;
    }
} catch (PDOException $e) {
    setFlash('danger', 'Error retrieving record: ' . $e->getMessage());
    header('Location: manage-complaints.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs & Quick Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="manage-complaints.php">Manage Complaints</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($complaint['tracking_code']) ?></li>
            </ol>
        </nav>
        <h2 class="fw-bold text-dark mb-0">Record #<?= $complaint['id'] ?> - <?= htmlspecialchars($complaint['tracking_code']) ?></h2>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </button>
        <a href="edit-complaint.php?id=<?= $complaint['id'] ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil-square me-1"></i> Edit / Update
        </a>
        <a href="manage-complaints.php" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<!-- Detailed Card -->
<div class="card card-custom mb-4">
    <div class="card-header-clean bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="font-monospace fs-5 fw-bold text-primary"><?= htmlspecialchars($complaint['tracking_code']) ?></span>
            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($complaint['category']) ?></span>
        </div>
        <div>
            <?php
            $sClass = 'badge-pending';
            if ($complaint['status'] === 'In Progress') $sClass = 'badge-in-progress';
            if ($complaint['status'] === 'Resolved')    $sClass = 'badge-resolved';
            if ($complaint['status'] === 'Closed')      $sClass = 'badge-closed';
            ?>
            <span class="badge badge-status <?= $sClass ?> fs-6">
                <?= htmlspecialchars($complaint['status']) ?>
            </span>
        </div>
    </div>

    <div class="card-body p-4 p-md-5">
        <!-- Subject Banner -->
        <div class="mb-4">
            <label class="text-muted small text-uppercase fw-bold">Subject / Issue Summary</label>
            <h4 class="fw-bold text-dark mt-1"><?= htmlspecialchars($complaint['subject']) ?></h4>
        </div>

        <!-- Meta Grid -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block">Complainant Name</small>
                    <span class="fw-bold text-dark"><?= htmlspecialchars($complaint['complainant_name']) ?></span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block">Email Address</small>
                    <a href="mailto:<?= htmlspecialchars($complaint['email']) ?>" class="fw-semibold text-decoration-none">
                        <?= htmlspecialchars($complaint['email']) ?>
                    </a>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block">Phone Number</small>
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($complaint['phone']) ?></span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block">Priority Level</small>
                    <?php
                    $pClass = 'badge-priority-medium';
                    if ($complaint['priority'] === 'Low') $pClass = 'badge-priority-low';
                    if ($complaint['priority'] === 'High') $pClass = 'badge-priority-high';
                    if ($complaint['priority'] === 'Urgent') $pClass = 'badge-priority-urgent';
                    ?>
                    <span class="badge badge-priority <?= $pClass ?>"><?= htmlspecialchars($complaint['priority']) ?></span>
                </div>
            </div>
        </div>

        <!-- Detailed Description -->
        <div class="mb-4">
            <label class="text-muted small text-uppercase fw-bold mb-2 d-block">Full Grievance Statement</label>
            <div class="p-4 bg-light rounded-3 border text-secondary" style="white-space: pre-line; line-height: 1.7;">
                <?= htmlspecialchars($complaint['description']) ?>
            </div>
        </div>

        <!-- Administrative Remarks & Resolution Notes -->
        <div class="p-4 rounded-3 border border-primary border-opacity-25 bg-primary bg-opacity-10 mb-4">
            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-1">
                <i class="bi bi-journal-text"></i> Administrative Resolution Notes & Action Remarks
            </h6>
            <?php if (!empty($complaint['admin_remarks'])): ?>
                <div class="text-dark" style="white-space: pre-line; line-height: 1.6;">
                    <?= htmlspecialchars($complaint['admin_remarks']) ?>
                </div>
            <?php else: ?>
                <p class="text-muted small fst-italic mb-0">No administrative notes have been logged for this incident yet.</p>
            <?php endif; ?>
        </div>

        <!-- Timestamps -->
        <div class="row g-2 text-muted small border-top pt-3">
            <div class="col-sm-6">
                <i class="bi bi-calendar-plus me-1"></i> Date Submitted: <strong><?= date('F j, Y - g:i A', strtotime($complaint['created_at'])) ?></strong>
            </div>
            <div class="col-sm-6 text-sm-end">
                <i class="bi bi-clock-history me-1"></i> Last Updated: <strong><?= date('F j, Y - g:i A', strtotime($complaint['updated_at'])) ?></strong>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
