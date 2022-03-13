/**
 * Login one time JS for its controller.
 */


class RdbaLoginResetController {


    /**
     * Ajax login.
     * 
     * @returns {undefined}
     */
    ajaxLogin() {
        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-login-form') {
                event.preventDefault();

                let thisForm = event.target;

                // reset form result placeholder
                document.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);
                formData.append(RdbaLoginReset.csrfName, RdbaLoginReset.csrfKeyPair[RdbaLoginReset.csrfName]);
                formData.append(RdbaLoginReset.csrfValue, RdbaLoginReset.csrfKeyPair[RdbaLoginReset.csrfValue]);

                RdbaCommon.XHR({
                    'url': RdbaLoginReset.loginUrl,
                    'method': RdbaLoginReset.loginMethod,
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
                            let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                            let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                            document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaLoginReset.csrfKeyPair = response.csrfKeyPair;
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
                            document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaLoginReset.csrfKeyPair = response.csrfKeyPair;
                    }

                    if (typeof(response.loggedIn) !== 'undefined' && response.loggedIn === true) {
                        // if login really success, redirect.
                        window.location.href = RdbaLoginReset.gobackUrl;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                   // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    thisForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                })
                ;
            }
        });
    }// ajaxLogin


    /**
     * Ajax form submit.
     * 
     * @returns {undefined}
     */
    ajaxSubmit() {
        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-loginreset-form') {
                event.preventDefault();

                let thisForm = event.target;

                // reset form result placeholder
                document.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');
                // copy password to hidden login form.
                let passwordField = document.getElementById('user_password');
                let newPasswordField = document.getElementById('new_password');
                if (passwordField && newPasswordField) {
                    passwordField.value = newPasswordField.value;
                }

                let formData = new FormData(thisForm);
                formData.append(RdbaLoginReset.csrfName, RdbaLoginReset.csrfKeyPair[RdbaLoginReset.csrfName]);
                formData.append(RdbaLoginReset.csrfValue, RdbaLoginReset.csrfKeyPair[RdbaLoginReset.csrfValue]);

                RdbaCommon.XHR({
                    'url': RdbaLoginReset.loginResetUrl,
                    'method': RdbaLoginReset.loginResetMethod,
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
                            let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                            let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                            document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaLoginReset.csrfKeyPair = response.csrfKeyPair;
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
                            document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaLoginReset.csrfKeyPair = response.csrfKeyPair;
                    }

                    if (response && response.changedPassword === true) {
                        thisForm.classList.add('rd-hidden');
                        let loginFormElement = document.getElementById('rdba-login-form');
                        if (loginFormElement) {
                            loginFormElement.classList.remove('rd-hidden');
                        }
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                   // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    thisForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                })
                ;
            }
        });
    }// ajaxSubmit


}// RdbaLoginResetController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaLoginResetController = new RdbaLoginResetController();

    // ajax submit
    rdbaLoginResetController.ajaxSubmit();

    // ajax login
    rdbaLoginResetController.ajaxLogin();

    // detect language change.
    RdbaCommonAdminPublic.listenOnChangeLanguage();
}, false);