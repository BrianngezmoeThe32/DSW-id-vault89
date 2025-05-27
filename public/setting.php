<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header("Location: ../public/FirstPage.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "idvault");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Fetch additional user info from database
$stmt = $conn->prepare("SELECT phone, address, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Fetch user preferences
$stmt = $conn->prepare("SELECT email_notifications, sms_notifications, two_factor_auth, language FROM user_preferences WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->fetch_assoc();
$stmt->close();

// If no preferences exist, create default ones
if (!$preferences) {
    $stmt = $conn->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Fetch again
    $stmt = $conn->prepare("SELECT email_notifications, sms_notifications, two_factor_auth, language FROM user_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $preferences = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_personal'])) {
        // Update personal information
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        
        // Update name in session
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssi", $_SESSION['user_name'], $phone, $address, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Success message
        $personal_success = "Personal information updated successfully!";
    } 
    elseif (isset($_POST['update_password'])) {
        // Update password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                $stmt->close();
                
                $password_success = "Password updated successfully!";
            } else {
                $password_error = "New passwords do not match!";
            }
        } else {
            $password_error = "Current password is incorrect!";
        }
    } 
    elseif (isset($_POST['update_preferences'])) {
        // Update preferences
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $two_factor = isset($_POST['two_factor']) ? 1 : 0;
        $language = $_POST['language'];
        
        $stmt = $conn->prepare("UPDATE user_preferences SET email_notifications = ?, sms_notifications = ?, two_factor_auth = ?, language = ? WHERE user_id = ?");
        $stmt->bind_param("iissi", $email_notifications, $sms_notifications, $two_factor, $language, $user_id);
        $stmt->execute();
        $stmt->close();
        
        $preferences_success = "Preferences updated successfully!";
    }
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/profile_images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $target_file = $target_dir . $user_id . '_' . time() . '.' . $file_extension;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Update database with new image path
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->bind_param("si", $target_file, $user_id);
                $stmt->execute();
                $stmt->close();
                
                $image_success = "Profile image updated successfully!";
                $user_data['profile_image'] = $target_file;
            } else {
                $image_error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $image_error = "File is not an image.";
        }
    }
}

$conn->close();

