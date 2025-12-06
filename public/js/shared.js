document.addEventListener('DOMContentLoaded', () => {
    insertNavBar();
});

function insertNavBar(){
    const nav = document.querySelector('.app-header');
    const isSignedIn = sessionStorage.getItem('username') ?? false;

    if(isSignedIn){
        nav.innerHTML = `
        <a class="app-title" href="home.html">Ravel<span>.</span></a>
        <nav class="nav">
            <a href="home.html">Home</a>
            <a href="reviews.html">Reviews</a>
            <a href="locations.html">Locations</a>
            <a href="trip.html">Trips</a>
            <a href="list.html">Lists</a>
            <a href="userprofile.html">Profile</a>
        </nav>
        `;
    } else {
        nav.innerHTML = `
        <a class="app-title" href="home.html">Ravel<span>.</span></a>
        <nav class="nav">
            <a href="home.html">Home</a>
            <a href="reviews.html">Reviews</a>
            <a href="locations.html">Locations</a>
            <a href="login.html">Sign In</a>
        </nav>
        `;
    }
}
