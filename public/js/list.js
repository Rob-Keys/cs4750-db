import { getUserLists } from './shared.js';

document.addEventListener('DOMContentLoaded', function() {
    fetchLists();
    createListButton();
});

function fetchLists() {
    list_container = document.getElementById('list_container');
    data = getUserLists().then(data => {
        data.forEach(list => {
            const li = document.createElement('li');
            li.textContent = `${list.list_title}`;
            list_container.appendChild(li);
        });
    });
}

