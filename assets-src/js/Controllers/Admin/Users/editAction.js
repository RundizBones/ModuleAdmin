/**
 * Edit a user JS for its controller.
 */


class RdbaUsersEditController {


    /**
     * XHR get form data and set it to form fields.
     * 
     * This method was called from `staticInit()` method and outside.
     * 
     * @returns {undefined}
     */
    ajaxGetFormData() {
        if (!document.getElementById('rdba-edit-user-form')) {
            // if no editing form, do not working to waste cpu.
            return false;
        }

        let $ = jQuery.noConflict();
        let thisClass = this;
        let editForm = document.querySelector('#rdba-edit-user-form');
        this.resetInputAvatar();

        RdbaCommon.XHR({
            'url': RdbaUsers.getUserUrlBase + '/' + document.getElementById('user_id').value,
            'method': RdbaUsers.getUserMethod
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
                let form = document.getElementById('rdba-edit-user-form');
                let formElements = (form ? form.elements : []);
                for (var i = 0, len = formElements.length; i < len; ++i) {
                    formElements[i].disabled = true;
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                    editForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                    editForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                }
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
                if (Object.prototype.hasOwnProperty.call(user, prop) && document.getElementById(prop) && prop !== 'user_id') {
                    document.getElementById(prop).value = user[prop];
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

            // set any user fields.
            if (user.user_fields) {
                user.user_fields.forEach(function(item, index) {
                    if (item.field_name && item.field_value) {
                        // if there is field name and value.
                        // set fields value.
                        let targetElement = document.querySelector('#user_fields_' + item.field_name);
                        let targetElements = Array.from(document.querySelectorAll('.user_fields_' + item.field_name));

                        if (targetElements && targetElements.length > 0) {
                            if (typeof(item.field_value) !== 'object') {
                                item.field_value = [item.field_value];
                            }
                            $('.user_fields_' + item.field_name).val(item.field_value);// native js is never been as easy as or easier than jquery with this.
                        }
                        if (targetElement && targetElement.type.toLowerCase() !== 'file') {
                            $('#user_fields_' + item.field_name).val(item.field_value);// use this can set input and select (with multiple).
                        }

                        // show/hide to only selected avatar type.
                        if (item.field_name === 'rdbadmin_uf_avatar_type') {
                            // hide all avatar type form.
                            editForm.querySelectorAll('.rdbadmin-avatar-type-form').forEach(function(item, index) {
                                item.classList.add('rd-hidden');
                            });
                            // remove hidden class from only selected.
                            editForm.querySelector('.rdbadmin-avatar-type-' + item.field_value).classList.remove('rd-hidden');
                            // render gravatar.
                            if (RdbaCommon.isset(() => response.gravatarUrl)) {
                                thisClass.renderCurrentGravatar(response.gravatarUrl);
                            }
                        }

                        // render current avatar.
                        if (item.field_name === 'rdbadmin_uf_avatar') {
                            thisClass.renderCurrentAvatar(item.field_value);
                        }

                        // render email change history.
                        if (item.field_name === 'rdbadmin_uf_changeemail_history') {
                            let source = document.getElementById('list-email-changed-history-table-row-template').innerHTML;
                            let template = Handlebars.compile(source);
                            Handlebars.registerHelper('formatDate', function (dateValue, options) {
                                if (typeof(dateValue) !== 'undefined') {
                                    return moment(dateValue + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
                                } else {
                                    return '';
                                }
                            });
                            if (typeof(item.field_value) === 'object') {
                                let emails = item.field_value;
                                emails = emails.slice(0, 10);// limit display just xx items.
                                if (typeof(emails) === 'object') {
                                    item.field_value = emails;
                                }
                            }
                            let html = template(item);
                            if (
                                document.querySelector('#list-email-changed-history-table tbody') &&
                                document.querySelector('.list-email-changed-history')
                            ) {
                                document.querySelector('#list-email-changed-history-table tbody').insertAdjacentHTML('afterbegin', html);
                                document.querySelector('.list-email-changed-history').classList.remove('rd-hidden');
                            }
                        }
                    }// endif there is field name and value.
                });
            }

            // set roles field.
            if (user.users_roles) {
                let selectOptions = Array.from(document.querySelectorAll('#user_roles option'));
                user.users_roles.forEach(function(item, index) {
                    if (item.userrole_id) {
                        let findSelectOptions = selectOptions.find(c => c.value == item.userrole_id);
                        if (findSelectOptions) {
                            findSelectOptions.selected = true;
                        }
                    }
                });
            }

            if (user.user_status === '0') {
                document.getElementById('form-group-user_statustext').style.display = '';
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                    //console.log('new token was set during get form data.', response.csrfKeyPair);
                    editForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                    editForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                }
            }
        });
    }// ajaxGetFormData


    /**
     * Listen on avatar type change and switch avatar form.
     * 
     * @private This method was called from `staticInit()` method.
     * @returns {undefined}
     */
    listenAvatarTypeChange() {
        let thisClass = this;
        let editForm = document.querySelector('#rdba-edit-user-form');
        /*let inputAvatarType;
        if (editForm) {
            inputAvatarType = editForm.querySelectorAll('.user_fields_rdbadmin_uf_avatar_type');
        }*/

        if (editForm) {
            editForm.addEventListener('change', function(event) {
                if (RdbaCommon.isset(() => event.target.classList) && event.target.classList.contains('user_fields_rdbadmin_uf_avatar_type')) {
                    event.preventDefault();

                    let inputAvatarType = event.target;
                    // hide all avatar type form.
                    editForm.querySelectorAll('.rdbadmin-avatar-type-form').forEach(function(item, index) {
                        item.classList.add('rd-hidden');
                    });
                    // remove hidden class from only selected.
                    editForm.querySelector('.rdbadmin-avatar-type-' + inputAvatarType.value).classList.remove('rd-hidden');
                }
            });
        }
    }// listenAvatarTypeChange


    /**
     * Listen on change avatar to upload.
     * 
     * @link https://stackoverflow.com/a/35274078/128761 Prevent drop outside zone original source code.
     * @private This method was called from `staticInit()` method.
     * @returns {undefined}
     */
    listenAvatarUpload() {
        let thisClass = this;
        let editForm = document.querySelector('#rdba-edit-user-form');
        let dropzoneId = 'rdbadmin-select-avatar-dropzone';
        let inputAvatarId = 'user_fields_rdbadmin_uf_avatar';
        let inputAvatarElement = editForm.querySelector('#' + inputAvatarId);
        let inputFileQueue = editForm.querySelector('.rd-input-files-queue');
        let uploadStatusPlaceholder = editForm.querySelector('#rdbadmin-avatar-upload-status-placeholder');

        // prevent drag & drop image file outside drop zone. --------------------------------------------------
        window.addEventListener('dragenter', function (e) {
            if (e.target.id != dropzoneId && e.target.id != inputAvatarId) {
                e.preventDefault();
                e.dataTransfer.effectAllowed = 'none';
                e.dataTransfer.dropEffect = 'none';
            } else {
                e.preventDefault();// prevent redirect page to show dropped image.
            }
        }, false);
        window.addEventListener('dragover', function (e) {
            if (e.target.id != dropzoneId && e.target.id != inputAvatarId) {
                e.preventDefault();
                e.dataTransfer.effectAllowed = 'none';
                e.dataTransfer.dropEffect = 'none';
            } else {
                e.preventDefault();// prevent redirect page to show dropped image.
            }
        });
        // end prevent drag & drop image file outside drop zone. ---------------------------------------------

        window.addEventListener('drop', function(event) {
            console.log(event.target.id);
            if (RdbaCommon.isset(() => event.target.id) && (event.target.id === dropzoneId || event.target.id === inputAvatarId)) {
                // if dropped in drop zone or input file.
                event.preventDefault();

                //console.log('user dropped file.', event.dataTransfer.files);
                if (event.dataTransfer.files.length > 1) {
                    RDTAAlertDialog.alert({
                        'type': 'error',
                        'html': RdbaUsers.txtSelectOnlyOneFile,
                    });
                } else {
                    editForm = document.querySelector('#rdba-edit-user-form');// force get new data. (prevent re-open dialog and this action will not working).
                    inputAvatarElement = editForm.querySelector('#' + inputAvatarId);// force get new data. (prevent re-open dialog and this action will not working).
                    inputAvatarElement.files = event.dataTransfer.files;
                    //console.log('success set files to input file.', inputAvatarElement);
                    inputAvatarElement.dispatchEvent(new Event('change', { 'bubbles': true }));
                }
            } else {
                // if not dropped in drop zone and input file.
                event.preventDefault();
                //console.log('not in drop zone.');
                event.dataTransfer.effectAllowed = 'none';
                event.dataTransfer.dropEffect = 'none';
            }
        });
        //console.log('listening avatar upload.');

        if (inputAvatarElement) {
            document.addEventListener('rdta.custominputfile.change', function(event) {
                event.preventDefault();

                let confirmUpload = confirm(RdbaUsers.txtConfirmUploadAvatar);
                if (confirmUpload === true) {
                    // if user confirmed upload.
                    editForm = document.querySelector('#rdba-edit-user-form');// force get new data. (prevent re-open dialog and this action will not working).
                    inputAvatarElement = event.target;// force get new data. (prevent re-open dialog and this action will not working).

                    // add loading icon.
                    uploadStatusPlaceholder.innerHTML = '<i class="fas fa-spinner fa-pulse loading-icon"></i> ' + RdbaUsers.txtUploading;
                    // lock submit button
                    editForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                    let formData = new FormData();
                    formData.append(RdbaUsers.csrfName, editForm.querySelector('#rdba-form-csrf-name').value);
                    formData.append(RdbaUsers.csrfValue, editForm.querySelector('#rdba-form-csrf-value').value);
                    formData.append('user_fields[rdbadmin_uf_avatar]', inputAvatarElement.files[0]);

                    let editingUserId = editForm.querySelector('#user_id').value;
                    let submitUrl = RdbaUsers.avatarUploadRESTUrl;
                    submitUrl = submitUrl.replace('{{user_id}}', editingUserId);

                    RdbaCommon.XHR({
                        'url': submitUrl,
                        'method': RdbaUsers.avatarUploadRESTMethod,
                        //'contentType': 'multipart/form-data',
                        'data': formData,
                        'dataType': 'json',
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
                            RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                            if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                                editForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                                editForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                            }
                        }

                        return Promise.reject(responseObject);
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        if (response) {
                            if (response.uploadSuccess && response.uploadSuccess === true) {
                                // if upload success.
                                // reset input file field.
                                thisClass.resetInputAvatar();
                            }

                            if (response.relativePublicUrl) {
                                // if there is url.
                                // render current avatar.
                                thisClass.renderCurrentAvatar(response.relativePublicUrl);
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                            if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                                editForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                                editForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                            }
                        }

                        return Promise.resolve(responseObject);
                    })
                    .finally(function() {
                        // remove loading icon and upload status text.
                        uploadStatusPlaceholder.innerHTML = '';
                        // unlock submit button
                        editForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                    });
                } else {
                    // if user cancelled upload.
                    thisClass.resetInputAvatar();
                    //console.log('user cancelled upload profile picture.');
                }
            });
        }
    }// listenAvatarUpload


    /**
     * Listen click on button to delete avatar.
     * 
     * @private This method was called from `staticInit()` method.
     * @returns {undefined}
     */
    listenClickDeleteAvatar() {
        let thisClass = this;
        let editForm = document.querySelector('#rdba-edit-user-form');

        if (editForm) {
            document.addEventListener('click', function(event) {
                if (event.target && event.target.closest('#rdbadmin-delete-current-avatar-button')) {
                    // if clicking on delete avatar button.
                    event.preventDefault();
                    let confirmResult = confirm(RdbaUsers.txtConfirmDeleteAvatar);

                    if (confirmResult === true) {
                        editForm = document.querySelector('#rdba-edit-user-form');// force get new data. (prevent re-open dialog and this action will not working).
                        let deleteButton = event.target.closest('#rdbadmin-delete-current-avatar-button');
                        let editingUserId = editForm.querySelector('#user_id').value;
                        let deleteStatusPlaceholder = editForm.querySelector('#rdbadmin-delete-avatar-status-placeholder');

                        // add loading icon.
                        deleteStatusPlaceholder.innerHTML = '<i class="fas fa-spinner fa-pulse loading-icon"></i> ';
                        // lock delete button
                        deleteButton.setAttribute('disabled', 'disabled');

                        let formData = new FormData();
                        formData.append(RdbaUsers.csrfName, editForm.querySelector('#rdba-form-csrf-name').value);
                        formData.append(RdbaUsers.csrfValue, editForm.querySelector('#rdba-form-csrf-value').value);
                        formData.append('user_id', editingUserId);

                        let deleteUrl = RdbaUsers.avatarDeleteRESTUrl;
                        deleteUrl = deleteUrl.replace('{{user_id}}', editingUserId);

                        RdbaCommon.XHR({
                            'url': deleteUrl,
                            'method': RdbaUsers.avatarDeleteRESTMethod,
                            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'data': new URLSearchParams(_.toArray(formData)).toString(),
                            'dataType': 'json',
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
                                RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                                if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                                    editForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                                    editForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                                }
                            }

                            return Promise.reject(responseObject);
                        })
                        .then(function(responseObject) {
                            // XHR success.
                            let response = responseObject.response;

                            if (response) {
                                if (response.deleteSuccess && response.deleteSuccess === true) {
                                    editForm.querySelector('#rdbadmin-current-avatar').innerHTML = '';
                                }
                            }

                            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                                RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                                if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                                    editForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                                    editForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                                }
                            }

                            return Promise.resolve(responseObject);
                        })
                        .finally(function() {
                            // remove loading icon and delete status.
                            deleteStatusPlaceholder.innerHTML = '';
                            // unlock delete button
                            deleteButton.removeAttribute('disabled');
                        });
                    }
                }
            });
        }
    }// listenClickDeleteAvatar


    /**
     * Listen on form submit.
     * 
     * @private This method was called from `staticInit()` method.
     * @returns {undefined}
     */
    listenFormSubmit() {
        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-edit-user-form') {
                event.preventDefault();

                let thisForm = event.target;

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);
                if (RdbaUsers && RdbaUsers.isInDataTablesPage && RdbaUsers.isInDataTablesPage === true) {
                    formData.append('isInDataTablesPage', 'true');
                }

                RdbaCommon.XHR({
                    'url': RdbaUsers.editUserSubmitUrlBase + '/' + document.getElementById('user_id').value,
                    'method': RdbaUsers.editUserMethod,
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
                        RdbaUsers.csrfKeyPair = response.csrfKeyPair;
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

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaUsers.csrfKeyPair = response.csrfKeyPair;
                        if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                            thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                            thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                        }
                    }

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
     * Listen on status change and toggle display status description input.
     * 
     * @private This method was called from `staticInit()` method.
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
     * Render current avatar.
     * 
     * @private This method was called from `ajaxGetFormData()`, `listenAvatarUpload()` methods.
     * @param {string} avatarRelativeUrl
     * @returns {undefined}
     */
    renderCurrentAvatar(avatarRelativeUrl) {
        let editForm = document.querySelector('#rdba-edit-user-form');
        let currentAvatarPlaceholder = editForm.querySelector('#rdbadmin-current-avatar');

        let source = editForm.querySelector('#rdbadmin-current-avatar-display-template').innerHTML;
        let template = Handlebars.compile(source);

        let item = {};
        item.rdbadmin_uf_avatar = RdbaUsers.urlAppBased + '/' + avatarRelativeUrl;
        let html = template(item);
        currentAvatarPlaceholder.innerHTML = html;
    }// renderCurrentAvatar


    /**
     * Render current Gravatar.
     * 
     * @private This method was called from `ajaxGetFormData()` method.
     * @param {string} avatarRelativeUrl
     * @returns {undefined}
     */
    renderCurrentGravatar(gravatarUrl) {
        let editForm = document.querySelector('#rdba-edit-user-form');
        let currentGravatarPlaceholder = editForm.querySelector('#rdbadmin-avatar-current-gravatar');

        let source = editForm.querySelector('#rdbadmin-current-gravatar-display-template').innerHTML;
        let template = Handlebars.compile(source);

        let item = {};
        item.gravatarUrl = gravatarUrl;
        let html = template(item);
        currentGravatarPlaceholder.innerHTML = html;
    }// renderCurrentGravatar


    /**
     * Reset input file (profile picture or avatar).
     * 
     * @private This method was called from `ajaxGetFormData()`, `listenAvatarUpload()` methods.
     * @returns {undefined}
     */
    resetInputAvatar() {
        let editForm = document.querySelector('#rdba-edit-user-form');
        let inputAvatarId = 'user_fields_rdbadmin_uf_avatar';
        let inputAvatarElement = editForm.querySelector('#' + inputAvatarId);
        let inputFileQueue = editForm.querySelector('.rd-input-files-queue');

        let parent = inputAvatarElement.parentElement;
        let wrap = document.createElement('form');
        wrap.appendChild(inputAvatarElement);
        parent.appendChild(wrap);
        // reset form.
        inputAvatarElement.closest('form').reset();
        // unwrap <form>
        inputAvatarElement.parentNode.replaceWith(inputAvatarElement);
        // clear input file queue.
        inputFileQueue.innerHTML = '';
    }// resetInputAvatar


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
        // listen on status change and toggle description.
        thisClass.listenStatusChange();
        // listen on avatar type change and switch avatar form.
        thisClass.listenAvatarTypeChange();
        // listen on change avatar to upload.
        thisClass.listenAvatarUpload();
        // listen click button to delete avatar.
        thisClass.listenClickDeleteAvatar();
        // listen on form submit and make it AJAX request.
        thisClass.listenFormSubmit();
   }// staticInit


}// RdbaUsersEditController


if (document.readyState !== 'loading') {
    // if document loaded.
    // equivalent to jquery document ready.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    // because this condition will be working on js loaded via ajax,
    // but 'DOMContentLoaded' will be working on load the full page.
    RdbaUsersEditController.staticInit();
}
document.addEventListener('DOMContentLoaded', function() {
    RdbaUsersEditController.staticInit();
}, false);
document.addEventListener('rdba.users.editing.init', function() {
    // manual trigger initialize class.
    // this is required when... user click edit > save > close dialog > click edit other > now it won't load if there is no this listener.
    let rdbaUsersEditController = new RdbaUsersEditController();
    // ajax get form data.
    rdbaUsersEditController.ajaxGetFormData();
});