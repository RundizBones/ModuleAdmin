/**
 * Settings page JS for its controller.
 */


class RdbaSettingsController {


    constructor() {
        this.settingsFormIdSelector = '#rdba-settings-form';
    }// constructor


    /**
     * AJAX get form data.
     * 
     * @returns {Promise}
     */
    ajaxGetFormData() {
        let thisClass = this;
        let $ = jQuery.noConflict();

        let promiseObj = new Promise((resolve, reject) => {
            RdbaCommon.XHR({
                'url': RdbaSettings.urls.getSettingsUrl,
                'method': RdbaSettings.urls.getSettingsMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'dataType': 'json',
            })
            .then((responseObject) => {
                let response = (responseObject ? responseObject.response : {});

                if (RdbaCommon.isset(() => response.configData) && _.isArray(response.configData)) {
                    response.configData.forEach((item, index) => {
                        if (
                            RdbaCommon.isset(() => item.config_name) && 
                            RdbaCommon.isset(() => item.config_value)
                        ) {
                            let thisInputElement = document.querySelector(thisClass.settingsFormIdSelector + ' #' + item.config_name);
                            if (thisInputElement) {
                                if (thisInputElement.type.toLowerCase() === 'checkbox') {
                                    if (thisInputElement.value == item.config_value) {
                                        thisInputElement.checked = true;
                                        //console.log('[rdba] mark ' + key + ' as checked.');
                                    }
                                } else if (thisInputElement.type.toLowerCase() === 'file') {
                                    // if it is input type file.
                                    // don't work here. this is for prevent errors only.
                                } else {
                                    thisInputElement.value = item.config_value;
                                }
                            }

                            if (item.config_name === 'rdbadmin_UserRegisterDefaultRoles') {
                                // if default roles setting.
                                let defaultRoles = item.config_value.split(',');
                                // set multiple values.
                                defaultRoles.forEach((item) => {
                                    let roleValue = item.trim();
                                    if (roleValue) {
                                        let defaultRolesSelectbox = document.querySelector(thisClass.settingsFormIdSelector + ' #rdbadmin_UserRegisterDefaultRoles option[value="' + roleValue + '"]');
                                        defaultRolesSelectbox.selected = true;
                                    }
                                });
                            }

                            if (item.config_name === 'rdbadmin_SiteFavicon' && item.config_value) {
                                // if favicon.
                                let rdbaSettingsFaviconController = new RdbaSettingsFaviconController();
                                rdbaSettingsFaviconController.displayFavicon(RdbaSettings.urls.publicUrl + '/' + item.config_value);
                            }
                        }// endif isset item.xxx

                        if (RdbaCommon.isset(() => item.config_description)) {
                            let thisInputElement = document.querySelector(thisClass.settingsFormIdSelector + ' #' + item.config_name);
                            let parentFormGroupElement = $(thisInputElement).parents('.form-group')[0];
                            if (parentFormGroupElement) {
                                parentFormGroupElement.dataset.configdescription = RdbaCommon.escapeHtml(RdbaCommon.stripTags(item.config_description));
                            }
                        }// endif isset item.config_description
                    });
                }// endif response.configData

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                }

                resolve(response);
            })
            .catch((responseObject) => {
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
            });// end XHR
        });// end new Promise();

