<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Application - IdVault</title>
    <link rel="stylesheet" href="../public/assets/css/homeAff.css">
    <link rel="stylesheet" href="../public/assets/css/firstPage.css">
    <link rel="stylesheet" href="../public/assets/css/applicationForms.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">IdVaut |</div>
            <ul>
                <li><a href="../organisation/police.html">Police Forum</a></li>
                <li><a href="../organisation/proofRes.html">Local Certifications</a></li>
                <li><a href="../organisation/homeAff.html">Home Affairs</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-magnifying-glass"></i><a href="../public/search.html">Search</a>
                <i class="fa-solid fa-arrow-right-from-bracket"></i><a href="../public/FirstPage.html">Log out</a>
            </div>
        </nav>

        <main>
            <section class="application-form">
                <h1>Passport Application</h1>
                <?php
                session_start();
                if(isset($_SESSION['message'])) {
                    $message_type = $_SESSION['message_type'];
                    $message = $_SESSION['message'];
                    echo "<div class='alert alert-$message_type'>$message</div>";
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                }
                ?>
                <form action="process_passport.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" required>
                    </div>

                    <div class="form-group">
                        <label for="id_number">ID Number:</label>
                        <input type="text" id="id_number" name="id_number" pattern="[0-9]{13}" maxlength="13" required>
                        <div class="error-message" id="id_error">ID number must be exactly 13 digits</div>
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth:</label>
                        <input type="date" id="dob" name="dob" required>
                        <div class="error-message" id="dob_error">You must be at least 18 years old</div>
                    </div>

                    <div class="form-group">
                        <label for="nationality">Nationality:</label>
                        <input type="text" id="nationality" name="nationality" required>
                    </div>

                    <div class="form-group">
                        <label for="passport_type">Passport Type:</label>
                        <select id="passport_type" name="passport_type" required>
                            <option value="regular">Regular Passport</option>
                            <option value="official">Official Passport</option>
                            <option value="diplomatic">Diplomatic Passport</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="photo">Passport Photo:</label>
                        <input type="file" id="photo" name="photo" accept="image/*" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Current Address:</label>
                        <textarea id="address" name="address" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contact">Contact Number:</label>
                        <input type="tel" id="contact" name="contact" pattern="[0-9]{10}" maxlength="10" required>
                        <div class="error-message" id="contact_error">Phone number must be exactly 10 digits</div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <button type="submit" class="submit-btn">Submit Application</button>
                </form>
            </section>
        </main>

        <footer class="site-footer">
            <div class="footer-links">
                <a href="#">Terms Of Use</a>
                <span>|</span>
                <a href="#">Idvault Regulations</a>
                <span>|</span>
                <a href="#">Privacy Statement</a>
                <span>|</span>
                <a href="#">Security Centre</a>
            </div>
            <p class="footer-text">
                © Copyright. Idvault Limited, Registration number 2025/03/06. All rights reserved |
                <a href="#">Authorised online Documentation Services Provider</a>
            </p>
        </footer>
    </div>
    <script>
        // Age validation
        const dobInput = document.getElementById('dob');
        const dobError = document.getElementById('dob_error');
        
        dobInput.addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            const age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            if (age < 18) {
                dobError.style.display = 'block';
                this.setCustomValidity('You must be at least 18 years old');
            } else {
                dobError.style.display = 'none';
                this.setCustomValidity('');
            }
        });

        // ID number validation
        const idInput = document.getElementById('id_number');
        const idError = document.getElementById('id_error');
        
        idInput.addEventListener('input', function() {
            if (this.value.length !== 13 || !/^\d+$/.test(this.value)) {
                idError.style.display = 'block';
                this.setCustomValidity('ID number must be exactly 13 digits');
            } else {
                idError.style.display = 'none';
                this.setCustomValidity('');
            }
        });

        // Phone number validation
        const contactInput = document.getElementById('contact');
        const contactError = document.getElementById('contact_error');
        
        contactInput.addEventListener('input', function() {
            if (this.value.length !== 10 || !/^\d+$/.test(this.value)) {
                contactError.style.display = 'block';
                this.setCustomValidity('Phone number must be exactly 10 digits');
            } else {
                contactError.style.display = 'none';
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 