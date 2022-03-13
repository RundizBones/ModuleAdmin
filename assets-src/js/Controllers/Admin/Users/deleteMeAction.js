/**
 * Delete me JS for its controller.
 */


class RdbaUsersDeleteMeController {


    /**
     * XHR get form data and set it to form fields.
     * 
     * @returns {undefined}
     */
    ajaxGetFormData() {
        // disable button.
        document.querySelector('.rdba-submit-button').disabled = true;

        RdbaCommon.XHR({
            'url': RdbaDeleteMe.getUserUrlBase + '/' + RdbaDeleteMe.user_id,
            'method': RdbaDeleteMe.getUserMethod
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

            if (responseObject && responseObject.status && responseObject.status === 404) {
                // if not found.
                // disable form.
                let form = document.getElementById('rdba-delete-me-confirm-form');
                let formElements = (form ? form.elements : []);
                for (var i = 0, len = formElements.length; i < len; ++i) {
                    formElements[i].disabled = true;
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaDeleteMe.csrfKeyPair = response.csrfKeyPair;
            }
        })
        .then(function(responseObject) {
            let response = (responseObject ? responseObject.response : {});
            let user = (response.user ? response.user : {});

            // config moment locale to use it later with any dates.
            if (RdbaCommon.isset(() => RdbaUIXhrCommonData.currentLocale)) {
                moment.locale(RdbaUIXhrCommonData.currentLocale);
            }
            let siteTimezone;
            if (RdbaCommon.isset(() => RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone)) {
                siteTimezone = RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone;
            } else {
                siteTimezone = 'Asia/Bangkok';
            }

            for (let prop in user) {
                if (Object.prototype.hasOwnProperty.call(user, prop) && document.getElementById(prop)) {
                    document.getElementById(prop).innerHTML = user[prop];
                }
            }// endfor;

            // render dates
            if (user.user_create_gmt) {
                document.getElementById('user_create').innerHTML = moment(user.user_create_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
            }
            if (user.user_lastupdate_gmt) {
                document.getElementById('user_lastupdate').innerHTML = moment(user.user_lastupdate_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
            }
            if (user.user_lastlogin_gmt) {
                document.getElementById('user_lastlogin').innerHTML = moment(user.user_lastlogin_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
            }

            // re-enable button.
            document.querySelector('.rdba-submit-button').disabled = false;

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaDeleteMe.csrfKeyPair = response.csrfKeyPair;
            }
        });
    }// ajaxGetFormData


    /**
     * Listen on form submit and make it ajax submit.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisForm = document.getElementById('rdba-delete-me-confirm-form');

        thisForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // reset form result placeholder
            thisForm.querySelector('.form-result-placeholder').innerHTML = '';
            // add spinner icon
            thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

            let formData = new FormData(thisForm);
            formData.append(RdbaDeleteMe.csrfName, RdbaDeleteMe.csrfKeyPair[RdbaDeleteMe.csrfName]);
            formData.append(RdbaDeleteMe.csrfValue, RdbaDeleteMe.csrfKeyPair[RdbaDeleteMe.csrfValue]);
            formData.append('action', 'delete');

            RdbaCommon.XHR({
                'url': RdbaDeleteMe.deleteMeSubmitUrl,
                'method': RdbaDeleteMe.deleteMeMethod,
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
                        thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                    }
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaDeleteMe.csrfKeyPair = response.csrfKeyPair;
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
                    RdbaDeleteMe.csrfKeyPair = response.csrfKeyPair;
                }

                if (response.redirectBack) {
                    window.location.href = response.redirectBack;
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
    }// listenFormSubmit


}// RdbaUsersDeleteMeController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaDeleteMeController = new RdbaUsersDeleteMeController();

    // ajax get data and display it.
    rdbaDeleteMeController.ajaxGetFormData();
    // ajax form submit (confirm delete).
    rdbaDeleteMeController.listenFormSubmit();
}, false);