/**
 * Edit a role JS for its controller.
 */


class RdbaRolesEditController {


    /**
     * XHR get form data and set it to form fields.
     * 
     * @returns {undefined}
     */
    ajaxGetFormData() {
        let thisForm = document.getElementById('rdba-edit-role-form');
        if (!thisForm) {
            // if no editing form, do not working to waste cpu.
            return false;
        }

        // set csrf again to prevent firefox form cached.
        if (!RdbaRoles.isInDataTablesPage) {
            thisForm.querySelector('#rdba-form-csrf-name').value = RdbaRoles.csrfKeyPair[RdbaRoles.csrfName];
            thisForm.querySelector('#rdba-form-csrf-value').value = RdbaRoles.csrfKeyPair[RdbaRoles.csrfValue];
        }

        RdbaCommon.XHR({
            'url': RdbaRoles.getRoleUrlBase + '/' + document.getElementById('userrole_id').value,
            'method': RdbaRoles.getRoleMethod
        })
        .catch(function(responseObject) {
            console.error(responseObject);
            let response = (responseObject ? responseObject.response : {});

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                    thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                }
            }

            if (responseObject && responseObject.status && responseObject.status === 404) {
                // if not found.
                // disable form.
                let formElements = (thisForm ? thisForm.elements : []);
                for (var i = 0, len = formElements.length; i < len; ++i) {
                    formElements[i].disabled = true;
                }
            }
        })
        .then(function(responseObject) {
            let response = (responseObject ? responseObject.response : {});
            let role = (response.role ? response.role : {});

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

            for (let prop in role) {
                if (
                    Object.prototype.hasOwnProperty.call(role, prop) && 
                    document.getElementById(prop) && 
                    prop !== 'userrole_id' &&
                    role[prop] !== null
                ) {
                    document.getElementById(prop).value = RdbaCommon.unEscapeHtml(role[prop]);
                }
            }// endfor;

            // render dates
            if (role.userrole_create_gmt) {
                document.getElementById('userrole_create').innerHTML = moment(role.userrole_create_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
            }
            if (role.userrole_lastupdate_gmt) {
                document.getElementById('userrole_lastupdate').innerHTML = moment(role.userrole_lastupdate_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
            }
        })
        ;
    }// ajaxGetFormData


    /**
     * Listen on form submit and ajax save data.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-edit-role-form') {
                event.preventDefault();

                let thisForm = event.target;

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);

                RdbaCommon.XHR({
                    'url': RdbaRoles.editRoleSubmitUrlBase + '/' + document.getElementById('userrole_id').value,
                    'method': RdbaRoles.editRoleMethod,
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
                        RdbaRoles.csrfKeyPair = response.csrfKeyPair;
                        if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                            thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                            thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                        }
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (response.redirectBack) {
                        if (RdbaRoles && RdbaRoles.isInDataTablesPage && RdbaRoles.isInDataTablesPage === true) {
                            // this is opening in dialog, close the dialog and reload page.
                            document.querySelector('#rdba-roles-dialog [data-dismiss="dialog"]').click();
                            //window.location.reload();// use datatables reload instead.
                            jQuery('#rolesTable').DataTable().ajax.reload(null, false);
                        } else {
                            // this is in its page, redirect to the redirect back url.
                            window.location.href = response.redirectBack;
                        }
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

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    thisForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                });
            }
        }, false);
    }// listenFormSubmit


    /**
     * Static initialize the class.
     * 
     * This is useful for ajax page.
     * 
     * @returns {undefined}
     */
    static staticInit() {
        let thisClass = new this() ;

        // ajax get form data.
        thisClass.ajaxGetFormData();
        // list form submit save data.
        thisClass.listenFormSubmit();
   }// staticInit


}// RdbaRolesEditController


if (document.readyState !== 'loading') {
    // if document loaded.
    // equivalent to jquery document ready.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    // because this condition will be working on js loaded via ajax,
    // but 'DOMContentLoaded' will be working on load the full page.
    RdbaRolesEditController.staticInit();
}
document.addEventListener('DOMContentLoaded', function() {
    RdbaRolesEditController.staticInit();
}, false);
document.addEventListener('rdba.roles.editing.reinit', function() {
    // manual trigger initialize class.
    // this is required when... user click edit > save > close dialog > click edit other > now it won't load if there is no this listener.
    let rdbaRolesEditController = new RdbaRolesEditController();

    // ajax get form data.
    rdbaRolesEditController.ajaxGetFormData();
});