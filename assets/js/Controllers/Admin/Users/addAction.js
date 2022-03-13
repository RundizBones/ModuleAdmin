/**
 * Add new user JS for its controller.
 */


class RdbaUsersAddController {


    /**
     * Listen on form submit and make it XHR.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let $ = jQuery.noConflict();

        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-add-user-form') {
                event.preventDefault();

                let thisForm = event.target;

                // set csrf again to prevent firefox form cached.
                if (!RdbaUsers.isInDataTablesPage) {
                    thisForm.querySelector('#rdba-form-csrf-name').value = RdbaUsers.csrfKeyPair[RdbaUsers.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = RdbaUsers.csrfKeyPair[RdbaUsers.csrfValue];
                }

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);

                // form data cannot use with jquery .ajax directly or it will be `append` error.
                // to make it query string (param1=value1&param2=value2), use `URLSearchParams().toString()`.
                // @link https://stackoverflow.com/a/44033425/128761
                // @link https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
                $.ajax({
                    url: RdbaUsers.addUserUrl,
                    method: RdbaUsers.addUserMethod,
                    data: new URLSearchParams(_.toArray(formData)).toString(),
                    dataType: 'json'
                })
                .done(function(data, textStatus, jqXHR) {
                    let response = data;

                    if (response.redirectBack) {
                        if (RdbaUsers && RdbaUsers.isInDataTablesPage && RdbaUsers.isInDataTablesPage === true) {
                            // this is opening in dialog, close the dialog and reload page.
                            document.querySelector('#rdba-users-dialog [data-dismiss="dialog"]').click();
                            //window.location.reload();// use datatables reload instead.
                            jQuery('#usersTable').DataTable().ajax.reload(null, false);
                        } else {
                            // this is in its page, redirect to the redirect back url.
                            window.location.href = response.redirectBack;
                        }
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    let response;
                    if (typeof(jqXHR) === 'object' && typeof(jqXHR.responseJSON) !== 'undefined') {
                        response = jqXHR.responseJSON;
                    } else if (typeof(jqXHR) === 'object' && typeof(jqXHR.responseText) !== 'undefined') {
                        response = jqXHR.responseText;
                    } else {
                        response = jqXHR;
                    }
                    if (typeof(response) === 'undefined' || response === null) {
                        response = {};
                    }
                })
                .always(function(data, textStatus, jqXHR) {
                    let response;
                    if (typeof(data) === 'object' && typeof(data.responseJSON) !== 'undefined') {
                        response = data.responseJSON;
                    } else if (typeof(data) === 'object' && typeof(data.responseText) !== 'undefined') {
                        response = data.responseText;
                    } else {
                        response = data;
                    }
                    if (typeof(response) === 'undefined' || response === null) {
                        response = {};
                    }

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                            let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                            thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                        if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                            thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                            thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                        }
                    }

                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    thisForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                });
            }
        }, false);
    }// listenFormSubmit


    /**
     * Listen on notify user checkbox, if it was checked then the status field will be hidden.
     * 
     * @returns {undefined}
     */
    listenNotifyUserCheckbox() {
        document.addEventListener('click', function(event) {
            if (event.target && event.target.name === 'notify_user') {
                let targetCheckbox = event.target;
                let statusField = document.getElementById('form-group-user_status');
                let statusTextField = document.getElementById('form-group-user_statustext');
                let statusTextFieldDisplay;
                if (statusField.querySelector('#user_status').value === '0') {
                    statusTextFieldDisplay = '';
                } else {
                    statusTextFieldDisplay = 'none';
                }

                if (targetCheckbox.checked === true) {
                    statusField.style.display = 'none';
                    statusTextField.style.display = 'none';
                } else {
                    statusField.style.display = '';
                    statusTextField.style.display = statusTextFieldDisplay;
                }
            }
        }, false);
    }// listenNotifyUserCheckbox


    /**
     * Listen on status change and toggle display status description input.
     * 
     * @returns {undefined}
     */
    listenStatusChange() {
        document.addEventListener('change', function(event) {
            if (event.target && event.target.id === 'user_status') {
                event.preventDefault();
                if (event.target.value === '0') {
                    document.querySelector('#form-group-user_statustext').style.display = '';
                } else {
                    document.querySelector('#form-group-user_statustext').style.display = 'none';
                }
            }
        }, false);
    }// listenStatusChange


    /**
     * Static initialize the class.
     * 
     * This is useful for ajax page.
     * 
     * @returns {undefined}
     */
    static staticInit() {
        let thisClass = new this() ;

        // listen on notify user checkbox.
        thisClass.listenNotifyUserCheckbox();
        // listen on status change and toggle description.
        thisClass.listenStatusChange();
        // listen on form submit and make it AJAX request.
        thisClass.listenFormSubmit();
   }// staticInit


}// RdbaUsersAddController


document.addEventListener('rdba.users.editing.newinit', function(event) {
    // listen on new assets loaded.
    // this will be working on js loaded via AJAX.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/add') !== false
    ) {
        RdbaUsersAddController.staticInit();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbaUsersAddController.staticInit();
}, false);