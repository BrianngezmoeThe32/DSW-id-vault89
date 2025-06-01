<?php
// Start session and enable error reporting for debugging
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header("Location: ../public/FirstPage.html");
    exit();
}

// Get username from session
$username = htmlspecialchars($_SESSION['user_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Idvault Online - Home</title>
    <link rel="stylesheet" href="../public/assets/css/home.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo" img="" alt="logo">IdVaut |</div>
            <ul>
                <li><a href="../organisation/police.html">Police Forum</a></li>
                <li><a href="../organisation/proofRes.html">Local Certifications</a></li>
                <li><a href="../organisation/homeAff.html">Home Affairs</a></li>
            </ul>
            <div class="user-actions">
                <i class="fa-solid fa-magnifying-glass"></i><a href="../public/search.html">Search</a>
                <i class="fa-solid fa-user"></i><span><?php echo $username; ?></span>
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <a href="FirstPage.html">Log out</a>
            </div>
        </nav>

        <div class="submenu">
            
            <a href="../public/status-check.php">Check status</a>
        </div>

        <main class="banner" style="background: url('../images/dashboard-bg.jpg') no-repeat center center/cover;">
            <div class="banner-text">
                <h1>Welcome back, <?php echo $username; ?></h1>
                <p>Access your dashboard, view your latest updates, and manage your documents with ease.</p>
            </div>
        </main>

        <section class="info">
            <h1>Your Dashboard</h1>
            <p>Explore the features designed for you:</p>
            <button class="info-button">Learn More About Your Account</button>
            <div class="offer-section">
                <div class="offer-box" style="background-image: url('../images/account-bg.jpg')">
                    <h3>My Documents</h3>
                    <p>View, download, or share your essential documents anytime.</p>
                    <button onclick="window.location.href='../public/auth/my_documents.php'" class="info-box-button">Go to Documents</button>
                </div>

                <div class="offer-box" style="background-image: url('../images/notifications-bg.jpg')">
                    <h3>Notifications</h3>
                    <p>Stay updated with the latest alerts and announcements.</p>
                    <button onclick="window.location.href='../public/auth/MyNotifications.html'" class="info-box-button">View Notifications</button>
                </div>

                <div class="offer-box" style="background-image: url('../images/settings-bg.jpg')">
                    <h3>Account Settings</h3>
                    <p>Review and update your account information, security settings, and preferences.</p>
                    <button onclick="window.location.href='../public/setting.php'" class="info-box-button">Manage Account</button>
                </div>
            </div>
        </section>

        <!-- Additional Info Grid -->
        <section class="info-grid">
            <div>
                <i class="fa-regular fa-bell"></i>
                <h3>Recent Activity</h3>
                <p>Your latest login, document views, and updates all in one place.</p>
            </div>
            <div>
                <i class="fa-regular fa-lightbulb"></i>
                <h3>Did You Know?</h3>
                <p>With Idvault, managing your documents digitally is secure, fast, and always at your fingertips.</p>
            </div>
        </section>

        <!-- Footer -->
        <div class="container">
            <div class="footer">
                <div>
                    <h3>Social</h3>
                    <div class="social-icons">
                        <i class="fab fa-facebook-f"></i>
                        <i class="fab fa-x-twitter"></i>
                        <i class="fab fa-linkedin-in"></i>
                        <i class="fab fa-blogger"></i>
                    </div>
                </div>
                <div>
                    <h3>Useful Tools</h3>
                    <ul>
                        <li><a href="#">Digital Certification</a></li>
                        <li><a href="#">Rates and Fees</a></li>
                        <li><a href="#">Latest Offers</a></li>
                        <li><a href="#">Find a Branch</a></li>
                        <li><a href="#">Safety and Security</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Who we are</h3>
                    <ul>
                        <li><a href="#">About Idvault</a></li>
                        <li><a href="#">Investor Relations</a></li>
                        <li><a href="#">Citizenship</a></li>
                        <li><a href="#">Media Centre</a></li>
                        <li><a href="#">Sponsorship</a></li>
                        <li><a href="#">Careers</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Our Sites</h3>
                    <ul>
                        <li><a href="../organisation/police.html">Police Forum</a></li>
                        <li><a href="../organisation/proofRes.html">Local Certifications</a></li>
                        <li><a href="../organisation/homeAff.html">Home Affairs</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Support</h3>
                    <ul>
                        <li><a href="../public/contactus.html">Contact Us</a></li>
                        <li><a href="../public/feedback.html">Send your Feedback</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <footer class="site-footer">
            <div class="footer-links">
                <a href="../public/terms.html">Terms Of Use</a>
                <span>|</span>
                <a href="#">Idvault Regulations</a>
                <span>|</span>
                <a href="#">Privacy Statement</a>
                <span>|</span>
                <a href="#">Security Centre</a>
            </div>
            <p class="footer-text"></p>
            <p class="footer-text">
                © Copyright. Idvault Limited, Registration number 2025/03/06. All
                rights reserved |
                <a href="#">Authorised online Documentation Services Provider</a>
            </p>
            <div class="footer-border"></div>
        </footer>
    </div>
</body>
</html>