        return promiseObj;
    }// ajaxGetFormData


    /**
     * Initialize class.
     * 
     * @returns {undefined}
     */
    init() {
        let $ = jQuery.noConflict();
        let thisClass = this;

        // wait for UI XHR common data finished then start working.
        $.when(uiXhrCommonData)
        .then(() => {
            // reset the form before call to ajax. this is to prevent Firefox form cache.
            document.querySelector(thisClass.settingsFormIdSelector).reset();
            return thisClass.ajaxGetFormData();
        })
        .then(() => {
            return thisClass.listenOnSearchSettings();
        })
        ;

        // listen on form submit and make ajax request.
        this.listenOnFormSubmit();
        // prevent search the settings form submit.
        this.listenOnSearchSubmit();

        // listen on test smtp connection.
        this.listenOnTestSmtpConnection();
        // listen on click regenerate API key.
        this.listenOnRegenerateAPIKey();
    }// init


    /**
     * Listen on form submit (save) and make ajax request.
     * 
     * @returns {undefined}
     */
    listenOnFormSubmit() {
        let settingsForm = document.querySelector(this.settingsFormIdSelector);
        settingsForm.addEventListener('submit', (event) => {
            event.preventDefault();

            // reset form result placeholder
            settingsForm.querySelector('.form-result-placeholder').innerHTML = '';
            // add spinner icon
            settingsForm.querySelector('.submit-button-row .submit-button-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            settingsForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

            let formData = new FormData(settingsForm);
            formData.append(RdbaSettings.csrfName, RdbaSettings.csrfKeyPair[RdbaSettings.csrfName]);
            formData.append(RdbaSettings.csrfValue, RdbaSettings.csrfKeyPair[RdbaSettings.csrfValue]);
            formData.delete('rdbadmin_SiteFavicon');

            // make sure that unchecked check box is send zero value instead of nothing.
            settingsForm.querySelectorAll('input[type="checkbox"]').forEach((item, index) => {
                if (item.checked === false && item.name && item.value == '1') {
                    // if checkbox is not checked and contains name and value is '1'. this means its value should be '0'.
                    // append name and value '0' to form object.
                    formData.append(item.name, 0);
                }
            });

            RdbaCommon.XHR({
                'url': RdbaSettings.urls.editSettingsSubmitUrl,
                'method': RdbaSettings.urls.editSettingsSubmitMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'data': new URLSearchParams(_.toArray(formData)).toString(),
                'dataType': 'json'
            })
            .then((responseObject) => {
                // XHR success.
                let response = responseObject.response;

                if (typeof(response) !== 'undefined') {
                    if (typeof(response.formResultMessage) !== 'undefined') {
                        RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                    }
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                }

                // dispatch custom event to let plugins reload their settings.
                document.dispatchEvent(
                    new CustomEvent(
                        'rdbadmin.RdbaSettingsController.updated',
                        {
                            bubbles: true,
                        }
                    )
                );

                return Promise.resolve(responseObject);
            })
            .catch((responseObject) => {
                // XHR failed.
                let response = responseObject.response;
                console.error('[rdba] ', responseObject);

                if (response.formResultMessage) {
                    RDTAAlertDialog.alert({
                        'html': response.formResultMessage,
                        'type': 'danger'
                    });
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .catch((responseObject) => {})
            .finally(() => {
                // remove loading icon
                settingsForm.querySelector('.loading-icon').remove();
                // unlock submit button
                settingsForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                return Promise.resolve();
            });
        });
    }// listenOnFormSubmit


    /**
     * Listen on regenerate API key button.
     * 
     * @since 1.2.1
     * @returns {undefined}
     */
    listenOnRegenerateAPIKey() {
        let regenerateBtn = document.querySelector('#rdbadmin_SiteRegenerateAPIKey');
        let thisClass = this;
        let targetInput = document.querySelector('#rdbadmin_SiteAPIKey');

        document.addEventListener('click', (event) => {
            let thisButton;
            if (RdbaCommon.isset(() => event.currentTarget.activeElement)) {
                thisButton = event.currentTarget.activeElement;
            } else {
                thisButton = event.target;
            }

            if (thisButton === regenerateBtn) {
                event.preventDefault();

                let result = '';
                let characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                let generateLength = 40;
                let charactersLength = characters.length;
                for (var i = 0; i < generateLength; i++) {
                    result += characters.charAt(
                        Math.floor(
                            Math.random() * charactersLength
                        )
                    );
                }
                targetInput.value = result;
            }
        });
    }// listenOnRegenerateAPIKey


    /**
     * Listen on search settings typing and search the settings form.
     * 
     * @returns {Promise}
     */
    listenOnSearchSettings() {
        let thisClass = this;
        let promiseObj = new Promise((resolve, reject) => {
            document.addEventListener('keyup', (event) => {
                if (RdbaCommon.isset(() => event.currentTarget.activeElement.id) && event.currentTarget.activeElement.id === 'rdba-search-settings-input') {
                    event.preventDefault();
                    let inputSearchElement = (RdbaCommon.isset(() => event.currentTarget.activeElement) ? event.currentTarget.activeElement : {});
                    let timer = 0;
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        search(inputSearchElement);
                    }, 1000);
                }
            });

            resolve('listening on search input key up.');
        });// end new Promise();

        //console.log('[rdba] listening on search input key up.');
        return promiseObj;

        /**
         * Do search the HTML content and hide unmatched.
         * 
         * @param {type} inputSearchElement
         * @returns {undefined}
         */
        function search(inputSearchElement) {
            let formGroupElements = document.querySelectorAll(thisClass.settingsFormIdSelector + ' .form-group');

            if (!inputSearchElement.value || inputSearchElement.value === null || inputSearchElement.value.trim() === '') {
                //console.log('[rdba] reset `.form-group` from hidden.');
                formGroupElements.forEach((item, index) => {
                    item.classList.remove('rd-hidden');
                });
                return false;
            }

            formGroupElements.forEach((item, index) => {
                let configDescription = ''
                if (RdbaCommon.isset(() => item.dataset.configdescription)) {
                    configDescription = item.dataset.configdescription;
                }
                let formGroupText = '';
                if (item.innerText) {
                    formGroupText = item.innerText;
                }
                let inputValue = '';
                if (RdbaCommon.isset(() => item.querySelectorAll('input')[0].value)) {
                    inputValue = item.querySelectorAll('input')[0].value;
                }

                let inputSearchRegexp = new RegExp(inputSearchElement.value, "i");
                if (
                    configDescription.search(inputSearchRegexp) !== -1 || 
                    formGroupText.search(inputSearchRegexp) !== -1 ||
                    inputValue.search(inputSearchRegexp) !== -1
                ) {
                    item.classList.remove('rd-hidden');
                } else {
                    item.classList.add('rd-hidden');
                }
            });
        }// search
    }// listenOnSearchSettings


    /**
     * Listen on search submit and prevent default.
     * 
     * @returns {undefined}
     */
    listenOnSearchSubmit() {
        let searchForm = document.querySelector('#rdba-search-settings-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (event) => {
                event.preventDefault();
                //console.log('[rdba] prevented search form submit.');
            });
        }
    }// listenOnSearchSubmit


    /**
     * Listen on test smtp connection.
     * 
     * @returns {undefined}
     */
    listenOnTestSmtpConnection() {
        let settingsForm = document.querySelector(this.settingsFormIdSelector);
        let testSmtpButton = document.querySelector('#rdbadmin_MailSmtpTestConnectionButton');
        let testSmtpResultPlaceholder = document.querySelector('#rdbadmin_MailSmtpTestConnectionResultPlaceholder');

        if (testSmtpButton && testSmtpResultPlaceholder) {
            testSmtpButton.addEventListener('click', (event) => {
                event.preventDefault();

                // reset result placeholder
                testSmtpResultPlaceholder.innerHTML = '';
                // add spinner icon
                testSmtpResultPlaceholder.insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                testSmtpButton.setAttribute('disabled', 'disabled');

                let formData = new FormData(settingsForm);
                formData.append(RdbaSettings.csrfName, RdbaSettings.csrfKeyPair[RdbaSettings.csrfName]);
                formData.append(RdbaSettings.csrfValue, RdbaSettings.csrfKeyPair[RdbaSettings.csrfValue]);

                RdbaCommon.XHR({
                    'url': RdbaSettings.urls.editSettingsTestSmtpConnectionUrl,
                    'method': RdbaSettings.urls.editSettingsTestSmtpConnectionMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'data': new URLSearchParams(_.toArray(formData)).toString(),
                    'dataType': 'json'
                })
                .then((responseObject) => {
                    // XHR success.
                    let response = responseObject.response;

                    if (response && response.debugMessage) {
                        testSmtpResultPlaceholder.insertAdjacentHTML('beforeend', response.debugMessage);
                    }

                    if (typeof(response) !== 'undefined') {
                        if (response.formResultMessage) {
                            RDTAAlertDialog.alert({
                                'html': response.formResultMessage,
                                'type': response.formResultStatus
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .catch((responseObject) => {
                    // XHR failed.
                    let response = responseObject.response;
                    console.error('[rdba] ', responseObject);

                    if (response && response.debugMessage) {
                        testSmtpResultPlaceholder.insertAdjacentHTML('beforeend', response.debugMessage);
                    }

                    if (response.formResultMessage) {
                        RDTAAlertDialog.alert({
                            'html': response.formResultMessage,
                            'type': 'danger'
                        });
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .catch((responseObject) => {})
                .finally(() => {
                    // remove loading icon
                    testSmtpResultPlaceholder.querySelector('.loading-icon').remove();
                    // unlock submit button
                    testSmtpButton.removeAttribute('disabled');
                });
            });
        }
    }// listenOnTestSmtpConnection


}// RdbaSettingsController


document.addEventListener('DOMContentLoaded', () => {
    let rdbaSettingsController = new RdbaSettingsController();

    // initialize class.
    rdbaSettingsController.init();

    // activate rdta tabs.
    RDTATabs.init('.tabs', {
        'rememberLastTab': true,
    });
});