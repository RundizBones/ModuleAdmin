/**
 * Cache management JS for its controller.
 */


class RdbaCacheController {


    /**
     * AJAX get form data.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #ajaxGetFormData() {
        let promiseObj = new Promise(function(resolve, reject) {
            RdbaCommon.XHR({
                'url': RdbaToolsCache.getCacheUrl,
                'method': RdbaToolsCache.getCacheMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'dataType': 'json'
            })
            .then(function(responseObject) {
                let response = (responseObject ? responseObject.response : {});

                if (RdbaCommon.isset(() => response.cache) && _.isObject(response.cache)) {
                    let thisForm = document.getElementById('rdba-toolscache-form');
                    thisForm.querySelector('.cache-driver .control-wrapper').innerText = response.cache.driver;

                    if (response.cache?.driver === 'filesystem') {
                        if (typeof(response.cache?.basePath) !== 'undefined') {
                            thisForm.querySelector('.cache-basePath .control-wrapper').innerText = response.cache.basePath;
                            thisForm.querySelector('.cache-basePath').classList.remove('rd-hidden');
                        }
                    }

                    if (typeof(response.cache?.totalFilesFolders) !== 'undefined') {
                        thisForm.querySelector('.cache-totalFilesFolders .control-wrapper').innerText = response.cache.totalFilesFolders;
                        thisForm.querySelector('.cache-totalFilesFolders').classList.remove('rd-hidden');
                    }

                    if (typeof(response.cache?.totalSize) !== 'undefined') {
                        thisForm.querySelector('.cache-totalSize .control-wrapper').innerText = RdbaCommon.humanFileSize(response.cache.totalSize, true);
                        thisForm.querySelector('.cache-totalSize').classList.remove('rd-hidden');
                    }

                    if (typeof(response.cache?.totalItems) !== 'undefined') {
                        thisForm.querySelector('.cache-totalItems .control-wrapper').innerText = response.cache.totalItems;
                        thisForm.querySelector('.cache-totalItems').classList.remove('rd-hidden');
                    }
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaToolsCache.csrfKeyPair = response.csrfKeyPair;
                }

                resolve(response);
            })
            .catch(function(responseObject) {
                console.error('[rdba] ', responseObject);
                let response = (responseObject ? responseObject.response : {});

                if (typeof(response) !== 'undefined') {
                    if (typeof(response.formResultMessage) !== 'undefined') {
                        let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                        let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                        document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                    }
                }

                reject(response);
            })
            ;// end XHR
        });// end new Promise();

        return promiseObj;
    }// #ajaxGetFormData


    /**
     * Listen cache command change and toggle show/hide other form group.
     * 
     * This method was called from `init()`.
     * 
     * @since 1.2.10
     * @returns {undefined}
     */
    #listenCacheCommandChange() {
        const cacheCommand = document.getElementById('rdba-tools-cachecommand');
        const localCacheFormGroup = document.getElementById('rdba-cache-local-form-group');

        cacheCommand?.addEventListener('change', (event) => {
            const thisTarget = event.target;
            if (thisTarget.value === 'clear') {
                localCacheFormGroup.classList.remove('rd-hidden');
            } else {
                localCacheFormGroup.classList.add('rd-hidden');
            }
        });
    }// #listenCacheCommandChange


    /**
     * Listen on form submit.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenOnFormSubmit() {
        const thisClass = this;
        const cacheForm = document.getElementById('rdba-toolscache-form');

        if (cacheForm) {
            cacheForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const selectCommand = cacheForm.querySelector('#rdba-tools-cachecommand');
                let restUrl, restMethod;

                if (!selectCommand || (selectCommand && selectCommand.value === '')) {
                    RDTAAlertDialog.alert({'text': RdbaToolsCache.txtPleaseSelectCommand});
                    return false;
                } else {
                    if (selectCommand.value === 'clear') {
                        restUrl = RdbaToolsCache.clearCacheUrl;
                        restMethod = RdbaToolsCache.clearCacheMethod;
                    }
                }

                // reset form result placeholder
                cacheForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                cacheForm.querySelector('.submit-button-row .submit-button-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                cacheForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(cacheForm);
                formData.append(RdbaToolsCache.csrfName, RdbaToolsCache.csrfKeyPair[RdbaToolsCache.csrfName]);
                formData.append(RdbaToolsCache.csrfValue, RdbaToolsCache.csrfKeyPair[RdbaToolsCache.csrfValue]);

                RdbaCommon.XHR({
                    'url': restUrl,
                    'method': restMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'data': new URLSearchParams(_.toArray(formData)).toString(),
                    'dataType': 'json'
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                            let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                            cacheForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaToolsCache.csrfKeyPair = response.csrfKeyPair;
                    }

                    thisClass.#maybeClearLocalCache(cacheForm);

                    return Promise.resolve(responseObject);
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;
                    console.error('[rdba] ', responseObject);

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RDTAAlertDialog.alert({
                                'type': response.formResultStatus,
                                'html': RdbaCommon.renderAlertContent(response.formResultMessage)
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaToolsCache.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    cacheForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    cacheForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                });
            });
        }// endif; there is cache form.
    }// #listenOnFormSubmit


    #maybeClearLocalCache(cacheForm) {
        const selectCommand = cacheForm.querySelector('#rdba-tools-cachecommand');

        if (selectCommand.value === 'clear') {
            const clearLS = document.querySelector('input[name="clear-local-session-storage"]');
            if (clearLS.checked) {
                // If delete local & session storage was checked.
                // Omit local storage word because we don't use it in both framework, RdbAdmin. So, nothing to clear.
                // However, local storage and session storage should not be using **clear** but remove individually that was set by the framework or this module (if available).
                Object.keys(sessionStorage).forEach((item) => {
                    if (typeof(item) === 'string') {
                        if (item.indexOf('rdba_') === 0 || item.indexOf('rdb_') === 0) {
                            // if found item named begins with 'rdba_', 'rdb_'. delete it.
                            sessionStorage.removeItem(item);
                        }
                    }// endif;
                });
            }
        }
    }// #maybeClearLocalCache


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        let $ = jQuery.noConflict();
        let thisClass = this;

        // wait for UI XHR common data finished then start working.
        $.when(uiXhrCommonData)
        .then(function() {
            return thisClass.#ajaxGetFormData();
        })
        .then(function() {
            thisClass.#listenCacheCommandChange();
            thisClass.#listenOnFormSubmit();
        })
        ;
    }// init


}


document.addEventListener('DOMContentLoaded', function() {
    let rdbaCacheController = new RdbaCacheController();

    // initialize the class.
    rdbaCacheController.init();
}, false);