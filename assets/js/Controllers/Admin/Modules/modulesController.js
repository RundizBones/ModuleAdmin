/**
 * Modules list JS for its controller.
 * 
 * @since 1.2.5
 */


class RdbaModulesController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdba-modules-form';
        this.datatableIDSelector = '#modulesTable';
        this.defaultSortOrder = [];
    }// constructor


    /**
     * Initialize datatables.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    #initDataTables() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbaModulesObject.urls.getModulesRESTUrl,
                    'method': RdbaModulesObject.urls.getModulesRESTMethod,
                    'dataSrc': function(data) {
                        RdbaModulesObject.currentModule = data.currentModule;
                        return data.listItems;
                    }// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                },
                'autoWidth': false,// don't set style="width: xxx;" in the table cell.
                'columnDefs': [
                    {
                        'orderable': false,// make columns not sortable.
                        'searchable': false,// make columns can't search.
                        'targets': [0, 1, 2, 3, 4, 5]
                    },
                    {
                        'className': 'dtr-control',
                        'data': 'id',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            let html = '<input id="module-checkbox-' + row.id + '" type="checkbox" name="module_id[]" value="' + row.id + '"';
                            html += ' data-module-system-name="' + row.module_system_name + '"';
                            html += ' data-module-location="' + row.module_location + '"';
                            if (row.module_system_name === RdbaModulesObject.currentModule) {
                                html += ' disabled="disabled" data-is-current-module="true"';
                            }
                            html += '>';
                            return html;
                        }
                    },
                    {
                        'data': 'id',
                        'targets': 2,
                        'visible': false,
                    },
                    {
                        'data': 'module_name',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            row.RdbaModulesObject = RdbaModulesObject;
                            let module_name = RdbaCommon.escapeHtml(data);
                            if (row.module_enabled === false) {
                                module_name = '<i class="fa-solid fa-ban"></i> ' + module_name;
                            }
                            let html = '<label for="module-checkbox-' + row.id + '">' + module_name + '</label>' + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'module_location',
                        'targets': 4
                    },
                    {
                        'data': 'module_description',
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-desctiption-secondary').innerHTML;
                            let template = Handlebars.compile(source);
                            row.RdbaModulesObject = RdbaModulesObject;
                            let html = ''

                            if (row.module_description && row.module_description !== null) {
                                html = RdbaCommon.escapeHtml(row.module_description);
                            }
                            html += template(row);
                            return html;
                        }
                    },
                ],
                'createdRow': function(row, data, dataIndex) {
                    if (data.module_enabled === false) {
                        $(row).addClass('module-disabled');
                    }
                },
                'dom': thisClass.datatablesDOM,
                'fixedHeader': true,
                'language': datatablesTranslation,// datatablesTranslation is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
                'order': thisClass.defaultSortOrder,
                'pageLength': parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage),
                'paging': false,// disable pagination.
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
                    json.RdbaModulesObject = RdbaModulesObject;
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
                    RdbaModulesObject.csrfKeyPair = json.csrfKeyPair;
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
            })// datatables on draw complete.
            ;
        });// uiXhrCommonData.done()
    }// #initDataTables


    /**
     * Listen on bulk action form submit and open as ajax.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    #listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (event.target && '#'+event.target.id === thisClass.formIDSelector) {
                event.preventDefault();

                let thisForm = event.target;

                // validate selected items.
                let formValidated = false;
                let modulesIdArray = [];
                let modulesLocation = [];
                let moduleSystemNameArray = [];
                event.target.querySelectorAll('input[type="checkbox"][name="module_id[]"]:checked').forEach(function(item, index) {
                    if (item.disabled === true) {
                        return ;
                    }
                    modulesIdArray.push(item.value);
                    modulesLocation.push(item.dataset.moduleLocation);
                    moduleSystemNameArray.push(item.dataset.moduleSystemName);
                });
                if (modulesIdArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbaModulesObject.txtPleaseSelectAtLeastOneModule,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = event.target.querySelector('#rdba-module-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbaModulesObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else if (selectAction?.value === 'update') {
                        // if selected action is running update (the same as command `php rdb system:module update --mname=xxx`).
                        // this action is since v 1.2.10
                        if (modulesIdArray.length > 1) {
                            RDTAAlertDialog.alert({
                                'text': RdbaModulesObject.txtPleaseSelectOneModule,
                                'type': 'error'
                            });
                            formValidated = false;
                        }

                        if (formValidated === true) {
                            const confirmVal = confirm(RdbaModulesObject.txtConfirmUpdateModule);
                            if (confirmVal === true) {
                                formValidated = true;
                            } else {
                                formValidated = false;
                            }
                        }
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    // if form validated.
                    // start processing the form.
                    // lock button
                    thisForm.querySelector('#rdba-module-action-button').setAttribute('disabled', 'disabled');

                    let formData = new FormData();
                    formData.append(RdbaModulesObject.csrfName, RdbaModulesObject.csrfKeyPair[RdbaModulesObject.csrfName]);
                    formData.append(RdbaModulesObject.csrfValue, RdbaModulesObject.csrfKeyPair[RdbaModulesObject.csrfValue]);
                    formData.append('module_ids', modulesIdArray.join(','));
                    formData.append('modules_location', modulesLocation.join(','));
                    formData.append('modulesystemnames', moduleSystemNameArray.join(','));
                    formData.append('action', selectAction.value);

                    RdbaCommon.XHR({
                        'url': RdbaModulesObject.urls.actionModulesRESTUrl,
                        'method': RdbaModulesObject.urls.actionModulesRESTMethod,
                        'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'data': new URLSearchParams(_.toArray(formData)).toString(),
                        'dataType': 'json',
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        if (typeof(response) !== 'undefined') {
                            if (typeof(response.formResultMessage) !== 'undefined') {
                                let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                                let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                                document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaModulesObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        if (response.updated === true) {
                            jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
                        }

                        return Promise.resolve(responseObject);
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
                            RdbaModulesObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.reject(responseObject);
                    })
                    .finally(function() {
                        // unlock submit button
                        thisForm.querySelector('#rdba-module-action-button').removeAttribute('disabled');
                    });
                }
            }
        });
    }// #listenFormSubmit


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // initialize datatables.
        this.#initDataTables();
        // listen on bulk action form submit and open as ajax.
        this.#listenFormSubmit();
    }// init


}


document.addEventListener('DOMContentLoaded', function() {
    let rdbaModulesController = new RdbaModulesController();

    // init the class.
    rdbaModulesController.init();
}, false);