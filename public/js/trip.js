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
            li.textContent = `${trip.trip_title} - ${trip.start_date} to ${trip.end_date}`;
            trip_list.appendChild(li);
        });
    });
}

function createTripButton(){
    add_trip_button = document.getElementById('add-trip');
    add_trip_button.addEventListener('click', function() {
        const trip_title = prompt("Enter trip title:");
        const start_date = prompt("Enter start date (YYYY-MM-DD):");
        const end_date = prompt("Enter end date (YYYY-MM-DD):");

        fetch('/api/createTrip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trip_title, start_date, end_date })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error adding trip: ' + data.error);
            } else {
                alert('Trip added successfully');
                const li = document.createElement('li');
                li.textContent = `${trip_title} - ${start_date} to ${end_date}`;
                trip_list.appendChild(li);
            }
        })
    });
}
