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

async function renderReviews(reviews) {
    reviewList.innerHTML = '';

    if (!reviews || reviews.length === 0) {
        emptyMessage.style.display = 'block';
        return;
    }

    emptyMessage.style.display = 'none';

    for (const review of reviews) {
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
        
        const reviewBody = document.createElement('div');
        reviewBody.className = 'review-text';
        reviewBody.textContent = review.written_review || "(No reviw text provided)";

        li.appendChild(header);
        li.appendChild(meta);
        li.appendChild(reviewBody);
        li.appendChild(itinerary);

        const pictures = await fetchPicturesForTrip(review.trip_id);
        if (pictures && pictures.length > 0) {
            const imageContainer = document.createElement('div');
            imageContainer.style.display = 'flex';
            imageContainer.style.gap = '0.5rem';
            imageContainer.style.flexWrap = 'wrap';
            imageContainer.style.marginTop = '0.75rem';

            pictures.forEach(picture => {
                const img = document.createElement('img');
                img.src = `/api/getPicture?picture_id=${picture.picture_id}`;
                img.alt = picture.pic_caption || 'Trip photo';
                img.style.width = '150px';
                img.style.height = '150px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '4px';
                img.style.cursor = 'pointer';

                img.onclick = function() {
                    window.open(this.src, '_blank');
                };

                imageContainer.appendChild(img);
            });

            li.appendChild(imageContainer);
        }

        if (currentUser && currentUser === review.author) {
            const actions = document.createElement('div');
            actions.className = 'mt-sm';

            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'button button-secondary';
            editBtn.textContent = 'Edit Review';
            editBtn.style.marginRight = '0.5rem';

            editBtn.addEventListener('click', () => {
                handleEditReview(review, reviewBody, ratingPill);
            });

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'button button-secondary';
            deleteBtn.textContent = 'Delete Review';

            deleteBtn.addEventListener('click', () => {
                handleDeleteReview(review, li);
            });

            actions.appendChild(editBtn);
            actions.appendChild(deleteBtn);
            li.appendChild(actions);
        }

        const commentsSection = buildCommentsSection(review.review_id);
        li.appendChild(commentsSection);

        reviewList.appendChild(li);
    };
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
function handleEditReview(review, reviewBodyEl, ratingPillEl) {
    const currentText = review.written_review || '';
    const currentRating = review.rating;

    const newText = prompt('Update your review text:', currentText);
    if (newText === null) {
        // User hit cancel
        return;
    }

    const newRatingStr = prompt('Update rating (1-5):', String(currentRating));
    if (newRatingStr === null) {
        return;
    }

    const newRating = parseInt(newRatingStr, 10);
    if (Number.isNaN(newRating) || newRating < 1 || newRating > 5) {
        alert('Rating must be a number between 1 and 5.');
        return;
    }

    fetch('/api/updateReview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            review_id: review.review_id,
            rating: newRating,
            review_text: newText
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert('Error updating review: ' + data.error);
            return;
        }

        review.rating = newRating;
        review.written_review = newText;

        ratingPillEl.textContent = `★ ${newRating}`;
        reviewBodyEl.textContent =
            newText && newText.trim() !== ""
                ? newText
                : "(No review text provided)";

        alert('Review updated successfully!');
    })
    .catch(err => {
        console.error('Update review error:', err);
        alert('Unexpected error updating review: ' + err.message);
    });
}

function handleDeleteReview(review, listItemEl) {
    const confirmed = confirm('Are you sure you want to delete this review? This cannot be undone.');
    if (!confirmed) {
        return;
    }

    fetch('/api/deleteReview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ review_id: review.review_id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert('Error deleting review: ' + data.error);
            return;
        }

        if (listItemEl && listItemEl.parentNode) {
            listItemEl.parentNode.removeChild(listItemEl);
        }

        alert('Review deleted successfully.');
    })
    .catch(err => {
        console.error('Delete review error:', err);
        alert('Unexpected error deleting review: ' + err.message);
    });
}

async function fetchPicturesForTrip(trip_id) {
    try {
        const response = await fetch('/api/getPicturesForTrip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trip_id })
        });
        const result = await response.json();
        return result.data || [];
    } catch (error) {
        console.error('Error fetching pictures:', error);
        return [];
    }
}
