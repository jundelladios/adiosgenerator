document.addEventListener("DOMContentLoaded", function () {
    // Select all elements with the lazy-background class
    const lazyBackgrounds = document.querySelectorAll('.et_pb_section');

    // Intersection Observer to detect when the element is in the viewport
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bgElement = entry.target;
                // Add a class to indicate that the image has been loaded
                bgElement.classList.add('loaded');
                // Stop observing this element
                observer.unobserve(bgElement);
            }
        });
    }, {
        threshold: 0.1  // Trigger when 10% of the element is in the viewport
    });

    // Observe each lazy-background element
    lazyBackgrounds.forEach(element => {
        observer.observe(element);
    });
});