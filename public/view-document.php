<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}

$docId = $_GET['id'] ?? null;
if (!$docId) {
    header("Location: admin-dashboard.php");
    exit();
}

// Initialize document data with default values
$document = [
    'id' => '',
    'name' => '',
    'email' => '',
    'document_type' => '',
    'document_category' => '',
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s'),
    'processed_at' => null,
    'document_path' => '',
    'affidavit_data' => [
        'name' => '',
        'id_number' => '',
        'age' => '',
        'residing_address' => '',
        'working_address' => '',
        'declaration' => '',
        'place' => '',
        'date' => '',
        'time' => ''
    ],
    'certified_data' => [
        'document_name' => '',
        'purpose' => '',
        'notes' => ''
    ]
];

try {
    $stmt = $pdo->prepare("
        SELECT d.*, u.name, u.email,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN ad.file_path
                   WHEN d.document_type = 'certified' THEN cd.file_path
                   ELSE d.document_path
               END AS document_path,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN 'Police Forum'
                   WHEN d.document_type = 'certified' THEN 'Local Certifications'
                   ELSE 'Home Affairs'
               END AS document_category
        FROM documents d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN certified_documents cd ON d.id = cd.document_id AND d.document_type = 'certified'
        LEFT JOIN affidavit_data ad ON d.id = ad.document_id AND d.document_type = 'affidavit'
        WHERE d.id = ?
    ");
    $stmt->execute([$docId]);
    $dbDocument = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dbDocument) {
        $document = array_merge($document, $dbDocument);
        
        if ($document['document_type'] === 'affidavit') {
            $stmt = $pdo->prepare("SELECT * FROM affidavit_data WHERE document_id = ?");
            $stmt->execute([$docId]);
            $affidavitData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($affidavitData) {
                $document['affidavit_data'] = array_merge($document['affidavit_data'], $affidavitData);
            }
        } elseif ($document['document_type'] === 'certified') {
            $stmt = $pdo->prepare("SELECT * FROM certified_documents WHERE document_id = ?");
            $stmt->execute([$docId]);
            $certifiedData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($certifiedData) {
                $document['certified_data'] = array_merge($document['certified_data'], $certifiedData);
            }
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

if (!$dbDocument) {
    header("Location: admin-dashboard.php");
    exit();
}

// Handle file viewing/downloading
if (isset($_GET['file'])) {
    if (!empty($document['document_path'])) {
        // Construct absolute path - adjust this based on your server structure
        $basePath = $_SERVER['DOCUMENT_ROOT'] . '/DSW-website/public/';
        $filePath = $basePath . ltrim($document['document_path'], '/');
        
        if (file_exists($filePath) && is_readable($filePath)) {
            // Get file info
            $filename = basename($filePath);
            $filesize = filesize($filePath);
            $mime = mime_content_type($filePath);
            
            // Set headers
            header("Content-Type: $mime");
            header("Content-Length: $filesize");
            
            // For download requests
            if (isset($_GET['download'])) {
                header("Content-Disposition: attachment; filename=\"$filename\"");
            } else {
                header("Content-Disposition: inline; filename=\"$filename\"");
            }
            
            // Clear buffers and send file
            ob_clean();
            flush();
            readfile($filePath);
            exit;
        }
    }
    header("HTTP/1.0 404 Not Found");
    echo "File not found or unavailable";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - Admin</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <link rel="stylesheet" href="../public/assets/css/home.css" />
    <style>
        .document-view {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .document-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .document-type {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        
        .type-police { background: #3498db; }
        .type-local { background: #2ecc71; }
        .type-home { background: #9b59b6; }
        
        .document-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .status-pending { background: #FFC107; color: black; }
        .status-approved { background: #28A745; color: white; }
        .status-rejected { background: #DC3545; color: white; }
        
        .document-meta {
            margin-bottom: 20px;
        }
        
        .document-meta p {
            margin: 5px 0;
            color: #555;
        }
        
        .document-content {
            margin: 25px 0;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            min-height: 200px;
        }
        
        .document-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            border: none;
        }
        
        .approve { background: #28A745; color: white; }
        .reject { background: #DC3545; color: white; }
        
        .detail-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .detail-section h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .declaration-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            white-space: pre-wrap;
        }
        
        iframe, img {
            width: 100%;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        
        iframe {
            height: 600px;
        }
        .download-btn {
            background: #9b59b6;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        
        .file-info {
            margin-top: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <div class="logo">IdVault | Admin</div>
            <ul>
                <li><a href="police-requests.php">Police Forum</a></li>
                <li><a href="local-certification-requests.php">Local Certifications</a></li>
                <li><a href="home-affairs-requests.php">Home Affairs</a></li>
                <li><a href="approval-history.php">Approval History</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-user"></i><span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <a href="../auth/logout.php">Log out</a>
            </div>
        </nav>

        <main class="admin-main">
            <div class="document-view">
                <h1><i class="fas fa-file-alt"></i> Document Details</h1>
                
                <div class="document-header">
                    <span class="document-type type-<?= strtolower(explode(' ', $document['document_category'])[0]) ?>">
                        <?= htmlspecialchars($document['document_category']) ?>
                    </span>
                    <span class="document-status status-<?= $document['status'] ?>">
                        <?= ucfirst($document['status']) ?>
                    </span>
                </div>
                
                <div class="document-meta">
                    <p><strong>User:</strong> <?= htmlspecialchars($document['name']) ?> (<?= htmlspecialchars($document['email']) ?>)</p>
                    <p><strong>Submitted:</strong> <?= date('M d, Y H:i', strtotime($document['created_at'])) ?></p>
                     <?php if (!empty($document['processed_at'])): ?>
                        <p><strong>Processed:</strong> <?= date('M d, Y H:i', strtotime($document['processed_at'])) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Affidavit Details -->
                <?php if ($document['document_type'] === 'affidavit' && isset($document['affidavit_data'])): ?>
                <div class="detail-section">
                    <h3><i class="fas fa-info-circle"></i> Affidavit Details</h3>
                    <div class="detail-row">
                        <div class="detail-label">Full Name:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['name']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">ID Number:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['id_number']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Age:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['age']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Residing Address:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['residing_address']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Working Address:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['working_address']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Declaration:</div>
                        <div class="detail-value">
                            <div class="declaration-box"><?= nl2br(htmlspecialchars($document['affidavit_data']['declaration'])) ?></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Place:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['place']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Date:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['date']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Time:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['affidavit_data']['time']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Certified Document Details -->
                <?php if ($document['document_type'] === 'certified'): ?>
                <div class="detail-section">
                    <h3><i class="fas fa-certificate"></i> Certification Details</h3>
                    <div class="detail-row">
                        <div class="detail-label">Document Name:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['certified_data']['document_name']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Purpose:</div>
                        <div class="detail-value"><?= htmlspecialchars($document['certified_data']['purpose']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Additional Notes:</div>
                        <div class="detail-value"><?= nl2br(htmlspecialchars($document['certified_data']['notes'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Document File Preview -->
                <div class="document-content">
                    <h3><i class="fas fa-file"></i> Document Preview</h3>
                    <?php if (!empty($document['document_path'])): ?>
                        <?php
                        $fileExtension = pathinfo($document['document_path'], PATHINFO_EXTENSION);
                        $viewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
                        $filename = basename($document['document_path']);
                        ?>
            
                    <div class="file-info">
                        <p><strong>File Name:</strong> <?= htmlspecialchars($filename) ?></p>
                        <p><strong>Type:</strong> <?= strtoupper($fileExtension) ?> file</p>
                    </div>
            
                    <?php if (in_array(strtolower($fileExtension), $viewableTypes)): ?>
                        <?php if (strtolower($fileExtension) === 'pdf'): ?>
                            <iframe src="view-document.php?id=<?= $docId ?>&file=1"></iframe>
                            <?php else: ?>
                                <img src="view-document.php?id=<?= $docId ?>&file=1" alt="Document Preview">
                            <?php endif; ?>
                        <?php else: ?>
                            <p>This file type cannot be previewed in browser.</p>
                        <?php endif; ?>
            
                        <a href="view-document.php?id=<?= $docId ?>&file=1&download=1" class="download-btn">
                            <i class="fas fa-download"></i> Download File
                        </a>
                        
                    <?php else: ?>
                        <p>No document file attached.</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($document['status'] === 'pending'): ?>
                <div class="document-actions">
                    <button class="action-btn approve" data-doc-id="<?= $document['id'] ?>">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="action-btn reject" data-doc-id="<?= $document['id'] ?>">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Handle approval/rejection
    document.querySelectorAll('.approve, .reject').forEach(btn => {
        btn.addEventListener('click', async function() {
            const docId = this.dataset.docId;
            const action = this.classList.contains('approve') ? 'approve' : 'reject';
            const actionText = action === 'approve' ? 'approve' : 'reject';
            
            if (!confirm(`Are you sure you want to ${actionText} this document?`)) {
                return;
            }
            
            try {
                const response = await fetch('handle_document.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ doc_id: docId, action: action })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Document ${actionText}d successfully!`);
                    location.reload();
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            }
        });
    });
    </script>
</body>
</html>