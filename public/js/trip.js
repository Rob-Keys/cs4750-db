document.addEventListener('DOMContentLoaded', function() {
    fetchTrips();
    createTripButton();
});

function fetchTrips() {
    let trip_list = document.getElementById('trip_list');
    const username = sessionStorage.getItem('username');
    if (!username) {
        alert('User not logged in');
        return;
    }
    fetch('/api/getTripsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching trips: ' + data.error);
            return;
        }
        data.data.forEach(trip => {
            const li = document.createElement('li');
            li.textContent = `${trip.trip_title}: ${trip.start_date} to ${trip.end_date}`;
            trip_list.appendChild(li);
        });
    });
}
