/**
 * Permissions list (management) JS for its controller.
 * 
 * @link https://github.com/martinberlin/datatables-dynamic-columns Example of dynamic column using DataTables. This is for reference in case that some other listing page want to use it.
 */


class RdbAdminPermissionsController {


    /**
     * Ajax get permissions data and render data table.
     * 
     * @returns {undefined}
     */
    ajaxGetDataAndRender() {
        let datatableSelector = '#permissionsTable';

        // add loading message to form result placeholder.
        let alertClass = RdbaCommon.getAlertClassFromStatus('info');
        let alertBox = RdbaCommon.renderAlertHtml(alertClass, RdbaPermissions.txtLoading);
        document.querySelector('.form-result-placeholder').innerHTML = alertBox;

        RdbaCommon.XHR({
            'url': RdbaPermissions.getPermissionsUrl + '?permissionFor=' + RdbaPermissions.permissionFor + '&permissionForUserId=' + RdbaPermissions.permissionForUserId + '&permissionModule=' + RdbaPermissions.permissionModule,
            'method': RdbaPermissions.getPermissionsMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
            'dataType': 'json'
        })
        .catch(function(responseObject) {
            // XHR failed
            let response = responseObject.response;
            console.error('[rdba] ', responseObject);

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
            }
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            // clear form result placeholder.
            document.querySelector('.form-result-placeholder').innerHTML = '';

            if (response && response.listModules) {
                let filterPermissionModule = document.getElementById('rdba-filter-permissionModule');
                if (filterPermissionModule) {
                    response.listModules.forEach(function(item, index) {
                        filterPermissionModule.insertAdjacentHTML('beforeend', '<option value="' + item + '">' + item + '</option>');
                    });
                    filterPermissionModule.value = RdbaPermissions.permissionModule;
                }
            }

            let dataTableElement = document.querySelector(datatableSelector);
            let totalColumns = 0;

            if (response && response.listColumns && _.isArray(response.listColumns)) {
                let thTemplate = document.getElementById('rdba-datatable-header-cells').innerHTML;
                let template = Handlebars.compile(thTemplate);
                let compiledHtml = template(response.listColumns);
                dataTableElement.querySelector('thead > tr').innerHTML = compiledHtml;
                dataTableElement.querySelector('tfoot > tr').innerHTML = compiledHtml;
                totalColumns = response.listColumns.length;
            }

            if (response && response.listItems && _.isArray(response.listItems) && !_.isEmpty(response.listItems)) {
                let tableDataRowTemplate = document.getElementById('rdba-datatable-row-data').innerHTML;
                Handlebars.registerHelper('ifEquals', function (v1, v2, options) {
                    if (v1 === v2) {
                        return options.fn(this);
                    }
                    return options.inverse(this);
                });
                let template = Handlebars.compile(tableDataRowTemplate);
                let compiledHtml = template(response.listItems);
                dataTableElement.querySelector('tbody').innerHTML = compiledHtml;
            } else {
                dataTableElement.querySelector('tbody').innerHTML = '<tr><td colspan="' + totalColumns + '">' + RdbaPermissions.txtNoData + '</td></tr>';
            }

            if (response && response.userData) {
                let filterUserId = document.querySelector('#rdba-filter-permissionForUserId');
                if (filterUserId) {
                    filterUserId.value = response.userData.user_id;
                }

                let filterUserName = document.querySelector('#rdba-filter-permissionForUserName');
                if (filterUserName) {
                    filterUserName.value = response.userData.user_login;
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
            }
        })
        ;
    }// ajaxGetDataAndRender


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        let $ = jQuery.noConflict();
        let thisClass = this;

