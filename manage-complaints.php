<?php
$pageTitle = "Complaints Management";
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();

// Filters & Search handling
$searchKeyword = trim($_GET['search'] ?? '');
$filterStatus  = trim($_GET['status'] ?? '');
$filterCat     = trim($_GET['category'] ?? '');

// Base query
$sql = "SELECT * FROM complaints WHERE 1=1";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (tracking_code LIKE :search 
               OR complainant_name LIKE :search 
               OR email LIKE :search 
               OR subject LIKE :search)";
    $params[':search'] = "%{$searchKeyword}%";
}

if (!empty($filterStatus)) {
    $sql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

if (!empty($filterCat)) {
    $sql .= " AND category = :category";
    $params[':category'] = $filterCat;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Get unique categories for dropdown filter
try {
    $catListStmt = $pdo->query("SELECT DISTINCT category FROM complaints WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $existingCategories = $catListStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $existingCategories = [];
}

// Stats for quick pill badges
$statStmt = $pdo->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
    SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed
FROM complaints");
$counts = $statStmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Complaints</li>
            </ol>
        </nav>
        <h2 class="fw-bold text-dark mb-0">Complaints Redressal Dashboard</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="export.php" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <a href="submit-complaint.php" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-lg"></i> New Complaint
        </a>
    </div>
</div>

<!-- Status Filter Pills -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="manage-complaints.php" class="btn btn-sm <?= empty($filterStatus) ? 'btn-dark' : 'btn-light border' ?>">
        All Statuses <span class="badge bg-secondary ms-1"><?= (int)($counts['total'] ?? 0) ?></span>
    </a>
    <a href="manage-complaints.php?status=Pending" class="btn btn-sm <?= ($filterStatus === 'Pending') ? 'btn-warning text-dark' : 'btn-light border' ?>">
        Pending <span class="badge bg-warning text-dark ms-1"><?= (int)($counts['pending'] ?? 0) ?></span>
    </a>
    <a href="manage-complaints.php?status=In+Progress" class="btn btn-sm <?= ($filterStatus === 'In Progress') ? 'btn-info text-white' : 'btn-light border' ?>">
        In Progress <span class="badge bg-info text-white ms-1"><?= (int)($counts['in_progress'] ?? 0) ?></span>
    </a>
    <a href="manage-complaints.php?status=Resolved" class="btn btn-sm <?= ($filterStatus === 'Resolved') ? 'btn-success' : 'btn-light border' ?>">
        Resolved <span class="badge bg-success ms-1"><?= (int)($counts['resolved'] ?? 0) ?></span>
    </a>
    <a href="manage-complaints.php?status=Closed" class="btn btn-sm <?= ($filterStatus === 'Closed') ? 'btn-secondary' : 'btn-light border' ?>">
        Closed <span class="badge bg-secondary ms-1"><?= (int)($counts['closed'] ?? 0) ?></span>
    </a>
</div>

<!-- Search & Filtering Card -->
<div class="card card-custom p-3 mb-4">
    <form action="manage-complaints.php" method="GET" class="row g-2 align-items-center">
        <?php if (!empty($filterStatus)): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
        <?php endif; ?>
        
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" 
                       value="<?= htmlspecialchars($searchKeyword) ?>" 
                       placeholder="Search by code, complainant name, email, or keywords...">
            </div>
        </div>

        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="">-- Filter by Category (All) --</option>
                <?php foreach ($existingCategories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($filterCat === $cat) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-funnel"></i> Filter
            </button>
            <?php if (!empty($searchKeyword) || !empty($filterStatus) || !empty($filterCat)): ?>
                <a href="manage-complaints.php" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Complaints Data Table (READ) -->
<div class="card card-custom">
    <div class="card-header-clean d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-dark">Grievance Records</h5>
            <small class="text-muted">Displaying <?= count($complaints) ?> record(s)</small>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">#ID</th>
                    <th>Tracking Code</th>
                    <th>Complainant</th>
                    <th>Subject & Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Lodged Date</th>
                    <th class="text-end" style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($complaints)): ?>
                    <?php foreach ($complaints as $row): ?>
                        <tr>
                            <td class="text-muted fw-bold"><?= $row['id'] ?></td>
                            <td>
                                <a href="view-complaint.php?id=<?= $row['id'] ?>" class="font-monospace fw-bold text-primary text-decoration-none">
                                    <?= htmlspecialchars($row['tracking_code']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($row['complainant_name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($row['email']) ?></div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($row['subject']) ?>">
                                    <?= htmlspecialchars($row['subject']) ?>
                                </div>
                                <span class="badge bg-light text-secondary border mt-1 small">
                                    <?= htmlspecialchars($row['category']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $pClass = 'badge-priority-medium';
                                if ($row['priority'] === 'Low') $pClass = 'badge-priority-low';
                                if ($row['priority'] === 'High') $pClass = 'badge-priority-high';
                                if ($row['priority'] === 'Urgent') $pClass = 'badge-priority-urgent';
                                ?>
                                <span class="badge badge-priority <?= $pClass ?>">
                                    <?= htmlspecialchars($row['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $sClass = 'badge-pending';
                                $sIcon  = 'hourglass';
                                if ($row['status'] === 'In Progress') { $sClass = 'badge-in-progress'; $sIcon = 'arrow-repeat'; }
                                if ($row['status'] === 'Resolved')    { $sClass = 'badge-resolved';    $sIcon = 'check-circle'; }
                                if ($row['status'] === 'Closed')      { $sClass = 'badge-closed';      $sIcon = 'lock'; }
                                ?>
                                <span class="badge badge-status <?= $sClass ?>">
                                    <i class="bi bi-<?= $sIcon ?>"></i> <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= date('M d, Y', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- View Details -->
                                    <a href="view-complaint.php?id=<?= $row['id'] ?>" class="btn btn-outline-secondary" title="View Full Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <!-- Edit / Update -->
                                    <a href="edit-complaint.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary" title="Edit / Update">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <!-- Delete Button (Triggers Modal) -->
                                    <button type="button" class="btn btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteConfirmModal"
                                            data-id="<?= $row['id'] ?>"
                                            data-tracking="<?= htmlspecialchars($row['tracking_code']) ?>"
                                            data-subject="<?= htmlspecialchars($row['subject']) ?>"
                                            title="Delete Record">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                            <h6 class="fw-semibold">No complaints matched your search criteria.</h6>
                            <p class="small mb-3">Try adjusting your filters or search keywords.</p>
                            <a href="manage-complaints.php" class="btn btn-sm btn-outline-secondary">Reset All Filters</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reusable Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="delete-complaint.php" method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="delete_complaint_id" value="">
                    
                    <p class="text-muted mb-3">
                        Are you sure you want to permanently delete this complaint record? This action cannot be reversed.
                    </p>
                    
                    <div class="bg-light p-3 rounded border">
                        <div class="small text-muted">Tracking Code:</div>
                        <div class="fw-bold font-monospace text-primary mb-2" id="delete_tracking_code"></div>
                        <div class="small text-muted">Subject:</div>
                        <div class="fw-semibold text-dark text-truncate" id="delete_subject"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Permanently Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
