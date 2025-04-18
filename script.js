document.addEventListener('DOMContentLoaded', () => {
    const signup = document.getElementById('signup');
    const login = document.getElementById('login');
    const showLoginBtn = document.getElementById('show-login');
    const showSignupBtn = document.getElementById('show-signup');
    const idPictureInput = document.getElementById('id-picture');
    const cameraButton = document.getElementById('camera-capture');
    const cameraStream = document.getElementById('camera-stream');
    const captureButton = document.getElementById('capture-button');
    const retakeButton = document.getElementById('retake-button');
    const previewImg = document.getElementById('preview');
    const captureCanvas = document.getElementById('capture-canvas');
    const camera = document.querySelector('.camera');
    
    let stream = null;
    let users = JSON.parse(localStorage.getItem('users')) || [];

    function showForm(formToShow, formToHide) {
        formToShow.style.display = 'block';
        formToHide.style.display = 'none';
    }

    showLoginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        stopCamera();
        showForm(login, signup);
    });

    showSignupBtn.addEventListener('click', (e) => {
        e.preventDefault();
        showForm(signup, login);
    });

    idPictureInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            stopCamera();
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                cameraStream.style.display = 'none';
                camera.style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    });

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'environment' }, 
                audio: false 
            });
            cameraStream.srcObject = stream;
            cameraStream.style.display = 'block';
            previewImg.style.display = 'none';
            camera.style.display = 'flex';
            captureButton.style.display = 'flex';
            retakeButton.style.display = 'none';
        } catch (err) {
            console.error('Error accessing camera:', err);
            alert('Unable to access camera. Please ensure you have granted camera permissions.');
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        cameraStream.style.display = 'none';
        camera.style.display = 'none';
    }

    cameraButton.addEventListener('click', async () => {
        await startCamera();
    });

    captureButton.addEventListener('click', () => {
        captureCanvas.width = cameraStream.videoWidth;
        captureCanvas.height = cameraStream.videoHeight;
        
        const context = captureCanvas.getContext('2d');
        context.drawImage(cameraStream, 0, 0, captureCanvas.width, captureCanvas.height);
        
        const imageData = captureCanvas.toDataURL('image/jpeg');
        previewImg.src = imageData;
        previewImg.style.display = 'block';
        
        cameraStream.style.display = 'none';
        captureButton.style.display = 'none';
        retakeButton.style.display = 'flex';
    });

    retakeButton.addEventListener('click', async () => {
        previewImg.style.display = 'none';
        await startCamera();
    });

    const signupForm = signup.querySelector('form');
    const loginForm = login.querySelector('form');

    signupForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(signupForm);
        const data = {
            fullName: formData.get('fullName'),
            idNumber: formData.get('idNumber'),
            studentNumber: formData.get('studentNumber'),
            email: formData.get('email'),
            password: formData.get('password'),
            idPicture: previewImg.src
        };

        if (!validateSignup(data)) return;

        if (users.some(user => user.email === data.email)) {
            alert('Email already registered!');
            return;
        }

        users.push(data);
        localStorage.setItem('users', JSON.stringify(users));
        
        alert('Account created successfully! Please login.');
        showForm(login, signup);
    });

    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(loginForm);
        const email = formData.get('email');
        const password = formData.get('password');

        const user = users.find(u => u.email === email && u.password === password);
        
        if (user) {
            localStorage.setItem('currentUser', JSON.stringify(user));
            alert('Login successful!');
            window.location.href = 'dashboard.html';
        } else {
            alert('Invalid email or password!');
        }
    });

    function validateSignup(data) {
        if (!data.fullName || !data.idNumber || !data.studentNumber || !data.email || !data.password) {
            alert('All fields are required!');
            return false;
        }

        if (!/^\d{8}$/.test(data.studentNumber)) {
            alert('Student number must be 8 digits!');
            return false;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            alert('Please enter a valid email address!');
            return false;
        }

        if (data.password.length < 6) {
            alert('Password must be at least 6 characters long!');
            return false;
        }

        if (!data.idPicture || data.idPicture === '#') {
            alert('Please upload or capture an ID picture!');
            return false;
        }

        return true;
    }

    window.addEventListener('beforeunload', stopCamera);
});
