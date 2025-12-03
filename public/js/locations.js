document.addEventListener('DOMContentLoaded', function() {
    console.log('Locations page loaded');

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
    })
    
    add_location_button = document.getElementById('add-location');
    add_location_button.addEventListener('click', function() {
        const location_name = prompt("Enter location name:");
        const country = prompt("Enter country:");
        const longitude = prompt("Enter longitude:");
        const latitude = prompt("Enter latitude:");

        fetch('/api/createLocation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ location_name, country, longitude, latitude })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error adding location: ' + data.error);
            } else {
                alert('Location added successfully');
                const li = document.createElement('li');
                li.textContent = `${location_name} - ${country}`;
                location_list.appendChild(li);
            }
        })
    });
});