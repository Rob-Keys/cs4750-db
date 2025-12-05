document.addEventListener('DOMContentLoaded', () => {
    console.log('Reviews page loaded');

    const reviewList = document.getElementById('review_list');
    const emptyMessage = document.getElementById('review-empty');
    const searchForm = document.getElementById('review-search-form');
    const searchInput = document.getElementById('review-search-input');
    const resetButton = document.getElementById('reset-reviews');

    function renderReviews(reviews) {
        reviewList.innerHTML = '';

        if (!reviews || reviews.length === 0) {
            emptyMessage.style.display = 'block';
            return;
        }

        emptyMessage.style.display = 'none';

        reviews.forEach(review => {
            const li = document.createElement('li');
            li.className = 'card';

            const header = document.createElement('div');
            header.className = 'card-header';

            const title = document.createElement('div');
            title.className = 'card-title';
            title.textContent = review.trip_title;

            const ratingPill = document.createElement('div');
            ratingPill.className = 'pill';
            ratingPill.textContent = `★ ${review.rating}`;

            header.appendChild(title);
            header.appendChild(ratingPill);

            const meta = document.createElement('div');
            meta.className = 'card-meta';
            meta.textContent = `By ${review.author} • ${review.date_written}`;

            const itinerary = document.createElement('div');
            itinerary.className = 'card-body';
            itinerary.textContent = review.itinerary
                ? `Itinerary: ${review.itinerary}`
                : 'Itinerary: (no locations recorded)';

            li.appendChild(header);
            li.appendChild(meta);
            li.appendChild(itinerary);

            reviewList.appendChild(li);
        });
    }

    function fetchReviews(query = "") {
        fetch('/api/searchReviews', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ q: query, limit: 50, offset: 0 })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error fetching reviews: ' + data.error);
                return;
            }
            renderReviews(data.data);
        })
        .catch(err => {
            console.error('Error fetching reviews:', err);
            alert('Unexpected error fetching reviews.');
        });
    }

    // Initial load
    fetchReviews("");

    // Search
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const q = searchInput.value.trim();
        fetchReviews(q);
    });

    // Reset
    resetButton.addEventListener('click', () => {
        searchInput.value = "";
        fetchReviews("");
    });
});
