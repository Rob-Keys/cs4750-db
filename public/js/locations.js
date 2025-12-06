document.addEventListener('DOMContentLoaded', function() {
    fetchLocations();
});

function fetchLocations() {
    location_list = document.getElementById('location_list');
    fetch('/api/searchLocations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ q: "", limit: 100, offset: 0 })
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
            location_list.appendChild(li);
        });
    });
}
