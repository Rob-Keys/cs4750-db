export function getUserLists(username = null) {
    const targetUsername = username || sessionStorage.getItem('username');
    return fetch('/api/getListsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: targetUsername })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching lists: ' + data.error);
            return;
        }
        return data.data;
    });
}

export function getUserTrips(username = null) {
    const targetUsername = username || sessionStorage.getItem('username');
    return fetch('/api/getTripsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: targetUsername })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching trips: ' + data.error);
            return;
        }
        return data.data;
    });
}

export function getUserReviews(username = null) {
    const targetUsername = username || sessionStorage.getItem('username');
    return fetch('/api/getReviewsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: targetUsername })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching reviews: ' + data.error);
            return;
        }
        return data.data;
    });
}

export function getUserFollowers(username = null) {
    const targetUsername = username || sessionStorage.getItem('username');
    return fetch('/api/getFollowersForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: targetUsername })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching followers: ' + data.error);
            return [];
        }
        return data.data;
    });
}

export function getUserFollowing(username = null) {
    const targetUsername = username || sessionStorage.getItem('username');
    return fetch('/api/getFollowingForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: targetUsername })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching following: ' + data.error);
            return [];
        }
        return data.data;
    });
}