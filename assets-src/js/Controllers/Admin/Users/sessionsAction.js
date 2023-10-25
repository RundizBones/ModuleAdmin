/**
 * User login sessions list JS for its controller.
 */


class RdtaSessionsController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     * @returns {RdbaUsersController}
     */
    constructor(options) {
        super(options);// call parent constructor.

        this.formIDSelector = '#rdba-loginsessions-form';
        this.datatableIDSelector = '#userLoginsTable';
        this.defaultSortOrder = [[4, 'desc']];
    }// constructor


    /**
     * Ajax delete logins sessions.
     * 
     * @private This method was called from `listenFormSubmit()`.
     * @param {string} data
     * @returns {undefined}
     */
    ajaxDeleteSessions(data) {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let selectAction = document.querySelector('#rdba-user-actions');
        let confirmMessage = '';
        if (selectAction.value === 'empty') {
            confirmMessage = RdbaUserSessions.txtAreYouSureEmpty;
        } else {
            confirmMessage = RdbaUserSessions.txtAreYouSureDelete;
        }
        let confirmResult = confirm(confirmMessage);

        if (confirmResult === true) {
            // if confirmed.
            // reset form result placeholder
            thisForm.querySelector('.form-result-placeholder').innerHTML = '';
            // add spinner icon
            thisForm.querySelector('.rdba-submit-button').insertAdjacentHTML('beforeend', ' <i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

            let deleteUrl = RdbaUserSessions.deleteLoginsSubmitUrl;
            if (deleteUrl) {
                deleteUrl = deleteUrl.replace('{{user_id}}', RdbaUserSessions.userId);
            }

            RdbaCommon.XHR({
                'url': deleteUrl,
                'method': RdbaUserSessions.deleteLoginsMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'data': data
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
                    RdbaUserSessions.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .then(function(responseObject) {
                // XHR success.
                let response = responseObject.response;

                // reload datatables.
                if (response && response.deleted === true) {
                    jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
                }

                if (typeof(response) !== 'undefined') {
                    if (typeof(response.formResultMessage) !== 'undefined') {
                        let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                        let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                        thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                    }
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaUserSessions.csrfKeyPair = response.csrfKeyPair;
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
    }// ajaxDeleteSessions


    /**
     * Initialize datatables.
     * 
     * @returns {undefined}
     */
    init() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

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

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            $.fn.dataTable.ext.errMode = 'throw';
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbaUserSessions.viewLoginsUrl.replace('{{user_id}}', RdbaUserSessions.userId),
                    'method': RdbaUserSessions.viewLoginsMethod,
                    'dataSrc': 'listItems',// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                    'data': function(data) {
                        data.filterResult = $('#rdba-filter-result').val();
                    }
                },
                'autoWidth': false,// don't set style="width: xxx;" in the table cell.
                'columnDefs': [
                    {
                        'orderable': false,// make checkbox column not sortable.
                        'searchable': false,// make checkbox column can't search.
                        'targets': [0, 1]
                    },
                    {
                        'className': 'dtr-control',
                        'data': 'userlogin_id',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'userlogin_id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            return '<input type="checkbox" name="userlogin_id[]" value="' + row.userlogin_id + '">';
                        }
                    },
                    {
                        'data': 'userlogin_ua',
                        'targets': 2,
                        'render': function(data, type, row, meta) {
                            let output = '<span title="' + RdbaCommon.escapeHtml(row.userlogin_ua) + '">' + row.userlogin_ua + '</span>';
                            if (row.userlogin_session_key === RdbaUIXhrCommonData.userData.userlogin_session_key) {
                                output += '<br>';
                                output += '<small><em>' + RdbaUserSessions.txtCurrentSession + '</em></small>';
                            }
                            return output;
                        }
                    },
                    {
                        'data': 'userlogin_ip',
                        'targets': 3
                    },
                    {
                        'data': 'userlogin_date_gmt',
                        'targets': 4,
                        'render': function(data, type, row, meta) {
                            if (row.userlogin_date_gmt) {
                                return moment(row.userlogin_date_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
                            } else {
                                return '';
                            }
                        }
                    },
                    {
                        'data': 'userlogin_result',
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            if (row.userlogin_result) {
                                if (row.userlogin_result === '1') {
                                    return '<i class="fa-regular fa-circle-check" aria-label="'+RdbaCommon.escapeHtml(RdbaUserSessions.txtSucceeded)+'"></i> <span class="screen-reader-only">'+RdbaUserSessions.txtSucceeded+'</span>';
                                } else {
                                    var output = '<i class="fa-solid fa-ban" aria-label="'+RdbaCommon.escapeHtml(RdbaUserSessions.txtFailed)+'"></i> <span class="screen-reader-only">'+RdbaUserSessions.txtFailed+'</span>';
                                    if (row.userlogin_result_text_withdata) {
                                        output += ' <span title="' + RdbaCommon.escapeHtml(row.userlogin_result_text_withdata) + '">' + RdbaCommon.truncateString(row.userlogin_result_text_withdata, 50) + '</span>';
                                    }
                                    return output;
                                }
                            } else {
                                return '<i class="fa-regular fa-circle-question" aria-label="'+RdbaCommon.escapeHtml(RdbaUserSessions.txtUnknow)+'"></i> <span class="screen-reader-only">'+RdbaUserSessions.txtUnknow+'</span>';
                            }
                        }
                    },
                ],
                'createdRow': function(row, data, dataIndex) {
                    if (data.userlogin_session_key === RdbaUIXhrCommonData.userData.userlogin_session_key) {
                        $(row).addClass('table-row-info');
                    }
                },
                'dom': thisClass.datatablesDOM,
                'fixedHeader': true,
                'language': datatablesTranslation,// datatablesTranslation is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
                'order': thisClass.defaultSortOrder,
                'pageLength': parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage),
                'pagingType': 'input',
                'processing': true,
                'responsive': {
                    'details': {
                        'type': 'column',
                        'target': 0
                    }
                },
                'searchDelay': 1300,
                'serverSide': true,
                // state save ( https://datatables.net/reference/option/stateSave ).
                // to use state save, any custom filter should use `stateLoadCallback` and set input value.
                // maybe use keepconditions ( https://github.com/jhyland87/DataTables-Keep-Conditions ).
                'stateSave': false
            });//.DataTable()

            // datatables events
            dataTable.on('xhr.dt', function(e, settings, json, xhr) {
                if (addedCustomResultControls === false) {
                    // add filter, search controls.
                    thisClass.addCustomResultControls(json);
                    // add bulk actions controls.
                    thisClass.addActionsControls(json);
                    addedCustomResultControls = true;
                }
                // add pagination.
                thisClass.addCustomResultControlsPagination(json);

                if (json && json.formResultMessage) {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(json.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, json.formResultMessage);
                    document.querySelector(thisClass.formIDSelector + ' .form-result-placeholder').innerHTML = alertBox;
                }

                // everytime getting data, the server side always generate new token because of the same method. set the new one into object.
                if (json && typeof(json) !== 'undefined' && typeof(json.csrfKeyPair) !== 'undefined') {
                    RdbaUserSessions.csrfKeyPair = json.csrfKeyPair;
                }

                if (json && json.user) {
                    thisClass.setUserData(json.user);
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
            })// datatables on draw complete.
            ;
        });// uiXhrCommonData.done()
    }// init


    /**
     * Listen on bulk action form submit.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-loginsessions-form') {
                event.preventDefault();
                // form validation.
                let formValidated = false;
                let selectAction = event.target.querySelector('#rdba-user-actions');
                // validate selected login session.
                let userLoginIdArray = [];
                event.target.querySelectorAll('input[type="checkbox"][name="userlogin_id[]"]:checked').forEach(function(item, index) {
                    userLoginIdArray.push(item.value);
                });
                if (userLoginIdArray.length <= 0 && selectAction.value !== 'empty') {
                    RDTAAlertDialog.alert({
                        'text': RdbaUserSessions.txtPleaseSelectAtLeastOneSession,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbaUserSessions.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    let formData = new FormData();
                    formData.append('userlogin_ids', userLoginIdArray.join(','));
                    formData.append('action', selectAction.value);
                    formData.append(RdbaUserSessions.csrfName, RdbaUserSessions.csrfKeyPair[RdbaUserSessions.csrfName]);
                    formData.append(RdbaUserSessions.csrfValue, RdbaUserSessions.csrfKeyPair[RdbaUserSessions.csrfValue]);
                    formData = new URLSearchParams(formData).toString();
                    thisClass.ajaxDeleteSessions(formData);
                }
            }
        });
    }// listenFormSubmit


    /**
     * Reset data tables.
     * 
     * Call from HTML button.<br>
     * Example: <pre>
     * &lt;button onclick=&quot;return RdtaSessionsController.resetDataTable();&quot;&gt;Reset&lt;/button&gt;
     * </pre>
     * 
     * @returns {false}
     */
    static resetDataTable() {
        let $ = jQuery.noConflict();
        let thisClass = new this();

        // reset form
        document.getElementById('rdba-filter-result').value = '';
        document.getElementById('rdba-filter-search').value = '';

        // datatables have to call with jQuery.
        $(thisClass.datatableIDSelector).DataTable().order(thisClass.defaultSortOrder).search('').draw();// .order must match in columnDefs.

        return false;
    }// resetDataTable


    /**
     * Set user data to display in HTML.
     * 
     * @param {object} user
     * @returns {undefined}
     */
    setUserData(user) {
        let userLoginElement = document.getElementById('user_login');
        userLoginElement.innerHTML = user.user_login;
    }// setUserData


}// RdtaSessionsController


document.addEventListener('DOMContentLoaded', function() {
    let rdtaSessionsController = new RdtaSessionsController();

    // initialize datatables.
    rdtaSessionsController.init();

    // listen bulk action form submit.
    rdtaSessionsController.listenFormSubmit();
}, false);