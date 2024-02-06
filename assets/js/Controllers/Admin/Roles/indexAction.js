/**
 * Roles management JS for its controller.
 */


class RdbaRolesController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     * @returns {RdbaRolesController}
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdba-roles-form';
        this.datatableIDSelector = '#rolesTable';
        this.defaultSortOrder = [];
    }// constructor


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
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbaRoles.getRolesUrl,
                    'method': RdbaRoles.getRolesMethod,
                    'dataSrc': 'listItems'// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
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
                        'data': 'userrole_id',
                        'targets': 0,
                        'render': function (data, type, row, meta) {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'userrole_id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            return '<input type="checkbox" name="userrole_id[]" value="' + row.userrole_id + '">';
                        }
                    },
                    {
                        'data': 'userrole_id',
                        'orderable': false,
                        'targets': 2,
                        'visible': false,
                    },
                    {
                        'data': 'userrole_name',
                        'orderable': false,
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            row.RdbaRoles = RdbaRoles;
                            let sortableIcon = '';
                            if (row && row.restrictedPriority === true) {
                                sortableIcon = '<span class="fa-stack fa-xs fa-fw sortable-icon sortable-handle">\n\
                                    <i class="fa-solid fa-up-down fa-stack-1x"></i>\n\
                                    <i class="fa-solid fa-ban fa-stack-2x"></i>\n\
                                </span>';
                            } else {
                                sortableIcon = '<i class="fa-solid fa-up-down fa-fw sortable-icon sortable-handle"></i> ';
                            }
                            let html = sortableIcon 
                                + '<a class="rdba-listpage-edit" href="' + RdbaRoles.editRolePageUrlBase + '/' + row.userrole_id + '">' + data + '</a>'
                                + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'userrole_description',
                        'orderable': false,
                        'targets': 4,
                        'render': function(data, type, row, meta) {
                            if (data !== null) {
                                let html = '<div title="' + data + '">';
                                html += RdbaCommon.truncateString(data, 40);
                                html += '</div>';
                                return html;
                            } else {
                                return '';
                            }
                        }
                    },
                    {
                        'data': 'userrole_create',
                        'orderable': false,
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            if (row.userrole_create_gmt) {
                                return moment(row.userrole_create_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
                            } else {
                                return '';
                            }
                        }
                    },
                    {
                        'data': 'userrole_lastupdate',
                        'orderable': false,
                        'targets': 6,
                        'render': function(data, type, row, meta) {
                            if (row.userrole_lastupdate_gmt) {
                                return moment(row.userrole_lastupdate_gmt + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
                            } else {
                                return '';
                            }
                        }
                    },
                    {
                        'data': 'userrole_priority',
                        'orderable': false,
                        'targets': 7
                    }
                ],
                'createdRow': function(row, data, dataIndex, cells) {
                    $(row).attr('data-userrole_id', data.userrole_id);
                    $(row).attr('data-userrole_priority', data.userrole_priority);

                    if (data && data.restrictedPriority === true) {
                        // if super admin or guest.
                        // do not allow reorder.
                        $(row).addClass('disallow-sortable');
                    } else {
                        // add class to allow re-order.
                        $(row).addClass('allow-sortable');
                    }
                },
                'dom': thisClass.datatablesDOM,
                'fixedHeader': true,
                'language': datatablesTranslation,// datatablesTranslation is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
                'order': thisClass.defaultSortOrder,
                'pageLength': parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage),
                'paging': false,// disable paging.
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

                if (typeof(json) !== 'undefined' && typeof(json.csrfKeyPair) !== 'undefined') {
                    RdbaRoles.csrfKeyPair = json.csrfKeyPair;
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
                // make data table sortable (re-order able).
                thisClass.makeTableSortable();
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
            if (event.target && event.target.id === 'rdba-roles-form') {
                event.preventDefault();
                // validate selected user.
                let formValidated = false;
                let userRoleIdsArray = [];
                event.target.querySelectorAll('input[type="checkbox"][name="userrole_id[]"]:checked').forEach(function(item, index) {
                    userRoleIdsArray.push(item.value);
                });
                if (userRoleIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbaRoles.txtPleaseSelectAtLeastOneRole,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = event.target.querySelector('#rdba-role-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbaRoles.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    let ajaxUrl = RdbaRoles.actionsRolesUrl + '?userrole_ids=' + userRoleIdsArray.join(',') + '&action=' + selectAction.value;
                    thisClass.RdbaXhrDialog.ajaxOpenLinkInDialog(ajaxUrl);
                }
            }
        });
    }// listenFormSubmit


    /**
     * Make datatables sortable (re-order able).
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    makeTableSortable() {
        let thisClass = this;
        let tableBody = document.querySelector(this.datatableIDSelector + ' tbody');
        let sortable = new Sortable(tableBody, {
            'animation': 150,// fix stuck transform translateZ after drag few times.
            'dataIdAttr': 'data-userrole_id',// data use in store property.
            'draggable': '.allow-sortable',// tr that contain this selector will be able to drag.
            'filter': '.disallow-sortable',// tr that contain this selector will not be able to drag.
            'handle': '.sortable-handle',// sortable handle.
            'preventOnFilter': false,// set to `false` and text will be selectable on filtered row.
            'onEnd': function(event) {
                if (event.item && event.item.style) {
                    event.item.style = '';// remove stuck transform translateZ for sure.
                }
            },
            'store': {
                'set': function(sortable) {
                    let $ = jQuery.noConflict();

                    let sortableArray = sortable.toArray();
                    if (sortableArray && sortableArray.length <= 1) {
                        // if only one item moved, no need to update.
                        return ;
                    }

                    let formData = new FormData();
                    formData.append('updateData', JSON.stringify(sortable.toArray()));
                    formData.append(RdbaRoles.csrfName, RdbaRoles.csrfKeyPair[RdbaRoles.csrfName]);
                    formData.append(RdbaRoles.csrfValue, RdbaRoles.csrfKeyPair[RdbaRoles.csrfValue]);
                    formData = new URLSearchParams(formData).toString();

                    // reset form result placeholder.
                    $('.form-result-placeholder').html('');

                    // ajax save
                    $.ajax({
                        'url': RdbaRoles.reorderSubmitUrl,
                        'method': RdbaRoles.reorderMethod,
                        'data': formData,
                        'dataType': 'json'
                    })
                    .done(function(data, textStatus, jqXHR) {
                        let response = data;

                        if (response.updated === true) {
                            jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
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
                                $('.form-result-placeholder').html(alertBox);
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaRoles.csrfKeyPair = response.csrfKeyPair;
                        }
                    });
                }
            }
        });
    }// makeTableSortable


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
        document.getElementById('rdba-filter-search').value = '';

        // datatables have to call with jQuery.
        $(thisClass.datatableIDSelector).DataTable().order(thisClass.defaultSortOrder).search('').draw();// .order must match in columnDefs.

        return false;
    }// resetDataTable


}// RdbaRolesController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaRolesController = new RdbaRolesController();
    let rdbaXhrDialog = new RdbaXhrDialog({
        'dialogIDSelector': '#rdba-roles-dialog',
        'dialogNewInitEvent': 'rdba.roles.editing.newinit',
        'dialogReInitEvent': 'rdba.roles.editing.reinit',
        'xhrLinksSelector': '.rdba-listpage-addnew, .rdba-listpage-edit'
    });
    rdbaRolesController.setRdbaXhrDialogObject(rdbaXhrDialog);

    // initialize datatables.
    rdbaRolesController.init();
    // set of methods to work on click add, edit and open as dialog instead of new page. -----------------
    // links to be ajax.
    rdbaXhrDialog.listenAjaxLinks();
    // listen on closed dialog and maybe change URL.
    rdbaXhrDialog.listenDialogClose();
    // listen on popstate and controls dialog.
    rdbaXhrDialog.listenPopStateControlsDialog();
    // end set of methods to open page as dialog. --------------------------------------------------------------

    // listen form submit (bulk actions).
    rdbaRolesController.listenFormSubmit();
}, false);