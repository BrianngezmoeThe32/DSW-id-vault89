<?php
session_start();
require_once '../public/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get all documents for the current user
try {
    $stmt = $pdo->prepare("
        SELECT d.*, 
               u.name as user_name,
               a.name as reviewer_name
        FROM documents d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN users a ON d.reviewed_by = a.id
        WHERE d.user_id = ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching documents: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Idvault Online - Check Application Status</title>
    <link rel="stylesheet" href="../public/assets/css/home.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">IdVault</div>
            <ul>
                <li><a href="../organisation/police.html">Police Forum</a></li>
                <li><a href="../organisation/proofRes.html">Local Certifications</a></li>
                <li><a href="../organisation/homeAff.html">Home Affairs</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-user"></i><span><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <a href="../auth/logout.php">Log out</a>
            </div>
        </nav>

        <div class="submenu">
            <a href="status-check.php" class="active">Check status</a>
            <a href="my-documents.php">My Documents</a>
        </div>

        <main class="banner">
            <div class="banner-text">
                <h1>Application Status</h1>
                <p>Track the progress of your document applications.</p>
            </div>
        </main>

        <section class="status-check">
            <div class="status-search">
                <div class="search-box">
                    <input type="text" placeholder="Enter application reference number" />
                    <button class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <p class="search-help">
                    Don't have a reference number?
                    <a href="status-check.php">View all your applications</a>
                </p>
            </div>

            <div class="status-timeline">
                <h2>Your Recent Applications</h2>

                <?php foreach ($documents as $document): ?>
                <div class="timeline-item">
                    <div class="timeline-header">
                        <h3><?= htmlspecialchars(ucfirst($document['document_type'])) ?></h3>
                        <span class="status-badge <?= $document['status'] ?>">
                            <?= htmlspecialchars($document['status']) ?>
                        </span>
                    </div>
                    <div class="timeline-body">
                        <div class="timeline-progress">
                            <div class="progress-step <?= $document['status'] !== 'pending' ? 'completed' : 'current' ?>">
                                <div class="step-icon">
                                    <?php if ($document['status'] !== 'pending'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <i class="fas fa-spinner"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-label">Submitted</div>
                                <div class="step-date"><?= date('d M Y', strtotime($document['created_at'])) ?></div>
                            </div>
                            
                            <div class="progress-step <?= 
                                $document['status'] === 'under_review' ? 'current' : 
                                ($document['status'] === 'approved' || $document['status'] === 'rejected' ? 'completed' : '') 
                            ?>">
                                <div class="step-icon">
                                    <?php if ($document['status'] === 'under_review'): ?>
                                        <i class="fas fa-spinner"></i>
                                    <?php elseif ($document['status'] === 'approved' || $document['status'] === 'rejected'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-label">Under Review</div>
                                <div class="step-date">
                                    <?= $document['status'] !== 'pending' ? date('d M Y', strtotime($document['created_at'])) : 'Pending' ?>
                                </div>
                            </div>
                            
                            <div class="progress-step <?= 
                                $document['status'] === 'approved' ? 'completed current' : 
                                ($document['status'] === 'rejected' ? 'completed current' : '') 
                            ?>">
                                <div class="step-icon">
                                    <?php if ($document['status'] === 'approved'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php elseif ($document['status'] === 'rejected'): ?>
                                        <i class="fas fa-times"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-label">
                                    <?= $document['status'] === 'rejected' ? 'Rejected' : 'Approval' ?>
                                </div>
                                <div class="step-date">
                                    <?= $document['reviewed_at'] ? date('d M Y', strtotime($document['reviewed_at'])) : 'Pending' ?>
                                </div>
                            </div>
                            
                            <div class="progress-step <?= $document['status'] === 'approved' ? 'current' : '' ?>">
                                <div class="step-icon">
                                    <?php if ($document['status'] === 'approved'): ?>
                                        <i class="fas fa-file-pdf"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-label">Document Ready</div>
                                <div class="step-date">
                                    <?= $document['status'] === 'approved' ? 
                                        date('d M Y', strtotime($document['reviewed_at'])) : 'Pending' ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($document['status'] === 'rejected'): ?>
                        <div class="timeline-message alert">
                            <p>
                                <i class="fas fa-exclamation-triangle"></i> 
                                Your document was rejected by <?= htmlspecialchars($document['reviewer_name'] ?? 'admin') ?>.
                            </p>
                        </div>
                        <?php elseif ($document['status'] === 'approved'): ?>
                        <div class="timeline-actions">
                            <?php if (!empty($document['document_path'])): ?>
                            <a href="../auth/download.php?id=<?= $document['id'] ?>" class="action-btn download">
                                <i class="fas fa-download"></i> Download Document
                            </a>
                            <?php endif; ?>
                            <button class="action-btn feedback">
                                <i class="fas fa-comment"></i> Provide Feedback
                            </button>
                        </div>
                        <?php elseif ($document['status'] === 'under_review'): ?>
                        <div class="timeline-message">
                            <p>
                                <i class="fas fa-info-circle"></i> 
                                Your application is currently being reviewed by our team.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($documents)): ?>
                <div class="no-documents">
                    <i class="fas fa-folder-open"></i>
                    <p>You haven't submitted any documents yet.</p>
                    <a href="../organisation/" class="action-btn">Submit a Document</a>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
