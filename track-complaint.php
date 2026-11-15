<?php
$pageTitle = "Track Complaint Status";
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();
$code = trim($_GET['code'] ?? '');
$complaint = null;
$errorMsg = null;

if (!empty($code)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM complaints WHERE tracking_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $complaint = $stmt->fetch();

        if (!$complaint) {
            $errorMsg = "No complaint record found matching tracking code <strong>" . htmlspecialchars($code) . "</strong>. Please double-check the reference number.";
        }
    } catch (PDOException $e) {
        $errorMsg = "Error retrieving tracking record: " . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Tracking Search Header -->
<div class="row justify-content-center mb-4">
    <div class="col-lg-8">
        <div class="card card-custom p-4 text-center">
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-radar text-primary me-2"></i> Track Complaint Progress
            </h3>
            <p class="text-muted small mb-3">Check the real-time resolution status and official administrative remarks.</p>
            
            <form action="track-complaint.php" method="GET" class="row g-2 justify-content-center">
                <div class="col-md-7 col-sm-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                        <input type="text" name="code" class="form-control text-uppercase" 
                               value="<?= htmlspecialchars($code) ?>" placeholder="Enter Code (e.g. CMP-2026-1001)" required>
                    </div>
                </div>
                <div class="col-md-3 col-sm-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Track Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($errorMsg): ?>
    <div class="alert alert-warning shadow-sm text-center col-lg-8 mx-auto" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2 text-warning"></i>
        <?= $errorMsg ?>
    </div>
<?php endif; ?>

<?php if ($complaint): ?>
    <?php
    $status = $complaint['status'];
    // Determine stepper state
    $isPending    = in_array($status, ['Pending', 'In Progress', 'Resolved', 'Closed']);
    $isInProgress = in_array($status, ['In Progress', 'Resolved', 'Closed']);
    $isResolved   = in_array($status, ['Resolved', 'Closed']);
    $isClosed     = ($status === 'Closed');
    ?>

    <div class="col-lg-10 mx-auto">
        <!-- Visual Timeline Stepper -->
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
                <div>
                    <span class="text-muted small">Tracking Reference:</span>
                    <h4 class="fw-bold font-monospace text-primary mb-0"><?= htmlspecialchars($complaint['tracking_code']) ?></h4>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
                        <i class="bi bi-printer me-1"></i> Print Receipt
                    </button>
                    <a href="manage-complaints.php" class="btn btn-outline-primary btn-sm no-print">
                        <i class="bi bi-speedometer2 me-1"></i> View in Dashboard
                    </a>
                </div>
            </div>

            <!-- Stepper Bar -->
            <div class="tracking-stepper">
                <div class="tracking-step <?= $isPending ? ($status === 'Pending' ? 'active' : 'completed') : '' ?>">
                    <div class="tracking-icon">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                    <div class="tracking-label">Submitted</div>
                    <small class="text-muted d-block"><?= date('M d, H:i', strtotime($complaint['created_at'])) ?></small>
                </div>

                <div class="tracking-step <?= $isInProgress ? ($status === 'In Progress' ? 'active' : 'completed') : '' ?>">
                    <div class="tracking-icon">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <div class="tracking-label">Under Investigation</div>
                    <small class="text-muted d-block"><?= $isInProgress ? 'In Review' : 'Waiting' ?></small>
                </div>

                <div class="tracking-step <?= $isResolved ? ($status === 'Resolved' ? 'active' : 'completed') : '' ?>">
                    <div class="tracking-icon">
                        <i class="bi bi-check2-all"></i>
                    </div>
                    <div class="tracking-label">Resolved</div>
                    <small class="text-muted d-block"><?= $isResolved ? 'Action Completed' : 'Pending' ?></small>
                </div>

                <div class="tracking-step <?= $isClosed ? 'completed' : '' ?>">
                    <div class="tracking-icon">
                        <i class="bi bi-archive"></i>
                    </div>
                    <div class="tracking-label">Closed</div>
                    <small class="text-muted d-block"><?= $isClosed ? 'Archived' : 'Active' ?></small>
                </div>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card card-custom mb-4">
            <div class="card-header-clean bg-white d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Complaint Specifications</h5>
                <div>
                    <?php
                    $sClass = 'badge-pending';
                    if ($status === 'In Progress') $sClass = 'badge-in-progress';
                    if ($status === 'Resolved')    $sClass = 'badge-resolved';
                    if ($status === 'Closed')      $sClass = 'badge-closed';
                    ?>
                    <span class="badge badge-status <?= $sClass ?> fs-6">
                        Status: <?= htmlspecialchars($status) ?>
                    </span>
                </div>
            </div>
            
            <div class="card-body p-4">
                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Complainant Name</span>
                            <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($complaint['complainant_name']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Contact Email & Phone</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($complaint['email']) ?></span>
                            <span class="text-muted small ms-1">(<?= htmlspecialchars($complaint['phone']) ?>)</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Category / Department</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($complaint['category']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small d-block">Urgency Level</span>
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

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-1">Subject:</h6>
                    <div class="p-3 bg-white rounded border fw-semibold text-secondary">
                        <?= htmlspecialchars($complaint['subject']) ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-1">Grievance Narrative:</h6>
                    <div class="p-3 bg-light rounded border text-secondary" style="white-space: pre-line;">
                        <?= htmlspecialchars($complaint['description']) ?>
                    </div>
                </div>

                <!-- Administrative Remarks Section -->
                <div class="p-4 rounded-3 border border-primary border-opacity-25 bg-primary bg-opacity-10">
                    <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-1">
                        <i class="bi bi-chat-left-quote-fill"></i> Official Administrative Remarks & Resolution Note:
                    </h6>
                    <?php if (!empty($complaint['admin_remarks'])): ?>
                        <div class="text-dark" style="white-space: pre-line;">
                            <?= htmlspecialchars($complaint['admin_remarks']) ?>
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="bi bi-clock-history me-1"></i> Last updated: <?= date('F j, Y - g:i A', strtotime($complaint['updated_at'])) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted fst-italic mb-0 small">
                            <i class="bi bi-info-circle me-1"></i> No remarks logged yet. The assigned department is actively reviewing your submission.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <small class="text-muted">
                        Lodged on: <?= date('F j, Y - g:i A', strtotime($complaint['created_at'])) ?>
                    </small>
                    <a href="edit-complaint.php?id=<?= $complaint['id'] ?>" class="btn btn-sm btn-primary no-print">
                        <i class="bi bi-pencil-square me-1"></i> Edit / Update Status
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
