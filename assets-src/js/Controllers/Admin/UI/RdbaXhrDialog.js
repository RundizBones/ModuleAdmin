/**
 * Commonly use XHR content and display in dialog. (mostly useful for add, edit page).
 */


class RdbaXhrDialog {


    constructor(options) {
        // dialog element ID selector.
        this.dialogIDSelector = '#rdba-listingpage-dialog';
        // dialog new initialized event name (css, js is not already loaded).
        this.dialogNewInitEvent = 'rdba.listingpage.editing.newinit';
        // dialog re-initialized event name. (found that css, js is already loaded).
        this.dialogReInitEvent = 'rdba.listingpage.editing.reinit';
        // form result placeholder selector. (for displaying alert box).
        this.formResultPlaceholderSelector = '.form-result-placeholder';

        // selector (class) to js file that will be work in xhr page.
        this.xhrInjectJsSelector = '.ajaxInjectJs';
        // selector (class) to css file that will be work in xhr page.
        this.xhrInjectCssSelector = '.ajaxInjectCss';
        // xhr links that will be listen on them.
        this.xhrLinksSelector = '.rdba-listpage-addnew, .rdba-listpage-edit';
        // xhr selector to the content header of page that was requested.
        this.xhrPageContentHeaderSelector = '.rdba-page-content-header';
        // xhr selector to the content of page that was requested.
        this.xhrPageContentSelector = '.rdba-edit-form';

        if (typeof(options) === 'object') {
            Object.assign(this, options);// use object.assign not _.defaults because that will not work with class property and normal property.
        }
    }// constructor


