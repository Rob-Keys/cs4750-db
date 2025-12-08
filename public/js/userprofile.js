import { getUserLists, getUserTrips, getUserReviews, getUserFollowers, getUserFollowing } from './shared.js';

document.addEventListener('DOMContentLoaded', () => {
    createSignOutButton();
    createExportButton();
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
function createExportButton() {
    const exportBtn = document.getElementById('export-reviews');
    if (!exportBtn) return;

    exportBtn.addEventListener('click', () => {
        window.location.href = '/api/exportUserReviews';
    });
}

async function addFollowButton(profileUsername, currentUser) {
    // Check if user is already following
    const followingResponse = await fetch('/api/getFollowingForUser', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: currentUser })
    });
    const followingData = await followingResponse.json();
    const followingSet = new Set(followingData.data || []);
    let isFollowing = followingSet.has(profileUsername);

    // Create follow button
    const buttonContainer = document.querySelector('#user-info');
    const followBtn = document.createElement('button');
    followBtn.className = 'button button-secondary';
    followBtn.style.marginTop = '1rem';
    followBtn.style.width = '20%';
    followBtn.style.justifyContent = 'center';
    followBtn.textContent = isFollowing ? 'Following' : 'Follow';

    followBtn.addEventListener('click', async () => {
        const endpoint = isFollowing ? '/api/unfollowUser' : '/api/followUser';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ followee_username: profileUsername })
            });
            const data = await response.json();

            if (data.error) {
                alert('Error updating follow status: ' + data.error);
                return;
            }

            // Toggle button state
            isFollowing = !isFollowing;
            followBtn.textContent = isFollowing ? 'Following' : 'Follow';

            // Reload to update follower count
            location.reload();
        } catch (err) {
            console.error('Toggle follow error:', err);
            alert('Unexpected error updating follow status.');
        }
    });

    buttonContainer.appendChild(followBtn);
}
function loadUserProfile() {
    let username_field = document.getElementById('user-name');
    let user_trips = document.getElementById('user-trips');
    let user_lists = document.getElementById('user-lists');
    let user_reviews = document.getElementById('user-reviews');
    let user_followers = document.getElementById('user-followers');
    let user_following = document.getElementById('user-following');

    // Check if username is provided in URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const profileUsername = urlParams.get('username') || sessionStorage.getItem('username');
    const currentUser = sessionStorage.getItem('username');

    // Hide export and sign out buttons if viewing someone else's profile
    const exportBtn = document.getElementById('export-reviews');
    const signoutBtn = document.getElementById('signout-btn');
    if (profileUsername !== currentUser) {
        if (exportBtn) exportBtn.style.display = 'none';
        if (signoutBtn) signoutBtn.style.display = 'none';

        // Add follow button if logged in and viewing another user's profile
        if (currentUser) {
            addFollowButton(profileUsername, currentUser);
        }
    }

    username_field.textContent = profileUsername;
    getUserTrips(profileUsername).then(trips => {
        trips.forEach(trip => {
            let li = document.createElement('li');
            li.textContent = `${trip.trip_title} (${trip.start_date} to ${trip.end_date})`;
            user_trips.appendChild(li);
        });
    });
    getUserLists(profileUsername).then(lists => {
        lists.forEach(list => {
            let li = document.createElement('li');
            li.textContent = list.list_title;
            user_lists.appendChild(li);
        });
    });
    getUserReviews(profileUsername).then(reviews => {
        reviews.forEach(review => {
            let li = document.createElement('li');
            li.textContent = `Rating: ${review.rating}, Review: ${review.written_review}, Date: ${review.date_written}`;
            user_reviews.appendChild(li);
        });
    });
    getUserFollowers(profileUsername).then(followers => {
        if (followers && followers.length > 0) {
            followers.forEach(follower => {
                let li = document.createElement('li');

                const followerLink = document.createElement('a');
                followerLink.href = `userprofile.html?username=${encodeURIComponent(follower)}`;
                followerLink.textContent = follower;
                followerLink.style.color = 'inherit';

                li.appendChild(followerLink);
                user_followers.appendChild(li);
            });
        } else {
            let li = document.createElement('li');
            li.textContent = 'No followers yet';
            li.style.fontStyle = 'italic';
            user_followers.appendChild(li);
        }
    });
    getUserFollowing(profileUsername).then(following => {
        if (following && following.length > 0) {
            following.forEach(user => {
                let li = document.createElement('li');

                const followingLink = document.createElement('a');
                followingLink.href = `userprofile.html?username=${encodeURIComponent(user)}`;
                followingLink.textContent = user;
                followingLink.style.color = 'inherit';

                li.appendChild(followingLink);
                user_following.appendChild(li);
            });
        } else {
            let li = document.createElement('li');
            li.textContent = 'Not following anyone yet';
            li.style.fontStyle = 'italic';
            user_following.appendChild(li);
        }
    });
}