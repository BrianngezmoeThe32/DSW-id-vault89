document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signup-form');
    const loginForm = document.getElementById('login-form');
    const showLoginBtn = document.getElementById('show-login');
    const showSignupBtn = document.getElementById('show-signup');
    const idPictureInput = document.getElementById('id-picture');
    const previewContainer = document.getElementById('preview-container');
    const preview = document.getElementById('preview');

    
    function switchForm(showForm, hideForm) {
        showForm.style.display = 'block';
        hideForm.style.display = 'none';
    }

    
    showLoginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        switchForm(loginForm, signupForm);
    });

    showSignupBtn.addEventListener('click', (e) => {
        e.preventDefault();
        switchForm(signupForm, loginForm);
    });

   
   
    idPictureInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
           
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            
            if (form.id === 'signup-form') {
                const password = form.querySelector('input[type="password"]').value;
                const confirmPassword = form.querySelectorAll('input[type="password"]')[1].value;
                
                if (password !== confirmPassword) {
                    alert('Passwords do not match!');
                    return;
                }

                
                const studentNumber = form.querySelector('input[placeholder="Student Number"]').value;
                if (!/^\d{8}$/.test(studentNumber)) {
                    alert('Student number must be 8 digits!');
                    return;
                }
            }
            
            
            console.log('Form submitted:', data);
            alert('Form submitted successfully!');
        });
    });
});
