document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;

            fetch('/api/loginUser', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password: password})
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert('Error logging in: ' + data.error);
                } else {
                    sessionStorage.setItem('username', username);
                    window.location.href = 'userprofile.html';
                }
            })
            .catch(error => {
                alert('An error occurred while logging in');
            });
        } catch (error) {
            alert('An error occurred while logging in');
        }
    });
});

