import { getUserTrips } from './shared.js';

document.addEventListener('DOMContentLoaded', function() {
    fetchTrips();
});

async function fetchTrips() {
    let trip_list = document.getElementById('trip_list');
    const data = await getUserTrips();

    for (const trip of data) {
        const li = document.createElement('li');
        li.style.marginBottom = '1.5rem';

        const tripHeader = document.createElement('div');
        tripHeader.style.display = 'flex';
        tripHeader.style.justifyContent = 'space-between';
        tripHeader.style.alignItems = 'center';
        tripHeader.style.marginBottom = '0.5rem';

        const tripInfo = document.createElement('div');
        tripInfo.textContent = `${trip.trip_title}: ${trip.start_date} to ${trip.end_date}`;
        tripInfo.style.fontWeight = '500';

        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = 'Delete';
        deleteBtn.style.padding = '0.25rem 0.5rem';
        deleteBtn.style.backgroundColor = '#dc3545';
        deleteBtn.style.color = 'white';
        deleteBtn.style.border = 'none';
        deleteBtn.style.borderRadius = '4px';
        deleteBtn.style.cursor = 'pointer';
        deleteBtn.onclick = () => deleteTrip(trip.trip_id, li);

        tripHeader.appendChild(tripInfo);
        tripHeader.appendChild(deleteBtn);
        li.appendChild(tripHeader);

        const pictures = await fetchPicturesForTrip(trip.trip_id);

        if (pictures && pictures.length > 0) {
            const imageContainer = document.createElement('div');
            imageContainer.style.display = 'flex';
            imageContainer.style.gap = '0.5rem';
            imageContainer.style.flexWrap = 'wrap';
            imageContainer.style.marginTop = '0.5rem';

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

        trip_list.appendChild(li);
    }
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

async function deleteTrip(trip_id, listItem) {
    if (!confirm('Are you sure you want to delete this trip? This will also delete any reviews and pictures associated with it.')) {
        return;
    }

    try {
        const response = await fetch('/api/deleteTrip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trip_id })
        });

        const result = await response.json();

        if (result.error) {
            alert('Error deleting trip: ' + result.error);
        } else {
            listItem.remove();
        }
    } catch (error) {
        console.error('Error deleting trip:', error);
        alert('Error deleting trip');
    }
}
