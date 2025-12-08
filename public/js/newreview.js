document.addEventListener('DOMContentLoaded', function() {
    loadTripsForSelector();
    handleReviewForm();
});

function loadTripsForSelector() {
    const tripSelect = document.getElementById('trip-select');

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
    const reviewImages = document.getElementById('review-images');
    let existingReviewId = null;

    tripSelect.addEventListener('change', async function() {
        const trip_id = tripSelect.value;
        if (!trip_id) {
            ratingSelect.value = '';
            reviewText.value = '';
            existingReviewId = null;
            return;
        }

        try {
            const response = await fetch('/api/getReviewByTripId', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ trip_id })
            });
            const result = await response.json();

            if (result.data) {
                existingReviewId = result.data.review_id;
                ratingSelect.value = result.data.rating;
                reviewText.value = result.data.written_review || '';
            } else {
                existingReviewId = null;
                ratingSelect.value = '';
                reviewText.value = '';
            }
        } catch (error) {
            console.error('Error fetching existing review:', error);
        }
    });

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

        const formData = new FormData();
        formData.append('trip_id', trip_id);
        formData.append('rating', rating);
        formData.append('review_text', review_text);

        if (existingReviewId) {
            formData.append('review_id', existingReviewId);
        }

        if (reviewImages.files.length > 0) {
            for (let i = 0; i < reviewImages.files.length; i++) {
                formData.append('review_images[]', reviewImages.files[i]);
            }
        }

        const endpoint = existingReviewId ? '/api/updateReview' : '/api/createReview';

        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                const message = existingReviewId ? 'Review updated successfully!' : 'Review submitted successfully!';
                alert(message);
                form.reset();
                setTimeout(() => {
                    window.location.href = 'reviews.html';
                }, 1500);
            }
        })
    });
}
