/**
 * Users list (users management) JS for its controller.
 */


class RdbaUsersController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     * @returns {RdbaUsersController}
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdba-users-form';
        this.datatableIDSelector = '#usersTable';
        this.defaultSortOrder = [[2, 'desc']];
    }// constructor


    /**
     * Initialize the class.
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
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbaUsers.getUsersUrl,
                    'method': RdbaUsers.getUsersUrlMethod,
                    'dataSrc': 'listItems',// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                    'data': function(data) {
                        data.filterRole = $('#rdba-filter-roles').val();
                        data.filterStatus = $('#rdba-filter-status').val();
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
                        'data': 'user_id',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'user_id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            return '<input type="checkbox" name="user_id[]" value="' + row.user_id + '">';
                        }
                    },
                    {
                        'data': 'user_id',
                        'targets': 2,
                        'visible': false,
                    },
                    {
                        'data': 'user_login',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            // @link https://stackoverflow.com/questions/42245693/handlebars-js-replacing-portion-of-string replace original source code.
                            Handlebars.registerHelper('replace', function (find, replace, options) {
                                var string = options.fn(this);
                                return string.replace(find, replace);
                            });
                            row.RdbaUsers = RdbaUsers;
                            let html = data + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'user_display_name',
                        'targets': 4
                    },
                    {
                        'data': 'user_email',
                        'targets': 5
                    },
                    {
                        'data': 'users_roles',
                        'orderable': false,
                        'targets': 6,
                        'render': function(data, type, row, meta) {
                            if (typeof(row.users_roles) === 'object') {
                                var output = '';
                                var length = row.users_roles.length;
                                $.each(row.users_roles, function(index, item) {
                                    output += item.userrole_name;
                                    if (index < (length - 1)) {
                                        output += ', ';
                                    }
                                });
                                return output;
                            }
                            return '';
                        }
                    },
                    {
                        'data': 'user_status',
                        'targets': 7,
                        'render': function(data, type, row, meta) {
                            if (typeof(row.user_status) !== 'undefined') {
                                if (row.user_status === '1') {
                                    return '<i class="fa-regular fa-circle-check" aria-label="'+RdbaCommon.escapeHtml(RdbaUsers.txtEnabled)+'"></i> <span class="screen-reader-only">'+RdbaUsers.txtEnabled+'</span>';
                                } else {
                                    var output = '<i class="fa-solid fa-ban" aria-label="'+RdbaCommon.escapeHtml(RdbaUsers.txtDisabled)+'"></i> <span class="screen-reader-only">'+RdbaUsers.txtDisabled+'</span>';
                                    if (typeof(row.user_statustext) !== 'undefined' && row.user_statustext !== null) {
                                        output += ' <span title="' + RdbaCommon.escapeHtml(row.user_statustext) + '">' + RdbaCommon.truncateString(row.user_statustext, 20) + '</span>';
                                    }
                                    return output;
                                }
                            } else {
                                return '<i class="fa-regular fa-circle-question" aria-label="'+RdbaCommon.escapeHtml(RdbaUsers.txtUnknow)+'"></i> <span class="screen-reader-only">'+RdbaUsers.txtUnknow+'</span>';
                            }
                        }
                    },
                    {
                        'data': 'user_lastlogin',
                        'targets': 8,
                        'render': function(data, type, row, meta) {
                            if (row.user_lastlogin_gmt) {
                                return moment(row.user_lastlogin_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
                            } else {
                                return '';
                            }
                        }
                    }
                ],
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
                    // if it was not added custom result controls yet.
                    // set additional data.
                    json.RdbaUsers = RdbaUsers;
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

                if (typeof(json) !== 'undefined' && typeof(json.csrfKeyPair) !== 'undefined') {
                    RdbaUsers.csrfKeyPair = json.csrfKeyPair;
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
     * Listen on bulk action form submit and open as ajax inside dialog.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdba-users-form') {
                event.preventDefault();
                // validate selected user.
                let formValidated = false;
                let userIdsArray = [];
                event.target.querySelectorAll('input[type="checkbox"][name="user_id[]"]:checked').forEach(function(item, index) {
                    userIdsArray.push(item.value);
                });
                if (userIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbaUsers.txtPleaseSelectAtLeastOneUser,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = event.target.querySelector('#rdba-user-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbaUsers.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    let ajaxUrl = RdbaUsers.actionsUsersUrl + '?user_ids=' + userIdsArray.join(',') + '&action=' + selectAction.value;
                    thisClass.RdbaXhrDialog.ajaxOpenLinkInDialog(ajaxUrl);
                }
            }
        });
    }// listenFormSubmit


    /**
     * Reset data tables.
     * 
     * Call from HTML button.<br>
     * Example: <pre>
     * &lt;button onclick=&quot;return RdbaUsersController.resetDataTable();&quot;&gt;Reset&lt;/button&gt;
     * </pre>
     * 
     * @returns {false}
     */
    static resetDataTable() {
        let $ = jQuery.noConflict();
        let thisClass = new this();

        // reset form
        document.getElementById('rdba-filter-status').value = '';
        document.getElementById('rdba-filter-roles').value = '';
        document.getElementById('rdba-filter-search').value = '';

        // datatables have to call with jQuery.
        $(thisClass.datatableIDSelector).DataTable().order(thisClass.defaultSortOrder).search('').draw();// .order must match in columnDefs.

        return false;
    }// resetDataTable


}// RdbaUsersController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaUsersController = new RdbaUsersController();
    let rdbaXhrDialog = new RdbaXhrDialog({
        'dialogIDSelector': '#rdba-users-dialog',
        'dialogNewInitEvent': 'rdba.users.editing.newinit',
        'dialogReInitEvent': 'rdba.users.editing.reinit',
        'xhrLinksSelector': '.rdba-listpage-addnew, .rdba-listpage-edit, .url-edit-your-account'
    });
    rdbaUsersController.setRdbaXhrDialogObject(rdbaXhrDialog);

    // initialize datatables.
    rdbaUsersController.init();

    // set of methods to work on click add, edit and open as dialog instead of new page. -----------------
    // links to be ajax.
    rdbaXhrDialog.listenAjaxLinks();
    // listen on closed dialog and maybe change URL.
    rdbaXhrDialog.listenDialogClose();
    // listen on popstate and controls dialog.
    rdbaXhrDialog.listenPopStateControlsDialog();
    // end set of methods to open page as dialog. --------------------------------------------------------------

    // listen form submit (bulk actions).
    rdbaUsersController.listenFormSubmit();
}, false);