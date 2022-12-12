/**
 * Script for src/Web/views/index.html
 *
 * @param {HTMLElement} currentScript - Script element that this script is loaded in, e.g. <script src="x.js"></script>.
 * @returns {void}
 */
(function (currentScript) {
    /** @type {HTMLElement} Main element that script references via data-render-selector attribute in <script>. */
    let renderElement = null;

    window.addEventListener('getmail.layout.ready', (event) => {
        renderElement = document.querySelector(currentScript?.getAttribute('data-render-selector'));

        // Prevent resubmission of form when user refreshes browser
        // See https://stackoverflow.com/a/45656609 and https://en.wikipedia.org/wiki/Post/Redirect/Get
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    });
})(document.currentScript); // pass in argument to ensure different instance each time
