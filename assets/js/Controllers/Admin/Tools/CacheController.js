/**
 * Cache management JS for its controller.
 */


class RdbaCacheController {


    /**
     * AJAX get form data.
     * 
     * @returns {undefined}
     */
    ajaxGetFormData() {
        let promiseObj = new Promise(function(resolve, reject) {
            RdbaCommon.XHR({
                'url': RdbaToolsCache.getCacheUrl,
                'method': RdbaToolsCache.getCacheMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'dataType': 'json'
            })
            .catch(function(responseObject) {
                console.error(responseObject);
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
            .then(function(responseObject) {
                let response = (responseObject ? responseObject.response : {});

                if (RdbaCommon.isset(() => response.cache) && _.isObject(response.cache)) {
                    let thisForm = document.getElementById('rdba-toolscache-form');
                    thisForm.querySelector('.cache-driver .control-wrapper').innerText = response.cache.driver;

                    if (typeof(response.cache?.basePath) !== 'undefined') {
                        thisForm.querySelector('.cache-basePath .control-wrapper').innerText = response.cache.basePath;
                        thisForm.querySelector('.cache-basePath').classList.remove('rd-hidden');
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
            });// end XHR
        });// end new Promise();

        return promiseObj;
    }// ajaxGetFormData


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
            return thisClass.ajaxGetFormData();
        })
        .then(function() {
            thisClass.listenOnFormSubmit();
        })
        ;
    }// init


    /**
     * Listen on form submit.
     * 
     * @returns {undefined}
     */
    listenOnFormSubmit() {
        let cacheForm = document.getElementById('rdba-toolscache-form');
        if (cacheForm) {
            cacheForm.addEventListener('submit', function(event) {
                event.preventDefault();

                let selectCommand = cacheForm.querySelector('#rdba-tools-cachecommand');
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
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;
                    console.error(responseObject);

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

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    cacheForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    cacheForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                });
            });
        }
    }// listenOnFormSubmit


}


document.addEventListener('DOMContentLoaded', function() {
    let rdbaCacheController = new RdbaCacheController();

    // initialize the class.
    rdbaCacheController.init();
}, false);