import { getUserLists } from './shared.js';

document.addEventListener('DOMContentLoaded', function() {
    fetchLists();
});

async function fetchLists() {
    const list_container = document.getElementById('list_container');
    const data = await getUserLists();

    const listsMap = {};
    data.forEach(row => {
        if (!listsMap[row.list_id]) {
            listsMap[row.list_id] = {
                list_id: row.list_id,
                list_title: row.list_title,
                trips: []
            };
        }
        if (row.trip_id) {
            listsMap[row.list_id].trips.push({ trip_id: row.trip_id, trip_title: row.trip_title });
        }
    });

    Object.values(listsMap).forEach(list => {
        const li = document.createElement('li');
        li.style.marginBottom = '1rem';

        const listHeader = document.createElement('div');
        listHeader.style.display = 'flex';
        listHeader.style.justifyContent = 'space-between';
        listHeader.style.alignItems = 'center';

        const listTitle = document.createElement('div');
        listTitle.textContent = list.list_title;
        listTitle.style.fontWeight = '500';

        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = 'Delete';
        deleteBtn.style.padding = '0.25rem 0.5rem';
        deleteBtn.style.backgroundColor = '#dc3545';
        deleteBtn.style.color = 'white';
        deleteBtn.style.border = 'none';
        deleteBtn.style.borderRadius = '4px';
        deleteBtn.style.cursor = 'pointer';
        deleteBtn.onclick = () => deleteList(list.list_id, li);

        listHeader.appendChild(listTitle);
        listHeader.appendChild(deleteBtn);
        li.appendChild(listHeader);

        if (list.trips.length > 0) {
            const tripList = document.createElement('ul');
            tripList.style.marginLeft = '2rem';
            tripList.style.marginTop = '0.5rem';
            list.trips.forEach(trip => {
                const tripLi = document.createElement('li');
                tripLi.textContent = trip.trip_title;
                tripList.appendChild(tripLi);
            });
            li.appendChild(tripList);
        }

        list_container.appendChild(li);
    });
}

async function deleteList(list_id, listItem) {
    if (!confirm('Are you sure you want to delete this list?')) {
        return;
    }

    try {
        const response = await fetch('/api/deleteList', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ list_id })
        });

        const result = await response.json();

        if (result.error) {
            alert('Error deleting list: ' + result.error);
        } else {
            listItem.remove();
        }
    } catch (error) {
        console.error('Error deleting list:', error);
        alert('Error deleting list');
    }
}

