/**
 * Browser history state for working with add, edit pages that were called via XHR from listing page.
 */


class RdbaHistoryState {


    /**
     * Listen on pop state and reload if contain no required state object (`pageUrl`).
     * 
     * @returns {undefined}
     */
    listenPopState() {
        // on backward, forward click.
        window.onpopstate = function(event) {
            // declare required state object.
            let pageUrl = (event.state && typeof(event.state.pageUrl) !== 'undefined' ? event.state.pageUrl.trim() : '');

            if (!pageUrl) {
                // if contain no required state object.
                // this happens when ... user is on listing page 
                //      > click add new 
                //      > AJAX works 
                //      > URL changed to add 
                //      > AJAX content displayed 
                //      > user hit refresh or reload 
                //      > user click back to listing page
                //          if there is no this method and condition, the URL will be changed back but page content is not change anymore.
                //          with this condition, it will reload the page and display listing page correctly.
                // reload it.
                window.location.reload();
            }
        };
    }// listenPopState


}// RdbaHistoryState


document.addEventListener('DOMContentLoaded', function() {
    let rdbaHistoryStateObject = new RdbaHistoryState();

    // listen on popstate and reload if url changed but no push, pop state.
    rdbaHistoryStateObject.listenPopState();
}, false);