document.addEventListener('DOMContentLoaded', function() {
    console.log('Home page loaded');

    setupHeroCarousel();
    setupHomeFeed();
});

function setupHeroCarousel() {
    const heroImage = document.getElementById('hero-image');
    const heroCaption = document.getElementById('hero-caption');

    if (!heroImage || !heroCaption) {
        return;
    }

    const slides = [
        { src: 'images/vacation1.jpg', caption: 'Sunset beach getaway' },
        { src: 'images/vacation2.jpg', caption: 'Mountain escape with friends' },
        { src: 'images/vacation3.jpg', caption: 'City lights and late-night walks' },
        { src: 'images/vacation4.jpg', caption: 'Road trip along the coast' }
    ];

    let index = 0;

    function showSlide(i) {
        const slide = slides[i];
        heroImage.src = slide.src;
        heroImage.alt = slide.caption;
        heroCaption.textContent = slide.caption;
    }

    showSlide(index);

    setInterval(() => {
        index = (index + 1) % slides.length;
        showSlide(index);
    }, 4000);
}

function setupHomeFeed() {
    const username = sessionStorage.getItem('username') ?? null;
    if (!username) {
        return;
    }

    fetch('/api/getHomeFeed', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ limit: 10, offset: 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.warn('Error loading home feed:', data.error);
            return;
        }
        renderHomeFeed(data.data || []);
    })
    .catch(err => {
        console.error('Home feed fetch error:', err);
    });
}

function renderHomeFeed(reviews) {
    const list = document.getElementById('home-feed-list');
    const empty = document.getElementById('home-feed-empty');

    list.innerHTML = '';

    if (!reviews || reviews.length === 0) {
        empty.textContent = 'No recent reviews from people you follow yet.';
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';

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

        list.appendChild(li);
    });
}
