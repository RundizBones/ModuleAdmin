/**
 * 2 Step verification JS for its controller.
 */


class RdbaMfaController {


    /**
     * Listen on form submit to make ajax submit.
     * 
     * @returns {undefined}
     */
    listenOnFormSubmit() {
        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-loginmfa-form') {
                event.preventDefault();

                let thisForm = event.target;

                // reset form result placeholder
                document.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);
                formData.append(RdbaLoginMfa.csrfName, RdbaLoginMfa.csrfKeyPair[RdbaLoginMfa.csrfName]);
                formData.append(RdbaLoginMfa.csrfValue, RdbaLoginMfa.csrfKeyPair[RdbaLoginMfa.csrfValue]);

                RdbaCommon.XHR({
                    'url': RdbaLoginMfa.loginMfaUrl,
                    'method': RdbaLoginMfa.loginMfaMethod,
                    'data': formData,
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
                        RdbaLoginMfa.csrfKeyPair = response.csrfKeyPair;
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.redirectUrl) !== 'undefined') {
                        // if have to redirect (such as 2 step verification page).
                        window.location.href = response.redirectUrl;
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
                        RdbaLoginMfa.csrfKeyPair = response.csrfKeyPair;
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.redirectUrl) !== 'undefined') {
                        // if have to redirect (such as 2 step verification page).
                        window.location.href = response.redirectUrl;
                    }

                    if (response && response.gobackUrl) {
                        // if there is goback url (redirect url on success), redirect it.
                        window.location.href = response.gobackUrl;
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
    }// listenOnFormSubmit


}// RdbaMfaController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaMfaController = new RdbaMfaController();

    // ajax submit.
    rdbaMfaController.listenOnFormSubmit();
}, false);