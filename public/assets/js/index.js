/**
 * Script for src/Web/views/index.html
 *
 * @param {HTMLElement} currentScript - Script element that this script is loaded in, e.g. <script src="x.js"></script>.
 * @returns {void}
 */
(function (currentScript) {
    let renderElement = null;

    window.addEventListener('getmail.layout.ready', (event) => {
        renderElement = document.querySelector(currentScript?.getAttribute('data-render-selector'));

        // Prevent resubmission of form when user refreshes browser
        // See https://en.wikipedia.org/wiki/Post/Redirect/Get
        // Solution from https://stackoverflow.com/a/45656609
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    });
})(document.currentScript);
