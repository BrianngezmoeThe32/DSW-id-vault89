<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: ../public/auth/login.html');
    exit();
}
?>
<!DOCTYPE html>
 <html lang="en">
   <head>
     <meta charset="UTF-8" />
     <meta name="viewport" content="width=device-width, initial-scale=1.0" />
     <title>Police Forum</title>
     <Link rel="stylesheet" href="../public/assets/css/home.css">
     <link rel="stylesheet" href="../public/assets/css/proofRes.css">
     <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
     <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
     <style>
         .modal {
             display: none;
             position: fixed;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             background-color: rgba(0,0,0,0.5);
             z-index: 1000;
         }
         .modal-content {
             background-color: white;
             margin: 15% auto;
             padding: 20px;
             border-radius: 5px;
             width: 70%;
             max-width: 600px;
         }
         .close {
             float: right;
             cursor: pointer;
             font-size: 24px;
         }
         .progress-container {
             margin-top: 20px;
         }
         .progress-bar {
             width: 100%;
             height: 20px;
             background-color: #f0f0f0;
             border-radius: 10px;
             overflow: hidden;
         }
         .progress {
             width: 0%;
             height: 100%;
             background-color: #4CAF50;
             transition: width 0.3s ease;
         }
         .location-history {
             margin-top: 20px;
         }
         .location-entry {
             padding: 10px;
             margin: 5px 0;
             background-color: #f8f8f8;
             border-radius: 5px;
         }
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
          <i class="fa-solid fa-magnifying-glass"></i><a href="../public/search.html">Search</a>
          <i class="fa-solid fa-arrow-right-from-bracket"></i
          ><a href="../public/FirstPage.html">Log out</a>
        </div>
      </nav>
      <div class="submenu">
        <a href="../organisation/proofRes.html">Local Certifications</a>
      </div>
     
     <main>
       <section class = "features">
           <div class = "heading">
               <h4>FEATURES</h4>
               <h1>All the services you need right at your fingertip.</h1>
               <h3>We are all tired of going to the Municipality every three months.
                With IdVaullt you get all your legal documents validation at the tip of your fingers.
               </h3>
           
       </section>
       <section class = "topFeatures">
           <div class = "feature1">
               <div class = "background1">
                   <div class = "featureName"><h2>Get Proof of Residence</h2></div>
               </div>
               <button class = "seeDetails">See Details</button><a href="../"></a>
             </div>
           
       </section>
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
                     <li><a href="#">rates and fees</a></li>
                     <li><a href="#">Latest offers</a></li>
                     <li><a href="#">Find a branch</a></li>
                     <li><a href="#">Safety and security</a></li>
                     
                 </ul>
             </div>
             <div>
                 <h3>Who we are</h3>
                 <ul>
                     <li><a href="#">About Idvault</a></li>
                     <li><a href="#">Investor Relations</a></li>
                     <li><a href="#">Citizenship</a></li>
                     <li><a href="#">Arts</a></li>
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
                     <li><a href="../public/auth/home.php">Home</a></li>
                     
                 </ul>
             </div>
             <div>
                 <h3>Support</h3>
                 <ul>
                     <li><a href="../public/contactus.html">Contact Us</a></li>
                     <li><a href="../public/feedback.html">Send your feedback</a></li>
                 </ul>
             </div>
             
         </div>
       </div>
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
         </p>
         <p class="footer-text">
           © Copyright. Idvault Limited, Registration number 2025/03/06. All rights reserved |
           <a href="#">Authorised online Documantation Services Provider</a>
         </p>
         <div class="footer-border"></div>
       </footer>
       <script>
         const buttons = document.querySelectorAll('.seeDetails');
         
         // Function to show modal
         function showModal() {
             const modal = document.getElementById('infoModal');
             modal.style.display = 'block';
         }
         
         // Function to close modal
         function closeModal() {
             const modal = document.getElementById('infoModal');
             modal.style.display = 'none';
         }
         
         // Function to store location in database
         async function storeLocationInDB(latitude, longitude) {
             try {
                 const response = await fetch('store_location.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify({
                         latitude: latitude,
                         longitude: longitude
                     })
                 });
                 
                 const result = await response.json();
                 if (!result.success) {
                     throw new Error(result.message);
                 }
                 return result;
             } catch (error) {
                 console.error('Error storing location:', error);
                 throw error;
             }
         }
         
         // Function to get stored locations
         async function getStoredLocations() {
             try {
                 const response = await fetch('get_locations.php');
                 const data = await response.json();
                 return data.locations || [];
             } catch (error) {
                 console.error('Error getting locations:', error);
                 return [];
             }
         }
         
         // Function to update progress
         async function updateProgress() {
             const locations = await getStoredLocations();
             const progress = (locations.length / 3) * 100;
             document.querySelector('.progress').style.width = `${progress}%`;
             
             // Update location history display
             const historyContainer = document.querySelector('.location-history');
             historyContainer.innerHTML = '';
             locations.forEach(loc => {
                 const entry = document.createElement('div');
                 entry.className = 'location-entry';
                 entry.textContent = `Date: ${new Date(loc.time).toLocaleDateString()} - Latitude: ${loc.lat}, Longitude: ${loc.long}`;
                 historyContainer.appendChild(entry);
             });
             
             // Check if we have 3 days of data
             if (locations.length >= 3) {
                 document.getElementById('startTracking').disabled = true;
                 document.getElementById('startTracking').textContent = 'Location Tracking Complete';
             }
         }
         
         // Function to get location using IP-based geolocation
         async function getLocationByIP() {
             try {
                 const response = await fetch('https://ipapi.co/json/');
                 const data = await response.json();
                 return {
                     latitude: data.latitude,
                     longitude: data.longitude
                 };
             } catch (error) {
                 console.error('Error getting location by IP:', error);
                 return null;
             }
         }
         
         // Function to start location tracking
         async function startLocationTracking() {
             const isSecure = window.location.protocol === 'https:';
             
             if (isSecure && navigator.geolocation) {
                 // Try browser geolocation first if on HTTPS
                 navigator.geolocation.getCurrentPosition(
                     async function(position) {
                         try {
                             await storeLocationInDB(
                                 position.coords.latitude,
                                 position.coords.longitude
                             );
                             await updateProgress();
                             alert('Location recorded successfully!');
                         } catch (error) {
                             alert('Error storing location: ' + error.message);
                         }
                     },
                     async function(error) {
                         console.error('Browser geolocation error:', error);
                         // Fallback to IP-based geolocation
                         const ipLocation = await getLocationByIP();
                         if (ipLocation) {
                             try {
                                 await storeLocationInDB(
                                     ipLocation.latitude,
                                     ipLocation.longitude
                                 );
                                 await updateProgress();
                                 alert('Location recorded using IP-based geolocation!');
                             } catch (error) {
                                 alert('Error storing location: ' + error.message);
                             }
                         } else {
                             alert('Could not determine your location. Please try again later.');
                         }
                     },
                     {
                         enableHighAccuracy: true,
                         timeout: 5000,
                         maximumAge: 0
                     }
                 );
             } else {
                 // Use IP-based geolocation if not on HTTPS
                 const ipLocation = await getLocationByIP();
                 if (ipLocation) {
                     try {
                         await storeLocationInDB(
                             ipLocation.latitude,
                             ipLocation.longitude
                         );
                         await updateProgress();
                         alert('Location recorded using IP-based geolocation!');
                     } catch (error) {
                         alert('Error storing location: ' + error.message);
                     }
                 } else {
                     alert('Could not determine your location. Please try again later.');
                 }
             }
         }
         
         // Initialize progress on page load
         document.addEventListener('DOMContentLoaded', updateProgress);
         
         buttons.forEach(button => {
             button.addEventListener('click', function() {
                 showModal();
             });
         });
       </script>
       
       <!-- Information Modal -->
       <div id="infoModal" class="modal">
           <div class="modal-content">
               <span class="close" onclick="closeModal()">&times;</span>
               <h2>Proof of Residence Verification Process</h2>
               <p>To verify your residence, we need to track your location for 3 consecutive days. This helps us ensure that you are actually residing at the address you claim.</p>
               
               <div class="progress-container">
                   <h3>Progress: <span id="progressText">0/3 days</span></h3>
                   <div class="progress-bar">
                       <div class="progress"></div>
                   </div>
               </div>
               
               <div class="location-history">
                   <h3>Location History</h3>
                   <!-- Location entries will be added here -->
               </div>
               
               <button id="startTracking" onclick="startLocationTracking()" style="margin-top: 20px; padding: 10px 20px;">
                   Record Today's Location
               </button>
           </div>
       </div>
       
       <!-- Map container -->
       <div id="map" style="height: 400px; width: 100%; margin: 20px; display: none; border: 1px solid #ccc;"></div>
   </body>
 </html>