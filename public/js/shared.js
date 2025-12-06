export function getUserLists() {
    return fetch('/api/getListsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: sessionStorage.getItem('username') })
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

export function getUserTrips() {
    return fetch('/api/getTripsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: sessionStorage.getItem('username') })
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

export function getUserReviews() {
    return fetch('/api/getReviewsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username: sessionStorage.getItem('username') })
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