        $.when(uiXhrCommonData)
        .done(function() {
            // make sure filters display correctly before gets render.
            thisClass.validateDatatableFilters();

            // ajax get data and render data table.
            thisClass.ajaxGetDataAndRender();
            // listen on filter permission for changed and show/hide user input.
            thisClass.listenOnFilterPermissionForChange();
            // listen on filter username and auto complete for user id.
            thisClass.listenOnFilterPermissionUsernameAutocomplete();
            // listen on filter form submit and make it xhr.
            thisClass.listenOnFilterSubmitAjax();
            // listen on popstate filter and make it xhr.
            thisClass.listenOnPopstateFilterAjax();

            // listen checkbox click and ajax save.
            thisClass.listenOnCheckboxClicked();
            // listen clear button clicked.
            thisClass.listenOnClearPermissions();
        });

        RDTATooltips.init('[data-toggle="tooltip"]');
    }// init


    /**
     * Listen on checkbox clicked and do ajax save.
     * 
     * @returns {undefined}
     */
    listenOnCheckboxClicked() {
        let $ = jQuery.noConflict();

        document.addEventListener('click', function(event) {
            let checkboxElement = event.target;
            if (RdbaCommon.isset(() => checkboxElement.classList) && checkboxElement.classList.contains('rdba-permission-checkbox')) {
                // if user is clicking on permission check box only.
                if (checkboxElement.dataset && checkboxElement.dataset.alwayschecked === 'true') {
                    event.preventDefault();
                    RDTAAlertDialog.alert({
                        'text': RdbaPermissions.txtPermissionThisRoleCantChange,
                        'type': 'warning'
                    });
                    return false;
                }

                let actingIcon = '<i class="fa-solid fa-spinner fa-pulse fa-fw process-icon"></i>';
                let savedIcon = '<i class="fa-solid fa-check fa-fw process-icon"></i>';

                // add saving in progress icon (loading icon).
                if (checkboxElement) {
                    $(checkboxElement).siblings('.rdba-permission-checkbox-action-status').html(actingIcon);
                }

                let formData = new FormData();
                formData.append(RdbaPermissions.csrfName, RdbaPermissions.csrfKeyPair[RdbaPermissions.csrfName]);
                formData.append(RdbaPermissions.csrfValue, RdbaPermissions.csrfKeyPair[RdbaPermissions.csrfValue]);
                formData.append('permissionFor', RdbaPermissions.permissionFor);
                formData.append(checkboxElement.name, checkboxElement.value);
                formData.append('module_system_name', RdbaPermissions.permissionModule);
                formData.append('permission_page', checkboxElement.dataset.permission_page);
                formData.append('permission_action', checkboxElement.dataset.permission_action);
                formData.append('checked', checkboxElement.checked);

                RdbaCommon.XHR({
                    'url': RdbaPermissions.editPermissionSubmitUrl,
                    'method': RdbaPermissions.editPermissionSubmitMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'data': new URLSearchParams(_.toArray(formData)).toString(),
                    'dataType': 'json'
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;
                    console.error('[rdba] ', responseObject);

                    $(checkboxElement).siblings('.rdba-permission-checkbox-action-status').html('');

                    if (checkboxElement.checked === true) {
                        // if click check box to check
                        checkboxElement.checked = false;
                    } else {
                        // if click check box to uncheck
                        checkboxElement.checked = true;
                    }

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RDTAAlertDialog.alert({
                                'text': response.formResultMessage,
                                'type': response.formResultStatus
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    $(checkboxElement).siblings('.rdba-permission-checkbox-action-status').html(savedIcon);

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RDTAAlertDialog.alert({
                                'text': response.formResultMessage,
                                'type': response.formResultStatus
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    if (RdbaCommon.isset(() => checkboxElement.nextElementSibling)) {
                        setTimeout(function() {
                            $(checkboxElement).siblings('.rdba-permission-checkbox-action-status').find('.process-icon').addClass('rd-animation fade fade-out');
                        }, 1000);
                        setTimeout(function() {
                            $(checkboxElement).siblings('.rdba-permission-checkbox-action-status').html('');
                        }, 1400);
                    }
                });
            }
        });
    }// listenOnCheckboxClicked


    /**
     * Clear permissions for selected module.
     * 
     * @returns {undefined}
     */
    listenOnClearPermissions() {
        let clearButton = document.getElementById('rdba-permission-reset');
        let thisClass = this;

        if (clearButton) {
            clearButton.addEventListener('click', function(event) {
                let confirmResult = confirm(RdbaPermissions.txtAreYouSureClear);
                if (confirmResult === true) {
                    let formData = new FormData();
                    formData.append(RdbaPermissions.csrfName, RdbaPermissions.csrfKeyPair[RdbaPermissions.csrfName]);
                    formData.append(RdbaPermissions.csrfValue, RdbaPermissions.csrfKeyPair[RdbaPermissions.csrfValue]);
                    formData.append('permissionFor', RdbaPermissions.permissionFor);
                    formData.append('module_system_name', RdbaPermissions.permissionModule);

                    RdbaCommon.XHR({
                        'url': RdbaPermissions.clearPermissionsSubmitUrlBase + '/' + RdbaPermissions.permissionModule,
                        'method': RdbaPermissions.clearPermissionsSUbmitMethod,
                        'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'data': new URLSearchParams(_.toArray(formData)).toString(),
                        'dataType': 'json'
                    })
                    .catch(function(responseObject) {
                        // XHR failed.
                        let response = responseObject.response;
                        console.error('[rdba] ', responseObject);

                        if (typeof(response) !== 'undefined') {
                            if (typeof(response.formResultMessage) !== 'undefined') {
                                if (response.alertDialog === true) {
                                    RDTAAlertDialog.alert({
                                        'text': response.formResultMessage,
                                        'type': response.formResultStatus
                                    });
                                } else {
                                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                                    document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                                }
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.reject(responseObject);
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        if (response && response.cleared === true) {
                            // if cleared successfully
                            // reset table.
                            RdbAdminPermissionsController.resetDataTable();
                        }

                        if (typeof(response) !== 'undefined') {
                            if (typeof(response.formResultMessage) !== 'undefined') {
                                if (response.alertDialog === true) {
                                    RDTAAlertDialog.alert({
                                        'text': response.formResultMessage,
                                        'type': response.formResultStatus
                                    });
                                } else {
                                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                                    document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                                }
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.resolve(responseObject);
                    });
                }
            });
        }
    }// listenOnClearPermissions


    /**
     * Listen on filter permission for changed and show or hide user input.
     * 
     * @returns {undefined}
     */
    listenOnFilterPermissionForChange() {
        let filterPermissionForSelect = document.getElementById('rdba-filter-permissionFor');

        if (filterPermissionForSelect) {
            filterPermissionForSelect.addEventListener('change', function(event) {
                let filterValue = (event.target ? event.target.value : RdbaPermissions.permissionFor);
                let userIdLabelElement = document.getElementById('rdba-filter-label-permissionForUserId');

                if (filterValue === 'roles' && RdbaCommon.isset(() => userIdLabelElement.classList)) {
                    userIdLabelElement.classList.add('rd-hidden');
                } else if (filterValue === 'users' && RdbaCommon.isset(() => userIdLabelElement.classList)) {
                    userIdLabelElement.classList.remove('rd-hidden');
                }
            });
        }
    }// listenOnFilterPermissionForChange


    /**
     * Listen on filter username and auto complete for their user id.
     * 
     * @returns {undefined}
     */
    listenOnFilterPermissionUsernameAutocomplete() {
        let filterPermissionUsername = document.getElementById('rdba-filter-permissionForUserName');
        let filterPermissionUserId = document.getElementById('rdba-filter-permissionForUserId');
        let filterDataList = document.getElementById('rdba-filter-permissionForUserName-dataList');

        if (filterPermissionUserId && filterPermissionUsername) {
            // prevent enter and submit filter.
            filterPermissionUsername.addEventListener('keydown', function(event) {
                if (event.key && event.key.toLowerCase() === 'enter') {
                    event.preventDefault();
                }
            });

            // make auto complete here.
            filterPermissionUsername.addEventListener('keyup', function(event) {
                if (filterDataList) {
                    // always clear the datalist.
                    filterDataList.innerHTML = '';
                }

                if (filterPermissionUsername.value === '' || filterPermissionUsername.value.trim() === '') {
                    // if nothing in the input then do not work.
                    return ;
                }

                RdbaCommon.XHR({
                    'url': RdbaPermissions.getUsersUrl + '?search[value]=' + filterPermissionUsername.value,
                    'method': RdbaPermissions.getUsersMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'dataType': 'json'
                })
                .catch(function(responseObject) {
                    // XHR failed
                    let response = responseObject.response;
                    console.error('[rdba] ', responseObject);

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RDTAAlertDialog.alert({
                                'text': response.formResultMessage,
                                'type': response.formResultStatus
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (response && response.listItems) {
                        let options = '';
                        response.listItems.forEach(function(item, index) {
                            options += '<option data-value="' + item.user_id + '" title="' + item.user_login + ' (' + item.user_email + ')">' + item.user_login + ' (' + item.user_email + ')</option>';
                        });
                        filterDataList.innerHTML = options;
                    }

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RDTAAlertDialog.alert({
                                'text': response.formResultMessage,
                                'type': response.formResultStatus
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaPermissions.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                });
            });

            // on select from datalist, make set inputs value.
            // @link https://stackoverflow.com/a/29882539/128761 Original source code.
            filterPermissionUsername.addEventListener('input', function(event) {
                let dataListOptions = filterDataList.childNodes;
                for(let i = 0; i < dataListOptions.length; i++) {
                    let option = dataListOptions[i];
                    if (option.innerText === filterPermissionUsername.value) {
                        filterPermissionUserId.value = option.dataset.value;
                        break;
                    }
                }
            }, false);
        }
    }// listenOnFilterPermissionUsernameAutocomplete


    /**
     * Listen on filter form submit and make it ajax request.
     * 
     * @returns {undefined}
     */
    listenOnFilterSubmitAjax() {
        let filterFormElement = document.getElementById('rdba-permissions-filter-form');
        let thisClass = this;

        if (filterFormElement) {
            filterFormElement.addEventListener('submit', function(event) {
                event.preventDefault();
                let thisForm = event.target;
                let formData = new FormData(thisForm);
                let filterUrl = RdbaPermissions.getPermissionsUrl + '?' + new URLSearchParams(_.toArray(formData)).toString();

                let inputPermissionFor = document.getElementById('rdba-filter-permissionFor').value;
                let inputPermissionForUserId = document.getElementById('rdba-filter-permissionForUserId').value;
                let inputPermissionModule = document.getElementById('rdba-filter-permissionModule').value;

                // change url.
                let historyState = {
                    'pageUrl': filterUrl,
                    'permissionFor': inputPermissionFor,
                    'permissionForUserId': inputPermissionForUserId,
                    'permissionModule': inputPermissionModule
                };
                window.history.pushState(historyState, '', filterUrl);

                // set the JS object properties.
                RdbaPermissions.permissionFor = inputPermissionFor;
                RdbaPermissions.permissionForUserId = inputPermissionForUserId;
                RdbaPermissions.permissionModule = inputPermissionModule;

                // reset data table, validate filters, then ajax get data and render data table.
                RdbAdminPermissionsController.resetDataTable();
            });
        }

        let historyState = {
            'pageUrl': RdbaPermissions.getPermissionsUrl,
            'permissionFor': RdbaPermissions.permissionFor,
            'permissionForUserId': RdbaPermissions.permissionForUserId,
            'permissionModule': RdbaPermissions.permissionModule
        };
        window.history.replaceState(historyState, document.title, document.location.href);
    }// listenOnFilterSubmitAjax


    /**
     * Listen on popstate filter and make ajax request.
     * 
     * @returns {undefined}
     */
    listenOnPopstateFilterAjax() {
        let thisClass = this;

        window.onpopstate = function(event) {
            let useReload = true;

            if (event && event.state) {
                // if contain state, it was ajax request before.
                if (event.state.permissionFor && event.state.permissionForUserId !== null && event.state.permissionModule) {
                    useReload = false;

                    // set the JS object properties.
                    RdbaPermissions.permissionFor = event.state.permissionFor;
                    RdbaPermissions.permissionForUserId = event.state.permissionForUserId;
                    RdbaPermissions.permissionModule = event.state.permissionModule;
                }
            } else {
                // if contain no state, it was basic page load.
                // do nothing here.
            }

            if (useReload === false) {
                // if use ajax get data.
                //console.log('[rdba] contain state and required objects, use ajax get and render data table.');
                // reset data table, validate filters, then ajax get data and render data table.
                RdbAdminPermissionsController.resetDataTable();
            } else {
                // if use reload.
                //console.log('[rdba] not contain state and required objects, use reload.');
                window.location.reload();
            }
        };
    }// listenOnPopstateFilterAjax


    /**
     * Reset data table, validate filters, then ajax get data and render data table.
     * 
     * Call from HTML button.<br>
     * Example: <pre>
     * &lt;button onclick=&quot;return RdbAdminPermissionsController.resetDataTable();&quot;&gt;Reset&lt;/button&gt;
     * </pre>
     * 
     * This method was called from `listenOnClearPermissions()`, `listenOnFilterSubmitAjax()` methods.
     * 
     * @returns {false}
     */
    static resetDataTable() {
        let thisClass = new this();

        // reset form
        document.getElementById('rdba-filter-permissionModule').innerHTML = '';
        document.getElementById('rdba-filter-permissionModule').value = '';
        document.getElementById('rdba-filter-permissionFor').value = '';
        document.getElementById('rdba-filter-permissionForUserId').value = '';
        document.getElementById('rdba-filter-permissionForUserName').value = '';

        // reset table element.
        let dataTableElement = document.querySelector('#permissionsTable');
        if (dataTableElement) {
            dataTableElement.querySelector('thead > tr').innerHTML = '';
            dataTableElement.querySelector('tfoot > tr').innerHTML = '';
            dataTableElement.querySelector('tbody').innerHTML = '';
        }

        // make sure filters display correctly before gets render.
        thisClass.validateDatatableFilters();

        // ajax get data and render.
        thisClass.ajaxGetDataAndRender();

        return false;
    }// resetDataTable


    /**
     * Validate, correct filters value, correctly show or hide filters.
     * 
     * This method must be called before `ajaxGetDataAndRender()` because this will correct all the filters before it gets render.<br>
     * This method was called from `resetDataTable()`, `init()`, `listenOnFilterSubmitAjax()` methods.
     * 
     * @returns {undefined}
     */
    validateDatatableFilters() {
        document.getElementById('rdba-filter-permissionFor').value = RdbaPermissions.permissionFor;

        let userIdLabelElement = document.getElementById('rdba-filter-label-permissionForUserId');
        if (RdbaPermissions.permissionFor === 'users') {
            if (RdbaCommon.isset(() => userIdLabelElement.classList)) {
                userIdLabelElement.classList.remove('rd-hidden');
            }
        } else {
            if (RdbaCommon.isset(() => userIdLabelElement.classList)) {
                userIdLabelElement.classList.add('rd-hidden');
            }
        }
        //document.getElementById('rdba-filter-permissionForUserId').value = RdbaPermissions.permissionForUserId;// no need because no reset button.
    }// validateDatatableFilters


}// RdbAdminPermissionsController


document.addEventListener('DOMContentLoaded', function() {
    let rdbAdminPermissionsController = new RdbAdminPermissionsController();

    // init the class.
    rdbAdminPermissionsController.init();
}, false);