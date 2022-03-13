/**
 * Add new role JS for its controller.
 */


class RdbaRolesAddController {


    /**
     * Listen on form submit and make it XHR.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let $ = jQuery.noConflict();

        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-add-role-form') {
                event.preventDefault();

                let thisForm = event.target;

                // set csrf again to prevent firefox form cached.
                if (!RdbaRoles.isInDataTablesPage) {
                    thisForm.querySelector('#rdba-form-csrf-name').value = RdbaRoles.csrfKeyPair[RdbaRoles.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = RdbaRoles.csrfKeyPair[RdbaRoles.csrfValue];
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
                    url: RdbaRoles.addRoleSubmitUrl,
                    method: RdbaRoles.addRoleMethod,
                    data: new URLSearchParams(_.toArray(formData)).toString(),
                    dataType: 'json'
                })
                .done(function(data, textStatus, jqXHR) {
                    let response = data;

                    if (response.redirectBack) {
                        if (RdbaRoles && RdbaRoles.isInDataTablesPage === true) {
                            // this is opening in dialog, close the dialog and reload page.
                            document.querySelector('#rdba-roles-dialog [data-dismiss="dialog"]').click();
                            //window.location.reload();// use datatables reload instead.
                            jQuery('#rolesTable').DataTable().ajax.reload(null, false);
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
                        RdbaRoles.csrfKeyPair = response.csrfKeyPair;
                        if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                            thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                            thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                        }
                    }

                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock login button
                    thisForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                });
            }
        }, false);
    }// listenFormSubmit


    /**
     * Static initialize the class.
     * 
     * @returns {undefined}
     */
    static staticInit() {
        let thisClass = new this() ;

        // listen on form submit and make it AJAX request.
        thisClass.listenFormSubmit();
    }// staticInit


}// RdbaRolesAddController


document.addEventListener('rdba.roles.editing.newinit', function(event) {
    // listen on new assets loaded.
    // this will be working on js loaded via AJAX.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/add') !== false
    ) {
        RdbaRolesAddController.staticInit();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbaRolesAddController.staticInit();
}, false);