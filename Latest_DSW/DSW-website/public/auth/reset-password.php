<?php
// This would be a new page where users can set a new password after clicking the reset link
// The design should match your login page
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <div class="login">
        <div class="card">
            <div class="signIn">
                <p class="primaryText">Reset Password</p>
                <input type="hidden" id="resetToken" value="<?php echo $_GET['token'] ?? ''; ?>">
                <input type="password" placeholder="New Password" id="newPassword">
                <input type="password" placeholder="Confirm New Password" id="confirmPassword">
                <button id="submitNewPassword">Reset Password</button>
            </div>
        </div>
    </div>

    <script src="../auth/auth.js"></script>
</body>
</html>