// Split name into first and last
$name_parts = explode(' ', $user_name);
$first_name = $name_parts[0] ?? '';
$last_name = $name_parts[1] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Idvault Online - Account Settings</title>
    <link rel="stylesheet" href="../public/assets/css/home.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        .strength-meter {
            height: 5px;
            background: #ddd;
            margin: 5px 0 15px;
            border-radius: 3px;
        }
        .strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }
        .weak { width: 30%; background: #ff4d4d; }
        .medium { width: 60%; background: #ffcc00; }
        .strong { width: 100%; background: #00cc66; }
        .success-message { color: green; margin: 10px 0; }
        .error-message { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo" img="" alt="logo">IdVaut |</div>
            <ul>
                <li><a href="../organisation/police.html">Police Forum</a></li>
                <li><a href="../organisation/proofRes.html">Local Certifications</a></li>
                <li><a href="../organisation/homeAff.html"> Home Affairs</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-magnifying-glass"></i><a href="../search/index.html">Search</a>
                <i class="fa-solid fa-user"></i><span><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i><a href="FirstPage.html
                ">Log out</a>
            </div>
        </nav>

        <div class="submenu">
            <a href="../Cards/index.html">Virtual Cards</a>
            <a href="../public/status-check.html">Check status</a>
        </div>

        <main class="banner">
            <div class="banner-text">
                <h1>Account Settings</h1>
                <p>Review and update your account information, security settings, and preferences.</p>
            </div>
        </main>

        <section class="account-settings">
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-pic">
                        <img src="<?php echo !empty($user_data['profile_image']) ? $user_data['profile_image'] : '../images/default-profile.jpg'; ?>" 
                             alt="Profile Picture" id="profile-image" />
                        <form method="post" enctype="multipart/form-data" class="photo-form">
                            <button type="button" class="change-photo-btn" onclick="document.getElementById('profile-upload').click()">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>
                            <input type="file" id="profile-upload" name="profile_image" accept="image/*" style="display: none" 
                                   onchange="document.getElementById('photo-form-submit').click()" />
                            <button type="submit" id="photo-form-submit" style="display: none"></button>
                        </form>
                        <?php if (isset($image_success)): ?>
                            <div class="success-message"><?php echo $image_success; ?></div>
                        <?php endif; ?>
                        <?php if (isset($image_error)): ?>
                            <div class="error-message"><?php echo $image_error; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user_name); ?></h2>
                        <p>Member since: <?php echo date('F Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                    </div>
                </div>
            </div>

            <div class="settings-tabs">
                <button class="tab-btn active" data-tab="personal">Personal Information</button>
                <button class="tab-btn" data-tab="security">Security</button>
                <button class="tab-btn" data-tab="preferences">Preferences</button>
            </div>

            <div class="tab-content active" id="personal-tab">
                <form class="settings-form" method="post">
                    <?php if (isset($personal_success)): ?>
                        <div class="success-message"><?php echo $personal_success; ?></div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required />
                    </div>
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required />
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_personal" class="save-btn">Save Changes</button>
                </form>
            </div>

            <div class="tab-content" id="security-tab">
                <form class="settings-form" method="post">
                    <?php if (isset($password_success)): ?>
                        <div class="success-message"><?php echo $password_success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($password_error)): ?>
                        <div class="error-message"><?php echo $password_error; ?></div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" name="current_password" required />
                    </div>
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new_password" required />
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" required />
                    </div>
                    <div class="password-strength">
                        <p>Password Strength: <span id="strength-indicator">Weak</span></p>
                        <div class="strength-meter">
                            <div class="strength-bar weak" id="strength-bar"></div>
                        </div>
                        <ul class="password-requirements">
                            <li>At least 8 characters</li>
                            <li>Contains a number</li>
                            <li>Contains a special character</li>
                        </ul>
                    </div>
                    <button type="submit" name="update_password" class="save-btn">Update Password</button>
                </form>
            </div>

            <div class="tab-content" id="preferences-tab">
                <form class="settings-form" method="post">
                    <?php if (isset($preferences_success)): ?>
                        <div class="success-message"><?php echo $preferences_success; ?></div>
                    <?php endif; ?>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="email-notifications" name="email_notifications" 
                            <?php echo ($preferences['email_notifications'] ?? true) ? 'checked' : ''; ?> />
                        <label for="email-notifications">Email Notifications</label>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="sms-notifications" name="sms_notifications" 
                            <?php echo ($preferences['sms_notifications'] ?? false) ? 'checked' : ''; ?> />
                        <label for="sms-notifications">SMS Notifications</label>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="two-factor" name="two_factor" 
                            <?php echo ($preferences['two_factor_auth'] ?? false) ? 'checked' : ''; ?> />
                        <label for="two-factor">Two-Factor Authentication</label>
                    </div>
                    <div class="form-group">
                        <label for="language">Language</label>
                        <select id="language" name="language">
                            <option value="en" <?php echo ($preferences['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                            <option value="es" <?php echo ($preferences['language'] ?? 'en') === 'es' ? 'selected' : ''; ?>>Spanish</option>
                            <option value="fr" <?php echo ($preferences['language'] ?? 'en') === 'fr' ? 'selected' : ''; ?>>French</option>
                        </select>
                    </div>
                    <button type="submit" name="update_preferences" class="save-btn">Save Preferences</button>
                </form>
            </div>
        </section>

        <!-- Footer (same as home page) -->
        <div class="container">
            <div class="footer">
                <!-- Footer content same as home page -->
            </div>
        </div>

        <footer class="site-footer">
            <!-- Footer content same as home page -->
        </footer>
    </div>
    <script>
        // Tab switching functionality
        document.querySelectorAll(".tab-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                // Remove active class from all buttons and tabs
                document.querySelectorAll(".tab-btn").forEach((b) => b.classList.remove("active"));
                document.querySelectorAll(".tab-content").forEach((t) => t.classList.remove("active"));

                // Add active class to clicked button
                btn.classList.add("active");

                // Show corresponding tab
                const tabId = btn.getAttribute("data-tab") + "-tab";
                document.getElementById(tabId).classList.add("active");
            });
        });

        // Profile image upload preview
        document.getElementById("profile-upload").addEventListener("change", function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById("profile-image").src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Password strength indicator
        document.getElementById("new-password").addEventListener("input", function() {
            const password = this.value;
            const indicator = document.getElementById("strength-indicator");
            const bar = document.getElementById("strength-bar");
            
            // Reset
            indicator.textContent = "Weak";
            bar.className = "strength-bar weak";
            
            if (password.length === 0) {
                return;
            }
            
            // Check password strength
            let strength = 0;
            
            // Length
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Contains numbers
            if (/\d/.test(password)) strength++;
            
            // Contains special chars
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            // Contains both lower and upper case
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            
            // Update UI
            if (strength >= 4) {
                indicator.textContent = "Strong";
                bar.className = "strength-bar strong";
            } else if (strength >= 2) {
                indicator.textContent = "Medium";
                bar.className = "strength-bar medium";
            }
        });
    </script>
</body>
</html>