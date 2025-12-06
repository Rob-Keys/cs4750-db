document.addEventListener('DOMContentLoaded', function() {
    loadTripsForSelector();
    handleReviewForm();
});

function loadTripsForSelector() {
    const tripSelect = document.getElementById('trip-select');

    // Fetch trips to populate selector
    fetch('/api/searchTrips', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ q: "", limit: 100, offset: 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching trips: ' + data.error);
            tripSelect.innerHTML = '<option value="">Error loading trips</option>';
            return;
        }
        tripSelect.innerHTML = '<option value="">Select a trip</option>';
        data.data.forEach(trip => {
            const option = document.createElement('option');
            option.value = trip.trip_id;
            option.textContent = trip.trip_title;
            tripSelect.appendChild(option);
        });
    });
}

function handleReviewForm() {
    const form = document.getElementById('new-review-form');
    const tripSelect = document.getElementById('trip-select');
    const ratingSelect = document.getElementById('review-rating');
    const reviewText = document.getElementById('review-text');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const trip_id = tripSelect.value;
        const rating = ratingSelect.value;
        const review_text = reviewText.value;

        if (!trip_id) {
            alert('Please select a trip');
            return;
        }

        if (!rating) {
            alert('Please select a rating');
            return;
        }

        fetch('/api/createReview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trip_id, rating, review_text })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                alert('Review submitted successfully!');
                form.reset();
                setTimeout(() => {
                    window.location.href = 'reviews.html';
                }, 1500);
            }
        })
    });
}
