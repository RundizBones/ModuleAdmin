/**
 * Plugins list JS for its controller.
 */


class RdbaPluginsController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     * @returns {RdbaPluginsController}
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdba-modulesplugins-form';
        this.datatableIDSelector = '#modulePluginsTable';
        this.defaultSortOrder = [];
    }// constructor


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // initialize datatables.
        this.initDataTables();
        // listen on bulk action form submit and open as ajax.
        this.listenFormSubmit();
    }// init


    /**
     * Initialize datatables.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    initDataTables() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbaModulesPlugins.getPluginsRESTUrl,
                    'method': RdbaModulesPlugins.getPluginsRESTMethod,
                    'dataSrc': 'listItems'// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
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
                            return '<input type="checkbox" name="plugin_id[]" value="' + row.id + '">';
                        }
                    },
                    {
                        'data': 'id',
                        'targets': 2,
                        'visible': false,
                    },
                    {
                        'data': 'plugin_name',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            row.RdbaModulesPlugins = RdbaModulesPlugins;
                            let plugin_name = RdbaCommon.escapeHtml(data);
                            if (row.enabled === false) {
                                plugin_name = '<i class="fa-solid fa-ban"></i> ' + plugin_name;
                            }
                            let html = plugin_name + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'plugin_location',
                        'targets': 4
                    },
                    {
                        'data': 'plugin_description',
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            if (row.plugin_description && row.plugin_description !== null) {
                                return RdbaCommon.escapeHtml(row.plugin_description);
                            }
                            return '';
                        }
                    },
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
                    json.RdbaModulesPlugins = RdbaModulesPlugins;
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
                    RdbaModulesPlugins.csrfKeyPair = json.csrfKeyPair;
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
            })// datatables on draw complete.
            ;
        });// uiXhrCommonData.done()
    }// initDataTables


    /**
     * Listen on bulk action form submit and open as ajax.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (event.target && '#'+event.target.id === thisClass.formIDSelector) {
                event.preventDefault();

                let thisForm = event.target;

                // validate selected items.
                let formValidated = false;
                let pluginsIdArray = [];
                event.target.querySelectorAll('input[type="checkbox"][name="plugin_id[]"]:checked').forEach(function(item, index) {
                    pluginsIdArray.push(item.value);
                });
                if (pluginsIdArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbaModulesPlugins.txtPleaseSelectAtLeastOnePlugin,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = event.target.querySelector('#rdba-moduleplugin-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbaModulesPlugins.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    // lock button
                    thisForm.querySelector('#rdba-plugin-action-button').setAttribute('disabled', 'disabled');

                    let formData = new FormData();
                    formData.append(RdbaModulesPlugins.csrfName, RdbaModulesPlugins.csrfKeyPair[RdbaModulesPlugins.csrfName]);
                    formData.append(RdbaModulesPlugins.csrfValue, RdbaModulesPlugins.csrfKeyPair[RdbaModulesPlugins.csrfValue]);
                    formData.append('plugin_ids', pluginsIdArray.join(','));
                    formData.append('action', selectAction.value);

                    RdbaCommon.XHR({
                        'url': RdbaModulesPlugins.actionPluginsRESTUrl,
                        'method': RdbaModulesPlugins.actionPluginsRESTMethod,
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
                            RdbaModulesPlugins.csrfKeyPair = response.csrfKeyPair;
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
                                document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaModulesPlugins.csrfKeyPair = response.csrfKeyPair;
                        }

                        if (response.updated === true) {
                            jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
                        }

                        return Promise.resolve(responseObject);
                    })
                    .finally(function() {
                        // unlock submit button
                        thisForm.querySelector('#rdba-plugin-action-button').removeAttribute('disabled');
                    });
                }
            }
        });
    }// listenFormSubmit


}


document.addEventListener('DOMContentLoaded', function() {
    let rdbaPluginsController = new RdbaPluginsController();

    // init the class.
    rdbaPluginsController.init();
}, false);