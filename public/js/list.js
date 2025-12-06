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

function createListButton(){
    add_list_button = document.getElementById('add-list');
    add_list_button.addEventListener('click', function() {
        const list_title = prompt("Enter list title:");

        fetch('/api/createList', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ list_title })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error adding list: ' + data.error);
            } else {
                alert('List added successfully');
                const li = document.createElement('li');
                li.textContent = `${list_title}`;
                list_container.appendChild(li);
            }
        })
    });
}
