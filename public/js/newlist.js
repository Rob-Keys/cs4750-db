document.addEventListener('DOMContentLoaded', function() {
    loadTripsForSelector();
    handleListForm();
});

function loadTripsForSelector() {
    const tripSelector = document.getElementById('trip_selector');

    // Fetch trips to populate selector
    fetch('/api/getTripsForUser', {
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
        data.data.forEach(trip => {
            const option = document.createElement('option');
            option.value = trip.trip_id;
            option.textContent = trip.trip_title;
            tripSelector.appendChild(option);
        });
    });
}

function handleListForm() {
    const form = document.getElementById('new-list-form');
    const message = document.getElementById('new-list-message');
    const tripSelector = document.getElementById('trip_selector');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const list_title = document.getElementById('list_title').value;
        const selectedTrips = Array.from(tripSelector.selectedOptions).map(opt => opt.value);

        fetch('/api/createList', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ list_title, trip_ids: selectedTrips })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                message.textContent = 'Error: ' + data.error;
                message.style.display = 'block';
            } else {
                message.textContent = 'List created successfully!';
                message.style.display = 'block';
                form.reset();
                setTimeout(() => {
                    window.location.href = 'list.html';
                }, 1500);
            }
        })
    });
}
