<?php
session_start();

if (!isset($_SESSION['success_message'])) {
    header("Location: affidavit_form.php");
    exit;
}

$message = $_SESSION['success_message'];
unset($_SESSION['success_message']); // Clear the message
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submission Successful</title>
    <link rel="stylesheet" href="../assets/css/home.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            color: #28a745;
            font-size: 50px;
            margin-bottom: 20px;
        }
        .action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #ff1212;
            color: white;
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #212529;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Include your header/navigation here -->
        
        <div class="confirmation-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Submission Successful!</h2>
            <p><?= htmlspecialchars($message) ?></p>
            
            <div class="action-buttons">
                <a href="status-check.html" class="btn btn-primary">
                    <i class="fas fa-tasks"></i> View Status
                </a>
                <a href="affidavit_form.html" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Form
                </a>
            </div>
        </div>
        
        <!-- Include your footer here -->
    </div>
</body>
</html>