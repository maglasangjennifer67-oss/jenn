<?php
$pageTitle = "Edit Complaint";
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'Invalid complaint record ID.');
    header('Location: manage-complaints.php');
    exit;
}

// Fetch existing complaint
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

// Fetch categories from DB
try {
    $catStmt = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = ['Technical Support', 'Billing & Payments', 'Customer Service', 'Facility & Infrastructure', 'Academic / Administrative', 'General Inquiry / Others'];
}

$errors = [];

// Handle UPDATE Operation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complainant_name = trim($_POST['complainant_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $category         = trim($_POST['category'] ?? '');
    $priority         = trim($_POST['priority'] ?? 'Medium');
    $subject          = trim($_POST['subject'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $status           = trim($_POST['status'] ?? 'Pending');
    $admin_remarks    = trim($_POST['admin_remarks'] ?? '');

    // Validation
    if (empty($complainant_name) || strlen($complainant_name) < 2) {
        $errors[] = "Complainant name cannot be empty.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($category)) {
        $errors[] = "Category must be chosen.";
    }
    if (empty($subject) || strlen($subject) < 5) {
        $errors[] = "Subject must be at least 5 characters.";
    }
    if (empty($description) || strlen($description) < 10) {
        $errors[] = "Description must be at least 10 characters.";
    }

    $validStatuses = ['Pending', 'In Progress', 'Resolved', 'Closed'];
    if (!in_array($status, $validStatuses)) {
        $status = 'Pending';
    }

    $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];
    if (!in_array($priority, $validPriorities)) {
        $priority = 'Medium';
    }

    if (empty($errors)) {
        try {
            $updateSql = "UPDATE complaints SET 
                complainant_name = :name,
                email            = :email,
                phone            = :phone,
                category         = :category,
                priority         = :priority,
                subject          = :subject,
                description      = :description,
                status           = :status,
                admin_remarks    = :remarks
                WHERE id = :id";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':name'      => $complainant_name,
                ':email'     => $email,
                ':phone'     => $phone,
                ':category'  => $category,
                ':priority'  => $priority,
                ':subject'   => $subject,
                ':description' => $description,
                ':status'    => $status,
                ':remarks'   => $admin_remarks,
                ':id'        => $id
            ]);

            setFlash('success', "Complaint <strong>{$complaint['tracking_code']}</strong> has been updated successfully.");
            header("Location: view-complaint.php?id={$id}");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Failed to update record: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="manage-complaints.php">Manage Complaints</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit <?= htmlspecialchars($complaint['tracking_code']) ?></li>
            </ol>
        </nav>
        <h2 class="fw-bold text-dark mb-0">Update Complaint Record</h2>
    </div>
    <a href="view-complaint.php?id=<?= $complaint['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-eye me-1"></i> View Record
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Update failed due to errors:</div>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Edit Form Card -->
<div class="card card-custom">
    <div class="card-header-clean bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-pencil-square text-primary me-1"></i> Edit Mode
            </h5>
            <small class="text-muted">Tracking Code: <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($complaint['tracking_code']) ?></span></small>
        </div>
        <span class="badge bg-light text-dark border">ID #<?= $complaint['id'] ?></span>
    </div>

    <div class="card-body p-4 p-md-5">
        <form action="edit-complaint.php?id=<?= $complaint['id'] ?>" method="POST" class="needs-validation" novalidate>
            
            <!-- Administrative Status & Resolution Section -->
            <div class="p-3 bg-light rounded-3 border mb-4">
                <h6 class="text-uppercase fw-bold text-primary mb-3 small">
                    <i class="bi bi-gear-fill me-1"></i> Administrative Status & Resolution
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Resolution Status <span class="text-danger">*</span></label>
                        <select class="form-select fw-semibold" id="status" name="status" required>
                            <option value="Pending" <?= ($complaint['status'] === 'Pending') ? 'selected' : '' ?>>Pending (Awaiting Review)</option>
                            <option value="In Progress" <?= ($complaint['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress (Under Investigation)</option>
                            <option value="Resolved" <?= ($complaint['status'] === 'Resolved') ? 'selected' : '' ?>>Resolved (Corrective Action Taken)</option>
                            <option value="Closed" <?= ($complaint['status'] === 'Closed') ? 'selected' : '' ?>>Closed (Completed & Archived)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="priority" class="form-label">Urgency / Priority <span class="text-danger">*</span></label>
                        <select class="form-select" id="priority" name="priority" required>
                            <option value="Low" <?= ($complaint['priority'] === 'Low') ? 'selected' : '' ?>>Low</option>
                            <option value="Medium" <?= ($complaint['priority'] === 'Medium') ? 'selected' : '' ?>>Medium</option>
                            <option value="High" <?= ($complaint['priority'] === 'High') ? 'selected' : '' ?>>High</option>
                            <option value="Urgent" <?= ($complaint['priority'] === 'Urgent') ? 'selected' : '' ?>>Urgent</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="admin_remarks" class="form-label">Admin Remarks / Resolution Details</label>
                        <textarea class="form-control" id="admin_remarks" name="admin_remarks" rows="3" 
                                  placeholder="Document actions taken, department notes, investigation findings, or message to the complainant..."><?= htmlspecialchars($complaint['admin_remarks'] ?? '') ?></textarea>
                        <div class="form-text">These remarks are visible to the complainant when tracking their ticket.</div>
                    </div>
                </div>
            </div>

            <!-- Complainant Contact Info -->
            <h6 class="text-uppercase fw-bold text-secondary mb-3 small">
                <i class="bi bi-person me-1"></i> Complainant Information
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="complainant_name" class="form-label">Complainant Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="complainant_name" name="complainant_name" 
                           value="<?= htmlspecialchars($complaint['complainant_name']) ?>" required>
                    <div class="invalid-feedback">Name is required.</div>
                </div>

                <div class="col-md-4">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($complaint['email']) ?>" required>
                    <div class="invalid-feedback">Valid email required.</div>
                </div>

                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?= htmlspecialchars($complaint['phone']) ?>" required>
                    <div class="invalid-feedback">Phone number required.</div>
                </div>
            </div>

            <!-- Complaint Incident Details -->
            <h6 class="text-uppercase fw-bold text-secondary mb-3 small">
                <i class="bi bi-file-earmark-text me-1"></i> Grievance Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($complaint['category'] === $cat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label for="subject" class="form-label">Subject / Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="subject" name="subject" 
                           value="<?= htmlspecialchars($complaint['subject']) ?>" required minlength="5">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="5" required minlength="10"><?= htmlspecialchars($complaint['description']) ?></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="manage-complaints.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Save Changes (Update)
                </button>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
