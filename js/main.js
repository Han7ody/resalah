// js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // OTP Input Handler
    const otpInputs = document.querySelectorAll('.otp-input');
    
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            if (e.target.value.length === 1) {
                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            }
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && e.target.value === '') {
                if (index > 0) {
                    otpInputs[index - 1].focus();
                }
            }
        });
    });

    // Form Validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'error');
            }
        });
    });

    // Password Strength Indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrength(strength);
        });
    }

    // Auto-hide alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
});

function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    return strength;
}

function updatePasswordStrength(strength) {
    const indicator = document.getElementById('password-strength');
    if (!indicator) return;

    const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'];
    
    indicator.textContent = levels[strength - 1] || 'Very Weak';
    indicator.style.color = colors[strength - 1] || colors[0];
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.auth-container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

// Resend OTP functionality
function resendOTP() {
    fetch('process-auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=resend_otp'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('New OTP sent to your email', 'success');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('An error occurred. Please try again.', 'error');
    });
}
