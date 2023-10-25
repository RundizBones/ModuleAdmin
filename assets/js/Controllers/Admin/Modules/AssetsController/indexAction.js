/**
 * Module's assets JS for its controller.
 * 
 * @since 1.1.8
 */


class RdbaAssetsController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);

        this.bulkActionsIDSelector = '#rdba-modulesassets-actions';
        this.buttonActionIDSelector = '#rdba-modulesassets-action-button';
        this.formIDSelector = '#rdba-modulesassets-form';
        this.datatableIDSelector = '#moduleAssetsTable';
        this.defaultSortOrder = [];
    }// constructor


    /**
     * Initialize/activate data table.
     * 
     * @returns {undefined}
     */
    activateDataTable() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbaModulesAssetsObject.urls.getAssetsRESTUrl,
                    'method': RdbaModulesAssetsObject.urls.getAssetsRESTMethod,
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
                            return '<input id="module-' + row.id + '" type="checkbox" name="module_system_name[]" value="' + row.id + '">';
                        }
                    },
                    {
                        'data': 'id',
                        'targets': 2,
                        'visible': false,
                    },
                    {
                        'data': 'id',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            row.RdbaModulesAssetsObject = RdbaModulesAssetsObject;
                            let module_name = RdbaCommon.escapeHtml(data);
                            let output = '<label for="module-' + row.id + '">';
                            if (row.enabled === false) {
                                output += '<i class="fa-solid fa-ban"></i> ' + module_name;
                            } else {
                                output += module_name;
                            }
                            output += '</label>';
                            let html = output + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'module_number_assets',
                        'targets': 4
                    },
                    {
                        'data': 'module_location',
                        'targets': 5
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
                    json.RdbaModulesAssetsObject = RdbaModulesAssetsObject;
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
                    RdbaModulesAssetsObject.csrfKeyPair = json.csrfKeyPair;
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
            })// datatables on draw complete.
            ;
        });// uiXhrCommonData.done()
    }// activateDataTable


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // initialize datatables.
        this.activateDataTable();
        // listen on bulk action form submit and open as ajax.
        this.listenFormSubmit();
    }// init


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
                let moduleSystemNameArray = [];
                event.target.querySelectorAll('input[type="checkbox"][name="module_system_name[]"]:checked').forEach(function(item, index) {
                    moduleSystemNameArray.push(item.value);
                });
                if (moduleSystemNameArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbaModulesAssetsObject.txtPleaseSelectAtLeastOneModule,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = event.target.querySelector(thisClass.bulkActionsIDSelector);
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbaModulesAssetsObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    if (selectAction.value === 'publish') {
                        return thisClass.listenFormSubmitPublish(moduleSystemNameArray);
                    }
                }
            }// endif target is form.
        });// endif event listener.
    }// listenFormSubmit


    /**
     * AJAX call to publish assets.
     * 
     * @private This method was called from `listenFormSubmit()`.
     * @param {array} moduleSystemNameArray 
     * @returns {undefined}
     */
    listenFormSubmitPublish(moduleSystemNameArray) {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let confirmVal = confirm(RdbaModulesAssetsObject.txtAreYouSurePublish);
        let submitBtn = thisForm.querySelector(thisClass.buttonActionIDSelector);
        let selectAction = event.target.querySelector(thisClass.bulkActionsIDSelector);

        if (confirmVal) {
            // reset form result placeholder
            thisForm.querySelector('.form-result-placeholder').innerHTML = '';
            // add spinner icon
            thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock button
            submitBtn.setAttribute('disabled', 'disabled');

            let formData = new FormData();
            formData.append(RdbaModulesAssetsObject.csrfName, RdbaModulesAssetsObject.csrfKeyPair[RdbaModulesAssetsObject.csrfName]);
            formData.append(RdbaModulesAssetsObject.csrfValue, RdbaModulesAssetsObject.csrfKeyPair[RdbaModulesAssetsObject.csrfValue]);
            formData.append('module_system_name', moduleSystemNameArray.join(','));
            formData.append('action', selectAction.value);

            RdbaCommon.XHR({
                'url': RdbaModulesAssetsObject.urls.publishAssetsRESTUrl,
                'method': RdbaModulesAssetsObject.urls.publishAssetsRESTMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'data': new URLSearchParams(_.toArray(formData)).toString(),
                'dataType': 'json',
            })
            .then(function(responseObject) {
                // XHR success.
                let response = responseObject.response;

                if (typeof(response) !== 'undefined') {
                    if (typeof(response.formResultMessage) !== 'undefined') {
                        RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus, true, 'bottom', {'autohide': false});
                    }
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaModulesAssetsObject.csrfKeyPair = response.csrfKeyPair;
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
                    RdbaModulesAssetsObject.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .finally(function() {
                // remove loading icon
                thisForm.querySelector('.loading-icon').remove();
                // unlock submit button
                submitBtn.removeAttribute('disabled');
            });// end ajax
        }// endif confirmed.
    }// listenFormSubmitPublish


}// RdbaAssetsController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaAssetsController = new RdbaAssetsController();

    // init the class.
    rdbaAssetsController.init();
}, false);