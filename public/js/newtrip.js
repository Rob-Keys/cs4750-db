let selectedLocations = [];

document.addEventListener('DOMContentLoaded', function() {
    handleTripForm();

    createLocationModal();
    
    document.getElementById('add-location-btn').addEventListener('click', function() {
        openLocationModal();
    });
});

function addLocationToList(location) {
    if (selectedLocations.find(l => l.location_id === location.location_id)) {
        alert('This location has already been added to your trip.');
        return;
    }

    selectedLocations.push(location);

    const selectedLocationsList = document.getElementById('selected-locations');
    
    const li = document.createElement('li');
    li.textContent = `${location.location_name} - ${location.country}`;
    li.dataset.locationId = location.location_id;

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.className = 'button button-secondary';
    deleteBtn.style.marginLeft = '1rem';
    deleteBtn.onclick = function() {
        li.remove();
        selectedLocations = selectedLocations.filter(loc => loc.location_id !== location.location_id);
    };

    li.appendChild(deleteBtn);
    selectedLocationsList.appendChild(li);
}

function handleTripForm() {
    const form = document.getElementById('new-trip-form');
    const message = document.getElementById('new-trip-message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const trip_title = document.getElementById('trip_title').value;
        const start_date = document.getElementById('start_date').value;
        const end_date = document.getElementById('end_date').value;
        const username = sessionStorage.getItem('username');
        const location_ids = selectedLocations.map(loc => loc.location_id);

        fetch('/api/createTrip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trip_title, start_date, end_date, username, location_ids })
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
                document.getElementById('selected-locations').innerHTML = '';
                selectedLocations = [];

                setTimeout(() => {
                    window.location.href = 'trip.html';
                }, 1500);
            }
        })
    });
}

function searchLocations(searchTerm = '') {
    const modalLocationsList = document.getElementById('modal-locations-list');
    modalLocationsList.innerHTML = '';

    fetch('/api/searchLocations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ q: searchTerm, limit: 100, offset: 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching locations: ' + data.error);
            return;
        }
        data.data.forEach(location => {
            const li = document.createElement('li');
            li.textContent = `${location.location_name} - ${location.country}`;
            
            const addBtn = document.createElement('button');
            addBtn.textContent = 'Add';
            addBtn.className = 'button';
            addBtn.style.marginLeft = '1rem';
            addBtn.onclick = function() {
                if (typeof addLocationToList === 'function') {
                    addLocationToList(location);
                    closeLocationModal();
                }
            };

            li.appendChild(addBtn);
            modalLocationsList.appendChild(li);
        });
    });
}

function createLocationModal() {
    const modalHTML = `
        <div id="location-modal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>Add Location to Trip</h2>
                <input type="text" id="location-search" placeholder="Search for a location..." class="input" style="width: 100%; margin-bottom: 1rem;">
                <ul id="modal-locations-list" class="location-list"></ul>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    document.querySelector('#location-modal .close-btn').onclick = closeLocationModal;
    document.getElementById('location-search').addEventListener('input', (e) => searchLocations(e.target.value));
}

function openLocationModal() {
    document.getElementById('location-modal').style.display = 'block';
    searchLocations();
}

function closeLocationModal() {
    document.getElementById('location-modal').style.display = 'none';
}