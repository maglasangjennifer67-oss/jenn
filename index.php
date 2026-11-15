<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Home";
require_once __DIR__ . '/config/database.php';

// Fetch quick live statistics from database
try {
    $pdo = getDbConnection();
    
    // Total count
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM complaints");
    $totalCount = (int)$stmt->fetch()['total'];

    // Status breakdown
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM complaints GROUP BY status");
    $stats = [
        'Pending' => 0,
        'In Progress' => 0,
        'Resolved' => 0,
        'Closed' => 0
    ];
    while ($row = $stmt->fetch()) {
        $stats[$row['status']] = (int)$row['count'];
    }

    // Recent 5 complaints for display
    $stmt = $pdo->query("SELECT tracking_code, subject, category, priority, status, created_at FROM complaints ORDER BY id DESC LIMIT 5");
    $recentComplaints = $stmt->fetchAll();
} catch (Exception $e) {
    $totalCount = 0;
    $stats = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0, 'Closed' => 0];
    $recentComplaints = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<div class="card-custom bg-white p-4 p-md-5 mb-5 border-0 shadow-sm" style="background: radial-gradient(circle at top right, #eff6ff 0%, #ffffff 70%);">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
                <i class="bi bi-patch-check-fill me-1"></i> Public Grievance Redressal Portal
            </span>
            <h1 class="display-6 fw-bold text-dark lh-sm mb-3">
                Voice Your Concerns. We Deliver <span class="text-primary">Action & Results</span>.
            </h1>
            <p class="lead text-muted fs-6 mb-4">
                Submit your issues, track real-time resolution stages with your unique reference ID, and receive official updates from support administration.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="submit-complaint.php" class="btn btn-primary btn-lg px-4 shadow-sm">
                    <i class="bi bi-pencil-square me-1"></i> Lodge New Complaint
                </a>
                <a href="manage-complaints.php" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="bi bi-speedometer2 me-1"></i> View All Complaints
                </a>
            </div>
        </div>

        <div class="col-lg-5">
            <!-- Quick Tracking Search Card -->
            <div class="card card-custom border-primary border-opacity-25 shadow-sm p-4 bg-white">
                <h5 class="fw-bold mb-2 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-primary"></i> Track Complaint Status
                </h5>
                <p class="small text-muted mb-3">Enter your unique reference tracking code (e.g., <code>CMP-2026-1001</code>) to view the current resolution progress.</p>
                <form action="track-complaint.php" method="GET" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                            <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. CMP-2026-XXXX" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Check Live Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Live Statistics Counters -->
<div class="row g-3 mb-5">
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-bg-total">
            <h6 class="text-uppercase fw-semibold small opacity-75 mb-1">Total Lodged</h6>
            <h2 class="display-6 fw-bold mb-0"><?= number_format($totalCount) ?></h2>
            <i class="bi bi-file-earmark-text icon-watermark"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-bg-pending">
            <h6 class="text-uppercase fw-semibold small opacity-75 mb-1">Pending Review</h6>
            <h2 class="display-6 fw-bold mb-0"><?= number_format($stats['Pending']) ?></h2>
            <i class="bi bi-hourglass-split icon-watermark"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-bg-progress">
            <h6 class="text-uppercase fw-semibold small opacity-75 mb-1">In Progress</h6>
            <h2 class="display-6 fw-bold mb-0"><?= number_format($stats['In Progress']) ?></h2>
            <i class="bi bi-gear-wide-connected icon-watermark"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-bg-resolved">
            <h6 class="text-uppercase fw-semibold small opacity-75 mb-1">Resolved</h6>
            <h2 class="display-6 fw-bold mb-0"><?= number_format($stats['Resolved']) ?></h2>
            <i class="bi bi-check2-circle icon-watermark"></i>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div class="card-custom p-4 p-md-5 mb-5">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark">How The Process Works</h3>
        <p class="text-muted small">Three simple transparent steps from submission to resolution.</p>
    </div>
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="p-3">
                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-input-cursor-text fs-3"></i>
                </div>
                <h5 class="fw-bold">1. File Complaint</h5>
                <p class="small text-muted">Fill out the detailed form specifying your category, issue description, and urgency level.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <div class="bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-qr-code fs-3"></i>
                </div>
                <h5 class="fw-bold">2. Receive Tracking ID</h5>
                <p class="small text-muted">An instant alphanumeric tracking code is created for you to monitor internal department actions.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
                <h5 class="fw-bold">3. Review & Resolution</h5>
                <p class="small text-muted">Authorized staff investigates the incident, updates status in real-time, and provides official remarks.</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Submissions Overview -->
<div class="card-custom">
    <div class="card-header-clean d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark">Recent Activity Feed</h5>
            <small class="text-muted">Latest filed complaints and their ongoing status</small>
        </div>
        <a href="manage-complaints.php" class="btn btn-sm btn-outline-primary">
            View All in Management Portal <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Tracking Code</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Filed On</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentComplaints)): ?>
                    <?php foreach ($recentComplaints as $c): ?>
                        <tr>
                            <td>
                                <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($c['tracking_code']) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 260px;">
                                    <?= htmlspecialchars($c['subject']) ?>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['category']) ?></span></td>
                            <td>
                                <?php
                                $pClass = 'badge-priority-medium';
                                if ($c['priority'] === 'Low') $pClass = 'badge-priority-low';
                                if ($c['priority'] === 'High') $pClass = 'badge-priority-high';
                                if ($c['priority'] === 'Urgent') $pClass = 'badge-priority-urgent';
                                ?>
                                <span class="badge badge-priority <?= $pClass ?>"><?= htmlspecialchars($c['priority']) ?></span>
                            </td>
                            <td>
                                <?php
                                $sClass = 'badge-pending';
                                $sIcon  = 'hourglass';
                                if ($c['status'] === 'In Progress') { $sClass = 'badge-in-progress'; $sIcon = 'arrow-repeat'; }
                                if ($c['status'] === 'Resolved')    { $sClass = 'badge-resolved';    $sIcon = 'check-circle'; }
                                if ($c['status'] === 'Closed')      { $sClass = 'badge-closed';      $sIcon = 'lock'; }
                                ?>
                                <span class="badge badge-status <?= $sClass ?>">
                                    <i class="bi bi-<?= $sIcon ?>"></i> <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?= date('M d, Y', strtotime($c['created_at'])) ?>
                            </td>
                            <td class="text-end">
                                <a href="track-complaint.php?code=<?= urlencode($c['tracking_code']) ?>" class="btn btn-sm btn-outline-primary" title="Track Live">
                                    <i class="bi bi-eye"></i> Track
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No complaints lodged yet. <a href="submit-complaint.php">Submit the first one!</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
