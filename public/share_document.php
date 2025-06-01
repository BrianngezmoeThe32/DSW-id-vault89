<?php
require_once '../config/database.php';

$docId = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$docId || !$token) {
    header("HTTP/1.1 400 Bad Request");
    die("Invalid share link");
}

try {
    // In a real implementation, you would verify the token from database
    // For now we'll just check if the document exists and is approved
    
    $stmt = $pdo->prepare("
        SELECT d.*, u.name as user_name
        FROM documents d
        JOIN users u ON d.user_id = u.id
        WHERE d.id = ? AND d.status = 'approved'
    ");
    $stmt->execute([$docId]);
    $document = $stmt->fetch();

    if (!$document || empty($document['pdf_path'])) {
        header("HTTP/1.1 404 Not Found");
        die("Document not found or not approved");
    }

    // Generate PDF path
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/DSW-website/public/' . ltrim($document['pdf_path'], '/');
    
    if (!file_exists($filePath)) {
        header("HTTP/1.1 404 Not Found");
        die("Document file not found");
    }

    // Display share page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shared Document - IdVault Online</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .document-info { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .download-btn { display: inline-block; padding: 10px 20px; background: #9b59b6; color: white; 
                          text-decoration: none; border-radius: 4px; margin-top: 10px; }
        </style>
    </head>
    <body>
        <h1>Shared Document from IdVault Online</h1>
        
        <div class="document-info">
            <h2><?= htmlspecialchars($document['document_name']) ?></h2>
            <p><strong>Shared by:</strong> <?= htmlspecialchars($document['user_name']) ?></p>
            <p><strong>Document Type:</strong> <?= htmlspecialchars($document['document_type']) ?></p>
            <p><strong>Approval Date:</strong> <?= date('M d, Y', strtotime($document['processed_at'])) ?></p>
            
            <a href="<?= htmlspecialchars($document['pdf_path']) ?>" class="download-btn" download>
                Download Document (PDF)
            </a>
        </div>
        
        <p>This document was shared via IdVault Online's secure document sharing system.</p>
    </body>
    </html>
    <?php

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    die("Error processing your request");
}