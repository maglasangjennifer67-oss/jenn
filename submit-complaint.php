<?php
$pageTitle = "Submit Complaint";
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();
$errors = [];
$successTrackingCode = null;

// Pre-fill form values in case of validation errors
$formData = [
    'complainant_name' => '',
    'email'            => '',
    'phone'            => '',
    'category'         => '',
    'priority'         => 'Medium',
    'subject'          => '',
    'description'      => ''
];

// Fetch categories from DB
try {
    $catStmt = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Fallback list if table not yet seeded
    $categories = [
        'Technical Support',
        'Billing & Payments',
        'Customer Service',
        'Facility & Infrastructure',
        'Academic / Administrative',
        'General Inquiry / Others'
    ];
}

// Handle Form Submission (CREATE Operation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['complainant_name'] = trim($_POST['complainant_name'] ?? '');
    $formData['email']            = trim($_POST['email'] ?? '');
    $formData['phone']            = trim($_POST['phone'] ?? '');
    $formData['category']         = trim($_POST['category'] ?? '');
    $formData['priority']         = trim($_POST['priority'] ?? 'Medium');
    $formData['subject']          = trim($_POST['subject'] ?? '');
    $formData['description']      = trim($_POST['description'] ?? '');

    // Server-side Validation
    if (empty($formData['complainant_name']) || strlen($formData['complainant_name']) < 2) {
        $errors[] = "Please provide your valid full name.";
    }

    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (empty($formData['phone']) || strlen($formData['phone']) < 7) {
        $errors[] = "Please provide a valid contact phone number.";
    }

    if (empty($formData['category'])) {
        $errors[] = "Please select an applicable complaint category.";
    }

    $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];
    if (!in_array($formData['priority'], $validPriorities)) {
        $formData['priority'] = 'Medium';
    }

    if (empty($formData['subject']) || strlen($formData['subject']) < 5) {
        $errors[] = "Subject must be at least 5 characters long.";
    }

    if (empty($formData['description']) || strlen($formData['description']) < 15) {
        $errors[] = "Please provide a detailed description of the incident (at least 15 characters).";
    }

    // If validation passed, perform DB INSERT
    if (empty($errors)) {
        try {
            // Generate unique tracking code and verify uniqueness
            $trackingCode = generateTrackingCode();
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE tracking_code = ?");
            $checkStmt->execute([$trackingCode]);
            while ($checkStmt->fetchColumn() > 0) {
                $trackingCode = generateTrackingCode();
                $checkStmt->execute([$trackingCode]);
            }

            // Prepared INSERT statement
            $insertSql = "INSERT INTO complaints 
                (tracking_code, complainant_name, email, phone, category, priority, subject, description, status) 
                VALUES 
                (:tracking_code, :complainant_name, :email, :phone, :category, :priority, :subject, :description, 'Pending')";
            
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':tracking_code'    => $trackingCode,
                ':complainant_name' => $formData['complainant_name'],
                ':email'            => $formData['email'],
                ':phone'            => $formData['phone'],
                ':category'         => $formData['category'],
                ':priority'         => $formData['priority'],
                ':subject'          => $formData['subject'],
                ':description'      => $formData['description']
            ]);

            $successTrackingCode = $trackingCode;
            setFlash('success', "Your complaint has been successfully registered! Tracking Reference: <strong>{$trackingCode}</strong>");

            // Reset form data on success
            $formData = [
                'complainant_name' => '',
                'email'            => '',
                'phone'            => '',
                'category'         => '',
                'priority'         => 'Medium',
                'subject'          => '',
                'description'      => ''
            ];
        } catch (PDOException $e) {
            $errors[] = "Database insertion failed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header Breadcrumbs -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Submit Complaint</li>
            </ol>
        </nav>
        <h2 class="fw-bold text-dark mb-0">Lodge an Official Complaint</h2>
    </div>
    <a href="manage-complaints.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list-task me-1"></i> Complaints List
    </a>
</div>

<?php if ($successTrackingCode): ?>
    <!-- Submission Success Highlight Card -->
    <div class="card card-custom border-success mb-4 p-4 text-center bg-white">
        <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px;">
            <i class="bi bi-check-lg fs-1"></i>
        </div>
        <h3 class="fw-bold text-success">Complaint Submitted Successfully!</h3>
        <p class="text-muted mb-3">Your grievance has been logged and queued for administrative review.</p>
        
        <div class="bg-light p-3 rounded-3 border d-inline-block mx-auto mb-4" style="min-width: 300px;">
            <div class="small text-muted text-uppercase fw-semibold mb-1">Your Tracking Reference Code</div>
            <div class="d-flex align-items-center justify-content-center gap-2">
                <span class="fs-3 fw-bold text-primary font-monospace" id="generatedCode"><?= htmlspecialchars($successTrackingCode) ?></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?= htmlspecialchars($successTrackingCode) ?>', this)" title="Copy Code">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>
            <div class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i> Please save this code to monitor real-time updates.</div>
        </div>

        <div class="d-flex justify-content-center gap-2">
            <a href="track-complaint.php?code=<?= urlencode($successTrackingCode) ?>" class="btn btn-primary px-4">
                <i class="bi bi-search me-1"></i> Track This Complaint Now
            </a>
            <a href="submit-complaint.php" class="btn btn-outline-secondary">
                <i class="bi bi-plus-circle me-1"></i> Submit Another
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Please correct the following errors:</div>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Form Container Card -->
<div class="card card-custom">
    <div class="card-header-clean bg-white">
        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
            <i class="bi bi-card-text text-primary"></i> Complaint Details Form
        </h5>
        <small class="text-muted">All marked fields (<span class="text-danger">*</span>) are mandatory.</small>
    </div>
    
    <div class="card-body p-4 p-md-5">
        <form action="submit-complaint.php" method="POST" class="needs-validation" novalidate>
            
            <!-- Section 1: Complainant Information -->
            <h6 class="text-uppercase fw-bold text-primary mb-3 small letter-spacing-1">
                <i class="bi bi-person-bounding-box me-1"></i> 1. Complainant Contact Information
            </h6>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="complainant_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="complainant_name" name="complainant_name" 
                               value="<?= htmlspecialchars($formData['complainant_name']) ?>" 
                               placeholder="e.g. Maria Santos" required minlength="2">
                    </div>
                    <div class="invalid-feedback">Please enter your full name.</div>
                </div>

                <div class="col-md-4">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($formData['email']) ?>" 
                               placeholder="name@domain.com" required>
                    </div>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= htmlspecialchars($formData['phone']) ?>" 
                               placeholder="e.g. 0917-123-4567" required minlength="7">
                    </div>
                    <div class="invalid-feedback">Please enter a valid phone number.</div>
                </div>
            </div>

            <hr class="my-4 text-muted opacity-25">

            <!-- Section 2: Complaint Particulars -->
            <h6 class="text-uppercase fw-bold text-primary mb-3 small letter-spacing-1">
                <i class="bi bi-exclamation-circle me-1"></i> 2. Incident & Grievance Details
            </h6>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="category" class="form-label">Category / Department <span class="text-danger">*</span></label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">-- Choose Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($formData['category'] === $cat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select an applicable category.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">Urgency / Priority Level <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-2 pt-1">
                        <?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $p): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="priority" id="p_<?= $p ?>" 
                                       value="<?= $p ?>" <?= ($formData['priority'] === $p) ? 'checked' : '' ?> required>
                                <label class="form-check-label fw-semibold" for="p_<?= $p ?>">
                                    <?php if ($p === 'Low'): ?><span class="text-success">Low</span><?php endif; ?>
                                    <?php if ($p === 'Medium'): ?><span class="text-primary">Medium</span><?php endif; ?>
                                    <?php if ($p === 'High'): ?><span class="text-warning">High</span><?php endif; ?>
                                    <?php if ($p === 'Urgent'): ?><span class="text-danger">Urgent</span><?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12">
                    <label for="subject" class="form-label">Subject / Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="subject" name="subject" 
                           value="<?= htmlspecialchars($formData['subject']) ?>" 
                           placeholder="Summarize the core grievance in one clear sentence..." required minlength="5" maxlength="200">
                    <div class="invalid-feedback">Subject must be between 5 and 200 characters.</div>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Detailed Description of Complaint <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="5" 
                              placeholder="Describe exactly what happened, dates, locations, reference numbers, or individuals involved..." 
                              required minlength="15"><?= htmlspecialchars($formData['description']) ?></textarea>
                    <div class="form-text text-muted">Include any relevant timestamps, transaction IDs, or error messages.</div>
                    <div class="invalid-feedback">Please provide detailed information (minimum 15 characters).</div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
                <div class="d-flex gap-2">
                    <button type="reset" class="btn btn-light border">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                    </button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-send-check me-1"></i> Submit Complaint
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
