<?php
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user's documents from database
    $stmt = $pdo->prepare("
        SELECT d.id, d.document_type, d.submission_date, d.status, d.pdf_path,
               CASE 
                   WHEN d.document_type = 'affidavit' THEN 'Affidavit Certificate'
                   WHEN d.document_type = 'certified' THEN 'Certified Document'
                   ELSE 'Document'
               END as document_name,
               CASE
                   WHEN d.document_type = 'affidavit' THEN 'Police Forum'
                   WHEN d.document_type = 'certified' THEN 'Local Certifications'
                   ELSE 'Home Affairs'
               END as document_category
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
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Idvault Online - My Documents</title>
    <link rel="stylesheet" href="../assets/css/home.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        .documents-section {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .documents-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-btn.active {
            background: #ff1212;
            color: white;
            border-color: #ff1212;
        }
        
        .documents-list {
            display: grid;
            gap: 15px;
        }
        
        .document-card {
            display: flex;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            align-items: center;
            gap: 20px;
        }
        
        .document-icon {
            font-size: 2rem;
            color: #ff1212;
        }
        
        .document-info {
            flex-grow: 1;
        }
        
        .document-info h3 {
            margin: 0 0 5px 0;
        }
        
        .document-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .status-badge.approved {
            background: #28a745;
            color: white;
        }
        
        .status-badge.pending {
            background: #ffc107;
            color: black;
        }
        
        .status-badge.review {
            background: #17a2b8;
            color: white;
        }
        
        .status-badge.rejected {
            background: #dc3545;
            color: white;
        }
        
        .document-actions {
            display: flex;
            gap: 10px;
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
        
        .action-btn.download {
            background: #ff1212;
            color: white;
        }
        
        .action-btn.share {
            background: white;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .no-documents {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">IdVault |</div>
            <ul>
                <li><a href="../organisation/police.html">Police Forum</a></li>
                <li><a href="../organisation/proofRes.html">Local Certifications</a></li>
                <li><a href="../organisation/homeAff.html">Home Affairs</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-magnifying-glass"></i><a href="../search.html">Search</a>
                <i class="fa-solid fa-arrow-right-from-bracket"></i><a href="../logout.php">Log out</a>
            </div>
        </nav>

        <div class="submenu">
            <a href="../home.php">Home</a>
            <a href="../status-check.php">Check status</a>
        </div>

        <main class="banner">
            <div class="banner-text">
                <h1>My Documents</h1>
                <p>View, download, or share your essential documents anytime.</p>
            </div>
        </main>

        <section class="documents-section">
            <div class="documents-filter">
                <button class="filter-btn active" data-filter="all">All Documents</button>
                <button class="filter-btn" data-filter="Police Forum">Police Forum</button>
                <button class="filter-btn" data-filter="Local Certifications">Local Certifications</button>
                <button class="filter-btn" data-filter="Home Affairs">Home Affairs</button>
            </div>

            <div class="documents-list">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif (empty($documents)): ?>
                    <div class="no-documents">
                        <p>You haven't submitted any documents yet.</p>
                        <a href="../affidavit_form.php" class="action-btn download">Submit an Affidavit</a>
                        <a href="../certify_form.php" class="action-btn download">Upload a Document for Certification</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-card" data-category="<?= htmlspecialchars($doc['document_category']) ?>">
                            <div class="document-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="document-info">
                                <h3><?= htmlspecialchars($doc['document_name']) ?></h3>
                                <p><?= htmlspecialchars($doc['document_category']) ?> | Applied: <?= date('d M Y', strtotime($doc['submission_date'])) ?></p>
                                <span class="status-badge <?= 
                                    $doc['status'] === 'approved' ? 'approved' : 
                                    ($doc['status'] === 'pending' ? 'pending' : 
                                    ($doc['status'] === 'under_review' ? 'review' : 'rejected')) 
                                ?>">
                                    <?= ucfirst(str_replace('_', ' ', $doc['status'])) ?>
                                </span>
                            </div>
                            <div class="document-actions">
                                <?php if ($doc['status'] === 'approved' && !empty($doc['pdf_path'])): ?>
                                    <a href="../download.php?id=<?= $doc['id'] ?>" class="action-btn download">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <button class="action-btn share" onclick="shareDocument(<?= $doc['id'] ?>)">
                                        <i class="fas fa-share-alt"></i> Share
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn download" disabled>
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                    <button class="action-btn share" disabled>
                                        <i class="fas fa-share-alt"></i> Share
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
        // Filter documents by category
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelector('.filter-btn.active').classList.remove('active');
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const cards = document.querySelectorAll('.document-card');
                
                cards.forEach(card => {
                    const category = card.getAttribute('data-category');
                    if (filter === 'all' || category === filter) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Share document functionality
        function shareDocument(docId) {
            fetch('../generate_share_link.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ doc_id: docId }),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Copy to clipboard
                    navigator.clipboard.writeText(data.share_url).then(() => {
                        alert('Shareable link copied to clipboard!\n\n' + data.share_url);
                    }).catch(err => {
                        prompt('Copy this shareable link:', data.share_url);
                    });
                } else {
                    alert('Error generating share link: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error generating share link. Please try again.');
            });
        }
    </script>
</body>
</html>