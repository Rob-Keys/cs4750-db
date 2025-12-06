import { getUserTrips } from './shared.js';

document.addEventListener('DOMContentLoaded', function() {
    fetchTrips();
});

function fetchTrips() {
    let trip_list = document.getElementById('trip_list');
    getUserTrips().then(data => {
        data.forEach(trip => {
            const li = document.createElement('li');
            li.textContent = `${trip.trip_title}: ${trip.start_date} to ${trip.end_date}`;
            trip_list.appendChild(li);
        });
    });
}
