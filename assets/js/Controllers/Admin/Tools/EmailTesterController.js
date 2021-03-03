/**
 * Email tester JS for its controller.
 */


class RdbaEmailTesterController {


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        this.listenOnFormSubmit();
    }// init


    /**
     * Listen on form submit.
     * 
     * @returns {undefined}
     */
    listenOnFormSubmit() {
        let thisForm = document.getElementById('rdba-toolsemailtester-form');
        if (thisForm) {
            thisForm.addEventListener('submit', function(event) {
                event.preventDefault();

                let toEmail = thisForm.querySelector('#rdba-tools-emailtester-toemail');

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .submit-button-wrapper').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);
                formData.append(RdbaToolsEmailTesterObject.csrfName, RdbaToolsEmailTesterObject.csrfKeyPair[RdbaToolsEmailTesterObject.csrfName]);
                formData.append(RdbaToolsEmailTesterObject.csrfValue, RdbaToolsEmailTesterObject.csrfKeyPair[RdbaToolsEmailTesterObject.csrfValue]);

                RdbaCommon.XHR({
                    'url': RdbaToolsEmailTesterObject.emailTestSubmitUrl,
                    'method': RdbaToolsEmailTesterObject.emailTestSubmitMethod,
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
                        RdbaToolsEmailTesterObject.csrfKeyPair = response.csrfKeyPair;
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
                            thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaToolsEmailTesterObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    thisForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                });
            });
        }
    }// listenOnFormSubmit


}// RdbaEmailTesterController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaEmailTesterController = new RdbaEmailTesterController();

    // initialize the class.
    rdbaEmailTesterController.init();
}, false);