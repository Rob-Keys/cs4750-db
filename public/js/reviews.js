let reviewList;
let emptyMessage;
let currentUser = null;
let followingSet = new Set();

document.addEventListener('DOMContentLoaded', () => {
    reviewList = document.getElementById('review_list');
    emptyMessage = document.getElementById('review-empty');
    const searchForm = document.getElementById('review-search-form');
    const searchInput = document.getElementById('review-search-input');
    const resetButton = document.getElementById('reset-reviews');

    currentUser = sessionStorage.getItem('username') ?? null;

    const initPromise = currentUser
        ? loadFollowingForCurrentUser()
        : Promise.resolve();

    initPromise.then(() => {
        fetchReviews("");
    });

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const q = searchInput.value.trim();
        fetchReviews(q);
    });

    resetButton.addEventListener('click', () => {
        searchInput.value = "";
        fetchReviews("");
    });

    addWriteReviewButton();
});

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
        console.error('Fetch error:', err);
        alert('Unexpected error fetching reviews: ' + err.message);
    });
}

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

        const metaText = document.createElement('span');
        metaText.textContent = `By ${review.author} • ${review.date_written}`;
        meta.appendChild(metaText);

        if (currentUser && currentUser !== review.author) {
            const followBtn = document.createElement('button');
            followBtn.className = 'button button-secondary';
            followBtn.style.marginLeft = '0.75rem';

            const isFollowing = followingSet.has(review.author);
            followBtn.textContent = isFollowing ? 'Following' : 'Follow';

            followBtn.addEventListener('click', () => {
                toggleFollow(review.author, followBtn);
            });

            meta.appendChild(followBtn);
        }

        const itinerary = document.createElement('div');
        itinerary.className = 'card-body';
        itinerary.textContent = review.itinerary
            ? `Itinerary: ${review.itinerary}`
            : 'Itinerary: (no locations recorded)';

        li.appendChild(header);
        li.appendChild(meta);
        li.appendChild(itinerary);

        const commentsSection = buildCommentsSection(review.review_id);
        li.appendChild(commentsSection);

        reviewList.appendChild(li);
    });
}

function addWriteReviewButton() {
    const writeReviewForm = document.getElementById('write-review-form');
    const username = sessionStorage.getItem('username') ?? null;
    if (username) {
        writeReviewForm.innerHTML = `
        <a href="newreview.html" class="button">Write Review</a>
        `;
    }
}


function loadFollowingForCurrentUser() {
    return fetch('/api/getFollowingForUser', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: currentUser })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.warn('Error loading following:', data.error);
            return;
        }
        followingSet = new Set(data.data || []);
    })
    .catch(err => {
        console.error('Error fetching following:', err);
    });
}

function toggleFollow(author, buttonEl) {
    const isFollowing = followingSet.has(author);
    const endpoint = isFollowing ? '/api/unfollowUser' : '/api/followUser';

    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ followee_username: author })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error updating follow status: ' + data.error);
            return;
        }

        if (isFollowing) {
            followingSet.delete(author);
            buttonEl.textContent = 'Follow';
        } else {
            followingSet.add(author);
            buttonEl.textContent = 'Following';
        }
    })
    .catch(err => {
        console.error('Toggle follow error:', err);
        alert('Unexpected error updating follow status.');
    });
}


function buildCommentsSection(reviewId) {
    const container = document.createElement('div');
    container.className = 'mt-sm';

    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'button button-secondary';
    toggleBtn.textContent = 'Show comments';

    const commentsList = document.createElement('ul');
    commentsList.className = 'list mt-sm';
    commentsList.style.display = 'none';

    let visible = false;

    toggleBtn.addEventListener('click', () => {
        visible = !visible;
        commentsList.style.display = visible ? 'block' : 'none';
        toggleBtn.textContent = visible ? 'Hide comments' : 'Show comments';

        if (visible && commentsList.childElementCount === 0) {
            loadCommentsForReview(reviewId, commentsList);
        }
    });

    container.appendChild(toggleBtn);
    container.appendChild(commentsList);

    if (currentUser) {
        const form = document.createElement('form');
        form.className = 'mt-sm';

        const textarea = document.createElement('textarea');
        textarea.className = 'input';
        textarea.style.minHeight = '60px';
        textarea.placeholder = 'Leave a comment...';

        const submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.className = 'button mt-sm';
        submitBtn.textContent = 'Post Comment';

        form.appendChild(textarea);
        form.appendChild(submitBtn);

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const text = textarea.value.trim();
            if (!text) return;

            addComment(reviewId, text)
                .then(() => {
                    textarea.value = '';
                    commentsList.innerHTML = '';
                    loadCommentsForReview(reviewId, commentsList);
                })
                .catch(err => {
                    console.error('Add comment error:', err);
                    alert('Error adding comment.');
                });
        });

        container.appendChild(form);
    }

    return container;
}

function loadCommentsForReview(reviewId, listEl) {
    fetch('/api/getCommentsForReview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ review_id: reviewId, limit: 20, offset: 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.warn('Error loading comments:', data.error);
            return;
        }

        const comments = data.data || [];
        listEl.innerHTML = '';

        if (comments.length === 0) {
            const li = document.createElement('li');
            li.className = 'text-muted';
            li.textContent = 'No comments yet.';
            listEl.appendChild(li);
            return;
        }

        comments.forEach(comment => {
            const li = document.createElement('li');
            li.className = 'card';

            const header = document.createElement('div');
            header.className = 'card-meta';
            header.textContent = `${comment.commenter_username} • ${comment.date_written}`;

            const body = document.createElement('div');
            body.className = 'card-body';
            body.textContent = comment.comment_text;

            li.appendChild(header);
            li.appendChild(body);

            listEl.appendChild(li);
        });
    })
    .catch(err => {
        console.error('Comments fetch error:', err);
    });
}

function addComment(reviewId, text) {
    return fetch('/api/addComment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ review_id: reviewId, comment_text: text })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        return data;
    });
}