    /**
     * XHR get link contents into dialog and open it.
     * 
     * This will make XHR to get contents, create push state, add content to dialog, open the dialog.
     * 
     * @private This method was called from `listenAjaxLinks()` method.
     * @param {string} targetLink The target link URL.
     * @param {Boolean} pushState
     * @returns {undefined}
     */
    ajaxOpenLinkInDialog(targetLink, pushState) {
        let $ = jQuery.noConflict();
        let thisClass = this;

        $.ajax({
            'url': targetLink,
            'method': 'GET',
            'dataType': 'html',
            'headers': {
                'rundizbones-no-profiler': true,
            }
        })
        .done(function(data, textStatus, jqXHR) {
            let response = data;
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            let response = jqXHR.responseText;
            let isResponseJson = false;

            try {
                response = JSON.parse(response);
                isResponseJson = true;
            } catch (e) {
            }

            if (isResponseJson === true && response.formResultMessage) {
                // if response is JSON and contain `formResultMessage`.
                // this can be permission denied error or anything important error, show alert dialog to user.
                RDTAAlertDialog.alert({
                    'type': 'error',
                    'html': RdbaCommon.renderAlertContent(response.formResultMessage)
                });
            }
        })
        .always(function(data, textStatus, jqXHR) {
            let response;
            let isResponseJson = false;

            if (typeof(data) === 'object' && typeof(data.responseJSON) !== 'undefined') {
                response = data.responseJSON;
                isResponseJson = true;
            } else if (typeof(data) === 'object' && typeof(data.responseText) !== 'undefined') {
                response = data.responseText;
                try {
                    response = JSON.parse(response);
                    isResponseJson = true;
                } catch (e) {
                    response = JSON.parse(JSON.stringify(response));
                    let pattern = /<[a-z][\s\S]*>/i;
                    if (pattern.test(response) === false) {
                        // if response is just string, not HTML.
                        // @link https://stackoverflow.com/a/15458987/128761 Original source code.
                        // set the response string as error message.
                        let formattedResponse = {};
                        formattedResponse.formResultMessage = response;
                        response = formattedResponse;
                        formattedResponse = undefined;
                    }
                }
            } else {
                response = data;
            }
            if (typeof(response) === 'undefined' || response === null) {
                response = {};
            }

            if (isResponseJson === false) {
                // if response is not JSON.
                let parser = new DOMParser();
                let parsedDoc = parser.parseFromString(response, 'text/html');

                // get parts of ajax contents.
                let pageContent = (parsedDoc.querySelector(thisClass.xhrPageContentSelector) ? parsedDoc.querySelector(thisClass.xhrPageContentSelector).outerHTML : response);
                let pageTitle = (parsedDoc.querySelector(thisClass.xhrPageContentHeaderSelector) ? parsedDoc.querySelector(thisClass.xhrPageContentHeaderSelector).innerHTML : '');
                let pageCss = parsedDoc.querySelectorAll(thisClass.xhrInjectCssSelector);
                let pageJs = parsedDoc.querySelectorAll(thisClass.xhrInjectJsSelector);

                // use storage to store ajax contents and it can get with push/pop state later.
                let storageObject = {
                    'pageUrl': targetLink,
                    'pageContent': pageContent,
                    'pageCss': (pageCss ? pageCss : null),
                    'pageJs': (pageJs ? pageJs : null),
                    'pageFullContent': response,
                    'pageTitle': pageTitle
                };
                storageObject.pageContent = pageContent;
                window.sessionStorage.setItem(targetLink, JSON.stringify(storageObject));
                if (pushState !== false) {
                    window.history.pushState({'pageUrl': targetLink}, '', targetLink);
                }

                // put ajax contents to dialog and activate it.
                thisClass.assignDialogAndActivate(pageContent, pageTitle);

                // inject css & js after everything is ready. this is for ajax get and set form value works.
                thisClass.injectCssAndJs(pageCss, pageJs);
            }// endif; isResponseJson

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                    document.querySelector(thisClass.formResultPlaceholderSelector).innerHTML = alertBox;
                }
            }
        });
    }// ajaxOpenLinkInDialog


    /**
     * Assign dialog title, content and then activate it.
     * 
     * @private This method was called from `ajaxOpenLinkInDialog()`, `listenPopStateControlsDialog()` methods.
     * @param {DOM} pageContent
     * @param {DOM} pageTitle
     * @returns {undefined}
     */
    assignDialogAndActivate(pageContent, pageTitle = '') {
        let thisClass = this;

        document.querySelector(thisClass.dialogIDSelector + ' .rd-dialog-body').innerHTML = pageContent;
        document.querySelector(thisClass.dialogIDSelector + ' .rd-dialog-title').innerHTML = pageTitle;

        let rdtaDialog = new RDTADialog();
        rdtaDialog.activateDialog(thisClass.dialogIDSelector);
    }// assignDialogAndActivate


    /**
     * Insert CSS, JS into main page.
     * 
     * @link https://stackoverflow.com/q/40808425/128761 Dynamically load JS file from JS.
     * @link https://stackoverflow.com/q/38362231/128761 Promise in `forEach`.
     * @link https://stackoverflow.com/q/22519784/128761 Onload with promise.
     * @private This method was called from `ajaxOpenLinkInDialog()`, `listenPopStateControlsDialog()` methods.
     * @param {object} pageCss The object that have got from `document.querySelector()`.
     * @param {object} pageJs The object that have got from `document.querySelector()`.
     * @returns {undefined}
     */
    injectCssAndJs(pageCss, pageJs) {
        let thisClass = this;
        let promises = [];
        let dispatchDialogReInit = false;
        let dispatchDialogNewInit = false;

        if (pageJs && typeof(pageJs) === 'object') {

            pageJs.forEach(function(item, index) {
                let jsItem = item;
                let injectJs = document.createElement('script');
                if (!_.isNull(jsItem.getAttribute('async')) && jsItem.getAttribute('async') != 'false') {
                    injectJs.async = jsItem.async;
                }
                injectJs.classList = jsItem.classList;
                injectJs.className = jsItem.className;
                injectJs.id = jsItem.id;
                injectJs.src = jsItem.src;
                injectJs.type = jsItem.type;
                console.log('[rdba] trying to load ' + injectJs.id + '.');

                if (!document.querySelector('#' + jsItem.id)) {
                    // if this element is really not found.
                    // add onload listener
                    let promiseObj = new Promise(function(resolve) {
                        injectJs.onload = function() {
                            console.log('[rdba] js ' + injectJs.id + ' was loaded.');
                            dispatchDialogNewInit = true;
                            return resolve(index);
                        };
                    });
                    promises.push(promiseObj);
                    // insert into page.
                    document.body.appendChild(injectJs);
                } else {
                    console.log('[rdba] js ' + jsItem.id + ' is already loaded.');
                    dispatchDialogReInit = true;
                    promises.push(index);
                }
            });// endforEach;
        }

        if (pageCss && typeof(pageCss) === 'object') {
            pageCss.forEach(function(item, index) {
                let cssItem = item;
                let injectCss = document.createElement('link');
                injectCss.classList = cssItem.classList;
                injectCss.className = cssItem.className;
                injectCss.id = cssItem.id;
                injectCss.rel = cssItem.rel;
                injectCss.type = cssItem.type;
                injectCss.href = cssItem.href;
                console.log('[rdba] trying to load ' + cssItem.id + '.');

                if (!document.querySelector('#' + cssItem.id)) {
                    // if this element is really not found.
                    // insert into page.
                    let promiseObj = new Promise(function(resolve) {
                        injectCss.onload = function() {
                            console.log('[rdba] css ' + cssItem.id + ' was loaded.');
                            dispatchDialogNewInit = true;
                            return resolve(index);
                        };
                    });
                    promises.push(promiseObj);
                    document.head.appendChild(injectCss);
                } else {
                    console.log('[rdba] css ' + cssItem.id + ' is already loaded.');
                    dispatchDialogReInit = true;
                    promises.push(index);
                }
            });// endforEach;
        }

        Promise.all(promises)
        .then(() => {
            console.log('[rdba] all css & js injection finished.', promises);
            if (dispatchDialogNewInit === true) {
                let event = new CustomEvent(
                    thisClass.dialogNewInitEvent, 
                    {
                        'detail': {
                            'rdbaUrl': document.URL,
                            'rdbaUrlNoDomain': window.location.href.replace(window.location.origin, ''),
                        }
                    }
                );
                document.dispatchEvent(event);
                console.log('[rdba] fired event ' + thisClass.dialogNewInitEvent + '.');
            } else if (dispatchDialogReInit === true) {
                if (typeof(thisClass.dialogInitEvent) !== 'undefined') {
                    // the legacy event property (RdbaXhrDialog.dialogInitEvent) will be remove in v 2.0.
                    // @todo [rdb] Remove this property (RdbaXhrDialog.dialogInitEvent) in v2.0.
                    this.dialogReInitEvent = this.dialogInitEvent;
                    console.warn('[rdba] The `dialogInitEvent` property will be remove in v2.0, please update your code by rename to `dialogReInitEvent`.');
                }
                let event = new CustomEvent(
                    thisClass.dialogReInitEvent, 
                    {
                        'detail': {
                            'rdbaUrl': document.URL,
                            'rdbaUrlNoDomain': window.location.href.replace(window.location.origin, ''),
                        }
                    }
                );
                document.dispatchEvent(event);
                console.log('[rdba] fired event ' + thisClass.dialogReInitEvent + '.');
            }
        });
    }// injectCssAndJs


    /**
     * Listen links click that will be open as ajax inside dialog.
     * 
     * @returns {undefined}
     */
    listenAjaxLinks() {
        let thisClass = this;

        // on click... (event delegation)
        document.addEventListener('click', function(event) {
            for (var target=event.target; target && target!=this; target=target.parentNode) {
                if (target.matches(thisClass.xhrLinksSelector)) {
                    // if clicked object is add new, edit page.
                    // prevent link and use ajax.
                    event.preventDefault();

                    let targetLink = target.href.trim();// .href is full url, .getAttribute('href') is as seen in href attribute.

                    if (targetLink !== '') {
                        thisClass.ajaxOpenLinkInDialog(targetLink);
                    }

                    break;
                }
            }
        });
    }// listenAjaxLinks


    /**
     * Listen dialog on close and maybe change URL.
     * 
     * This event happens when..<br>
     * 1. User click on add, edit, bulk actions submit > click on close dialog.<br>
     * 2. User click on add, edit, bulk actions submit > click on save or confirm button and success.<br>
     * These actions, the dialog will be close but URL did not changed, this listener will be change it.
     * 
     * If you want this method to work on specific URL, please copy and re-write it in your class.
     * 
     * @returns {undefined}
     */
    listenDialogClose() {
        let thisClass = this;

        // on closed dialog.
        let dialogElement = document.querySelector(this.dialogIDSelector);
        if (!dialogElement) {
            return ;
        }

        dialogElement.addEventListener('rdta.dialog.closed', function handler(event) {
            let pageUrl = (RdbaCommon.isset(() => window.location.href) === true ? window.location.href.trim() : '');

            if (
                    pageUrl && 
                    (
                        pageUrl.toLowerCase().indexOf('/add') !== -1 ||
                        pageUrl.toLowerCase().indexOf('/edit') !== -1 ||
                        pageUrl.toLowerCase().indexOf('/actions') !== -1
                    )
                ) {
                    // if contain state and page URL is add or edit or actions. 
                    // this condition is for prevent double go back.
                    //console.log('[rdba] on url: ' + window.location.href);
                    //console.log('[rdba] url is in add, edit, actions page.', pageUrl);
                    //console.log('[rdba] redirect back.');
                    window.history.go(-1);
            }
        }, false);
    }// listenDialogClose


    /**
     * Listen on popstate and controls the dialog (open or close).
     * 
     * If you want this method to work on specific URL, please copy and re-write it in your class.
     * 
     * @returns {undefined}
     */
    listenPopStateControlsDialog() {
        let thisClass = this;

        window.onpopstate = function(event) {
            if (!event.state) {
                // no history, no state just listing page.
                // trigger click on close dialog button.
                //console.log('[rdba] no history', event.state);
                //console.log('[rdba] on url: ' + window.location.href);
                //console.log('[rdba] trigger click on close dialog.');
                document.querySelector(thisClass.dialogIDSelector + ' [data-dismiss="dialog"]').click();
            } else {
                let pageUrl = (event.state && typeof(event.state.pageUrl) !== 'undefined' ? event.state.pageUrl.trim() : '');
                if (
                    pageUrl && 
                    (
                        pageUrl.toLowerCase().indexOf('/add') !== -1 ||
                        pageUrl.toLowerCase().indexOf('/edit') !== -1 ||
                        pageUrl.toLowerCase().indexOf('/actions') !== -1
                    )
                ) {
                    // if contain state and page URL is add or edit or actions. 
                    // this happens when user is on listing page, click add OR edit, go back to listing page, click forward to opened page again.
                    let dialogBody = document.querySelector(thisClass.dialogIDSelector + ' .rd-dialog-body');
                    if (RdbaCommon.isset(() => dialogBody.innerHTML) && dialogBody.innerHTML.trim() === '') {
                        // if loaded content was gone or nothing in it, load again.
                        thisClass.ajaxOpenLinkInDialog(event.state.pageUrl, false);
                    } else {
                        // if the opened page was already loaded in dialog body, then it is no need to load again.
                        // get cached content (JS) on sessionStorage.
                        let storageObject = JSON.parse(window.sessionStorage.getItem(pageUrl));
                        if (storageObject.pageFullContent) {
                            let parser = new DOMParser();
                            let parsedDoc = parser.parseFromString(storageObject.pageFullContent, 'text/html');
                            let pageCss = parsedDoc.querySelectorAll(thisClass.xhrInjectCssSelector);
                            let pageJs = parsedDoc.querySelectorAll(thisClass.xhrInjectJsSelector);

                            // get parts of ajax contents.
                            let pageContent = (parsedDoc.querySelector('.rdba-edit-form') ? parsedDoc.querySelector('.rdba-edit-form').outerHTML : '');
                            let pageTitle = (parsedDoc.querySelector('.rdba-page-content-header') ? parsedDoc.querySelector('.rdba-page-content-header').innerHTML : '');

                            // assign dialog and activate and then inject js css.
                            //console.log('[rdba] assign dialog from storage.', {pageTitle, pageContent});
                            thisClass.assignDialogAndActivate(pageContent, pageTitle);
                            thisClass.injectCssAndJs(pageCss, pageJs);
                        }
                        // open the dialog.
                        let rdtaDialog = new RDTADialog();
                        rdtaDialog.activateDialog(thisClass.dialogIDSelector);
                    }
                }
            }
        };
    }// listenPopStateControlsDialog


}// RdbaXhrDialog