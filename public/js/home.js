document.addEventListener('DOMContentLoaded', function() {
    console.log('Home: DOMContentLoaded fired');

    setupHeroCarousel();
    setupHomeFeed();
});

function setupHeroCarousel() {
    const heroImage = document.getElementById('hero-image');
    const heroCaption = document.getElementById('hero-caption');

    if (!heroImage || !heroCaption) {
        console.log('Home: hero elements not found, skipping carousel');
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
    const listEl = document.getElementById('home-feed-list');
    const emptyEl = document.getElementById('home-feed-empty');

    console.log('Home: setupHomeFeed called');
    console.log('Home: username from sessionStorage =', username);
    console.log('Home: listEl found?', !!listEl, 'emptyEl found?', !!emptyEl);

    if (!listEl || !emptyEl) {
        console.warn('Home: home feed elements not found in DOM, not fetching feed');
        return;
    }

    if (!username) {
        console.log('Home: no username, showing sign-in message');
        emptyEl.textContent = 'Sign in and follow other users to see their latest reviews here.';
        emptyEl.style.display = 'block';
        return;
    }

    console.log('Home: fetching /api/getHomeFeed...');

    fetch('/api/getHomeFeed', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, limit: 10, offset: 0 })
    })
    .then(res => {
        console.log('Home: getHomeFeed response status =', res.status);
        return res.json();
    })
    .then(data => {
        console.log('Home: getHomeFeed JSON =', data);

        if (data.error) {
            emptyEl.textContent = 'Error loading feed: ' + data.error;
            emptyEl.style.display = 'block';
            return;
        }

        renderHomeFeed(data.data || [], listEl, emptyEl);
    })
    .catch(err => {
        console.error('Home: fetch error for getHomeFeed:', err);
        emptyEl.textContent = 'Unexpected error loading feed.';
        emptyEl.style.display = 'block';
    });
}

function renderHomeFeed(reviews, listEl, emptyEl) {
    listEl.innerHTML = '';

    if (!reviews || reviews.length === 0) {
        console.log('Home: feed is empty array');
        emptyEl.textContent = 'No recent reviews from people you follow yet.';
        emptyEl.style.display = 'block';
        return;
    }

    console.log('Home: rendering', reviews.length, 'feed items');
    emptyEl.style.display = 'none';

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

        const metaText = document.createElement('span');
        const authorLink = document.createElement('a');
        authorLink.href = `userprofile.html?username=${encodeURIComponent(review.author)}`;
        authorLink.textContent = review.author;
        authorLink.style.color = 'inherit';

        metaText.appendChild(authorLink);
        metaText.appendChild(document.createTextNode(` • ${review.date_written}`));
        meta.appendChild(metaText);

        const itinerary = document.createElement('div');
        itinerary.className = 'card-body';
        itinerary.textContent = review.itinerary
            ? `Itinerary: ${review.itinerary}`
            : 'Itinerary: (no locations recorded)';

        li.appendChild(header);
        li.appendChild(meta);
        li.appendChild(itinerary);

        listEl.appendChild(li);
    });
}
