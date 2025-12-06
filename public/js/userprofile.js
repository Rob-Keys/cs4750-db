import { getUserLists, getUserTrips, getUserReviews } from './shared.js';

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
    let user_trips = document.getElementById('user-trips');
    let user_lists = document.getElementById('user-lists');
    let user_reviews = document.getElementById('user-reviews');

    username_field.textContent = sessionStorage.getItem('username');
    getUserTrips().then(trips => {
        trips.forEach(trip => {
            let li = document.createElement('li');
            li.textContent = `${trip.trip_title} (${trip.start_date} to ${trip.end_date})`;
            user_trips.appendChild(li);
        });
    });
    getUserLists().then(lists => {
        lists.forEach(list => {
            let li = document.createElement('li');
            li.textContent = list.list_name;
            user_lists.appendChild(li);
        });
    });
    getUserReviews().then(reviews => {
        reviews.forEach(review => {
            let li = document.createElement('li');
            li.textContent = `Rating: ${review.rating}, Review: ${review.written_review}, Date: ${review.date_written}`;
            user_reviews.appendChild(li);
        });
    });
}