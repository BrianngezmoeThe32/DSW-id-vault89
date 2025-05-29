@echo off
echo Setting up HTTPS for XAMPP...

REM Create directories if they don't exist
if not exist "C:\xampp\apache\conf\ssl.crt" mkdir "C:\xampp\apache\conf\ssl.crt"
if not exist "C:\xampp\apache\conf\ssl.key" mkdir "C:\xampp\apache\conf\ssl.key"

REM Generate SSL certificate
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout "C:\xampp\apache\conf\ssl.key\server.key" -out "C:\xampp\apache\conf\ssl.crt\server.crt" -subj "/CN=localhost"

echo SSL certificate generated successfully!
echo Please follow these steps to complete the setup:
echo 1. Open C:\xampp\apache\conf\httpd.conf
echo 2. Uncomment these lines (remove # from the start):
echo    LoadModule ssl_module modules/mod_ssl.so
echo    LoadModule socache_shmcb_module modules/mod_socache_shmcb.so
echo 3. Add these lines at the end of the file:
echo    Listen 443
echo    ^<VirtualHost *:443^>
echo        DocumentRoot "C:/xampp/htdocs"
echo        ServerName localhost
echo        SSLEngine on
echo        SSLCertificateFile "conf/ssl.crt/server.crt"
echo        SSLCertificateKeyFile "conf/ssl.key/server.key"
echo    ^</VirtualHost^>
echo 4. Restart Apache from XAMPP Control Panel
echo.
echo After completing these steps, you can access your site at https://localhost
pause 