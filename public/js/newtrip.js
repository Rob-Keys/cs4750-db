document.addEventListener('DOMContentLoaded', function() {
    handleTripForm();
});

function handleTripForm() {
    const form = document.getElementById('new-trip-form');
    const message = document.getElementById('new-trip-message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const trip_title = document.getElementById('trip_title').value;
        const start_date = document.getElementById('start_date').value;
        const end_date = document.getElementById('end_date').value;
        const username = sessionStorage.getItem('username');

        fetch('/api/createTrip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trip_title, start_date, end_date, username })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                message.textContent = 'Error: ' + data.error;
                message.style.display = 'block';
            } else {
                message.textContent = 'Trip created successfully!';
                message.style.display = 'block';
                form.reset();
                setTimeout(() => {
                    window.location.href = 'trip.html';
                }, 1500);
            }
        })
    });
}
