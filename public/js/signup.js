document.addEventListener('DOMContentLoaded', function() {
    handleSignupForm();
});

function handleSignupForm() {
    const form = document.getElementById('signup-form');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const username = document.getElementById('signup-username').value;
        const email = document.getElementById('signup-email').value;
        const first_name = document.getElementById('signup-first-name').value;
        const last_name = document.getElementById('signup-last-name').value;
        const password = document.getElementById('signup-password').value;
        const passwordConfirm = document.getElementById('signup-password-confirm').value;

        if (password !== passwordConfirm) {
            alert('Passwords do not match');
            return;
        }

        fetch('/api/createUser', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, email, password, first_name, last_name })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                sessionStorage.setItem('username', username);
                window.location.href = 'home.html';
            }
        })
    });
}
