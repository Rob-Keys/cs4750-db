document.addEventListener('DOMContentLoaded', function() {
    loadTripsForSelector();
    handleLocationForm();
});

function loadTripsForSelector() {
    const tripSelector = document.getElementById('trip_id');
    const username = sessionStorage.getItem('username');
    if (!username) {
        alert('User not logged in');
        return;
    }

    // Fetch trips to populate selector
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
            const option = document.createElement('option');
            option.value = trip.trip_id;
            option.textContent = trip.trip_title;
            tripSelector.appendChild(option);
        });
    });
}

function handleLocationForm() {
    const form = document.getElementById('new-location-form');
    const message = document.getElementById('new-location-message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const location_name = document.getElementById('location_name').value;
        const country = document.getElementById('country').value;
        const longitude = document.getElementById('longitude').value;
        const latitude = document.getElementById('latitude').value;
        const trip_id = document.getElementById('trip_id').value;

        fetch('/api/createLocation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ location_name, country, longitude, latitude, trip_id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                message.textContent = 'Error: ' + data.error;
                message.style.display = 'block';
            } else {
                message.textContent = 'Location created successfully!';
                message.style.display = 'block';
                form.reset();
                window.location.href = 'locations.html';
            }
        })
    });
}
