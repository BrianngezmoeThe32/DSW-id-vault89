// track_status.php
<?php
require_once 'database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT d.id, d.document_type, d.submission_date, d.status, d.pdf_path, d.admin_notes,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN 'Affidavit'
                   WHEN d.document_type = 'certified' THEN 'Certified Document'
                   ELSE 'Document'
               END as document_name
        FROM documents d
        WHERE d.user_id = ?
        ORDER BY d.submission_date DESC
    ");
    $stmt->execute([$user_id]);
    $documents = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Track Document Status</title>
    <!-- Include your CSS styles here -->
</head>
<body>
    <div class="container">
        <!-- Your navigation and header code -->
        
        <section class="status-container">
            <div class="status-header">
                <h2>Your Document Applications</h2>
                <p>Below are all your submitted applications and their current status</p>
            </div>

            <?php if (empty($documents)): ?>
                <div class="no-applications">
                    <p>You haven't submitted any documents yet.</p>
                    <a href="affidavit_form.html" class="btn">Submit an Affidavit</a>
                    <a href="certify_form.html" class="btn">Upload a Document for Certification</a>
                </div>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <div class="application-card <?= $doc['status'] === 'approved' ? 'approved' : ($doc['status'] === 'rejected' ? 'rejected' : 'pending') ?>">
                        <div class="app-header">
                            <h3 class="app-title"><?= htmlspecialchars($doc['document_name']) ?></h3>
                            <span class="app-type <?= $doc['document_type'] === 'affidavit' ? 'police' : ($doc['document_type'] === 'certified' ? 'local' : 'home') ?>">
                                <?= htmlspecialchars(ucfirst($doc['document_type'])) ?>
                            </span>
                        </div>

                        <div class="app-details">
                            <div class="app-meta">
                                <strong>Reference:</strong> <?= strtoupper(substr($doc['document_type'], 0, 1)) ?>-<?= date('Y') ?>-<?= str_pad($doc['id'], 5, '0', STR_PAD_LEFT) ?>
                            </div>
                            <div class="app-meta">
                                <strong>Submitted:</strong> <?= date('d M Y', strtotime($doc['submission_date'])) ?>
                            </div>
                            <div class="app-meta">
                                <strong>Status:</strong>
                                <span class="app-status status-<?= $doc['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $doc['status'])) ?>
                                </span>
                            </div>
                        </div>

                        <div class="status-timeline">
                            <div class="timeline-item completed">
                                <div class="timeline-date"><?= date('d M Y', strtotime($doc['submission_date'])) ?></div>
                                <div class="timeline-content">Application submitted</div>
                            </div>
                            
                            <?php if ($doc['status'] !== 'pending'): ?>
                                <div class="timeline-item <?= $doc['status'] === 'approved' ? 'completed' : ($doc['status'] === 'rejected' ? 'completed' : 'current') ?>">
                                    <div class="timeline-date">
                                        <?= date('d M Y', strtotime($doc['submission_date']) + 86400) // +1 day ?>
                                    </div>
                                    <div class="timeline-content">Application under review</div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($doc['status'] === 'approved' || $doc['status'] === 'rejected'): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-date">
                                        <?= date('d M Y', strtotime($doc['submission_date']) + 172800) // +2 days ?>
                                    </div>
                                    <div class="timeline-content">
                                        Application <?= $doc['status'] === 'approved' ? 'approved' : 'rejected' ?>
                                        <?php if ($doc['admin_notes']): ?>
                                            <p><em><?= htmlspecialchars($doc['admin_notes']) ?></em></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($doc['status'] === 'approved'): ?>
                                    <div class="timeline-item completed">
                                        <div class="timeline-date">
                                            <?= date('d M Y', strtotime($doc['submission_date']) + 172800) ?>
                                        </div>
                                        <div class="timeline-content">Document generated and ready</div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="timeline-item">
                                    <div class="timeline-date">Pending</div>
                                    <div class="timeline-content">Application review completion</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($doc['status'] === 'approved' && $doc['pdf_path']): ?>
                            <div class="app-actions">
                                <a href="download.php?id=<?= $doc['id'] ?>" class="action-btn download-btn">
                                    <i class="fas fa-download"></i> Download Document
                                </a>
                            </div>
                        <?php elseif ($doc['status'] === 'rejected'): ?>
                            <div class="app-actions">
                                <a href="<?= $doc['document_type'] === 'affidavit' ? 'affidavit_form.html' : 'certify_form.html' ?>" class="action-btn download-btn">
                                    <i class="fas fa-redo"></i> Resubmit
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>