document.addEventListener('DOMContentLoaded', function() {
    fetchLists();
    createListButton();
});

function fetchLists() {
    list_container = document.getElementById('list_container');
    fetch('/api/getListsForUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ q: "", limit: 100, offset: 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error fetching lists: ' + data.error);
            return;
        }
        data.data.forEach(list => {
            const li = document.createElement('li');
            li.textContent = `${list.list_title}`;
            list_container.appendChild(li);
        });
    });
}

