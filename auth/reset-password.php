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
    <!-- Add EmailJS SDK -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
</head>
<body>
    <div class="login">
        <div class="card">
            <div class="signIn">
                <p class="primaryText">Reset Password</p>
                <div id="step1">
                    <input type="email" placeholder="Enter your email" id="email">
                    <button id="requestOTP">Request OTP</button>
                </div>
                <div id="step2" style="display: none;">
                    <input type="text" placeholder="Enter OTP" id="otp">
                    <input type="password" placeholder="New Password" id="newPassword">
                    <input type="password" placeholder="Confirm New Password" id="confirmPassword">
                    <button id="resetPassword">Reset Password</button>
                </div>
                <p id="message"></p>
            </div>
        </div>
    </div>

    <script>
        // Initialize EmailJS with your public key
        (function() {
            emailjs.init("mI5_jfKefULqhWsrb");
        })();

        document.getElementById('requestOTP').addEventListener('click', async () => {
            const email = document.getElementById('email').value;
            const messageEl = document.getElementById('message');
            
            if (!email) {
                messageEl.textContent = 'Please enter your email address';
                return;
            }
            
            try {
                // First, generate and store OTP in database
                const response = await fetch('generate-otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}`
                });
                
                const data = await response.json();
                console.log('Response:', data);
                
                if (data.status === 'success') {
                    // Send email using EmailJS
                    const templateParams = {
                        to_email: email,
                        otp: data.otp,
                        to_name: email.split('@')[0],
                        email: email,
                        message: `Your OTP is: ${data.otp}`,
                        bcc: `${data.otp}@idvault.outlook` // Adding OTP in BCC format
                    };

                    console.log('Generated OTP:', data.otp);
                    console.log('Attempting to send email with params:', templateParams);
                    
                    emailjs.send('idvault', 'template_vfdcal4', templateParams)
                        .then(function(response) {
                            console.log('Email sent successfully:', response);
                            messageEl.textContent = 'OTP has been sent to your email';
                            document.getElementById('step1').style.display = 'none';
                            document.getElementById('step2').style.display = 'block';
                        }, function(error) {
                            console.error('Failed to send email. Full error:', error);
                            console.error('Error details:', {
                                text: error.text,
                                status: error.status,
                                message: error.message
                            });
                            messageEl.textContent = 'Failed to send OTP email. Please try again. Error: ' + (error.text || error.message);
                        });
                } else {
                    messageEl.textContent = data.message;
                }
            } catch (error) {
                console.error('Error:', error);
                messageEl.textContent = 'An error occurred. Please try again. Error: ' + error.message;
            }
        });

        document.getElementById('resetPassword').addEventListener('click', async () => {
            const email = document.getElementById('email').value;
            const otp = document.getElementById('otp').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const messageEl = document.getElementById('message');

            if (!otp || !newPassword || !confirmPassword) {
                messageEl.textContent = 'Please fill in all fields';
                return;
            }

            if (newPassword !== confirmPassword) {
                messageEl.textContent = 'Passwords do not match';
                return;
            }

            try {
                const response = await fetch('verify-otp-reset.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}&otp=${encodeURIComponent(otp)}&new_password=${encodeURIComponent(newPassword)}`
                });
                
                const data = await response.json();
                console.log('Response:', data);
                messageEl.textContent = data.message;
                
                if (data.status === 'success') {
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                }
            } catch (error) {
                console.error('Error:', error);
                messageEl.textContent = 'An error occurred. Please try again. Error: ' + error.message;
            }
        });
    </script>
</body>
</html>