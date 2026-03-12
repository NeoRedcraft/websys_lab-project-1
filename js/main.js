// Global script placeholder for shared page interactions.
window.addEventListener('DOMContentLoaded', function () {
    var mobileNavToggle = document.querySelector('[data-mobile-nav-toggle]');
    var mobileNavMenu = document.querySelector('[data-mobile-nav-menu]');

    if (mobileNavToggle && mobileNavMenu) {
        mobileNavToggle.addEventListener('click', function () {
            var isExpanded = mobileNavToggle.getAttribute('aria-expanded') === 'true';
            mobileNavToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            mobileNavMenu.classList.toggle('hidden');
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                mobileNavMenu.classList.add('hidden');
                mobileNavToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
