document.addEventListener('DOMContentLoaded', () => {
    createSignOutButton();
    loadUserProfile();
});

function createSignOutButton() {
    const signoutBtn = document.getElementById('signout-btn');

    if (signoutBtn) {
        signoutBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('/api/signout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }).then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error signing out: ' + data.error);
                    } else {
                        // Clear session storage
                        sessionStorage.removeItem('username');

                        // Redirect to home page
                        window.location.href = 'home.html';
                    }
                });
            } catch (error) {
                console.error('Error during signout:', error);
                alert('An error occurred while signing out');
            }
        });
    }
}

function loadUserProfile() {
    let username_field = document.getElementById('user-name');
    username_field.textContent = sessionStorage.getItem('username');
}