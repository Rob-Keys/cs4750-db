document.addEventListener('DOMContentLoaded', function() {
    console.log('Home page loaded');

    const heroImage = document.getElementById('hero-image');
    const heroCaption = document.getElementById('hero-caption');

    if (!heroImage || !heroCaption) {
        return;
    }

    const slides = [
        {
            src: 'images/vacation1.jpg',
            caption: 'Sunset beach getaway'
        },
        {
            src: 'images/vacation2.jpg',
            caption: 'Mountain escape with friends'
        },
        {
            src: 'images/vacation3.jpg',
            caption: 'City lights and late-night walks'
        },
        {
            src: 'images/vacation4.jpg',
            caption: 'Road trip along the coast'
        }
    ];

    let index = 0;

    function showSlide(i) {
        const slide = slides[i];
        heroImage.src = slide.src;
        heroImage.alt = slide.caption;
        heroCaption.textContent = slide.caption;
    }

    showSlide(index);

    setInterval(() => {
        index = (index + 1) % slides.length;
        showSlide(index);
    }, 4000); // 4 seconds per image
});
