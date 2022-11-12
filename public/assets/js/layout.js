/**
 * Layout script containing common client-side helper functions
 *
 * Note that this script loads after scripts in the views (if any). To be safe,
 * scripts in views should run after getmail.layout.ready event is emitted.
 *
 * @global
 * @param {HTMLElement} currentScript - Script element that this script is loaded in, e.g. <script src="x.js"></script>.
 */
const layout = (function (currentScript) { // not using `var` so that there will be error if it's loaded more than once
    /** @type {object} Self reference - all public properties/methods are stored here & returned as public interface. */
    const self = {}; // methods attached to this are ordered alphabetically

    // Initialization
    (function init() {
        window.addEventListener('getmail.layout.ready', (event) => {
            console.log('Layout script ready.');
        });

        // Emit layout ready event - view scripts should listen for "getmail.layout.ready" event
        // before running, especially if calling methods in this script
        window.addEventListener('DOMContentLoaded', () => {
            // This event may be dispatched multiple times hence each view script should check
            // if it has already handled this event.
            window.dispatchEvent(new CustomEvent('getmail.layout.ready')); // find dispatchEvent() to see other triggers
        });
    })();

    // Return public interface of IIFE
    return self;
})(document.currentScript); // pass in argument to ensure different instance each time
