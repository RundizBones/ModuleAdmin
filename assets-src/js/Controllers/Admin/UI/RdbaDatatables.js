/**
 * Base datatables class.
 */


class RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @returns {RdbaDatatables}
     */
    constructor(options) {
        // Bulk actions wrapper selector that appears in `datatablesDOM` property.
        this.actionsControlsDOMWrapperSelector = '.rdba-datatables-actions-controls';
        // Template ID that contain bulk actions. The selector should begins with # mark.
        this.actionsControlsTemplateSelector = '#rdba-datatables-actions-controls';

        // Datatables DOM. (see https://datatables.net/reference/option/dom).
        this.datatablesDOM = '<"rdba-datatables-result-controls rd-columns-flex-container"\n\
            <"col-xs-12 col-sm-6 rdba-datatables-result-pagination"p>\n\
            >\n\
            <"clear clearfix">\n\
            rt\n\
            <"rdba-datatables-actions-controls rd-columns-flex-container"\n\
                <"col-xs-12 col-sm-6 rdba-datatables-result-pagination"p>\n\
            >\n\
            <"clear clearfix">';
        // Datatables ID selector.
        this.datatableIDSelector = '#listingTable';

        // Form ID of datatables.
        this.formIDSelector = '#rdba-listingpage-form';

        // Filter button selector. The selector should begins with # mark.
        this.inputFilterButtonSelector = '#rdba-datatables-filter-button';
        // Filter search selector. The selector should begins with # mark.
        this.inputFilterSearchSelector = '#rdba-filter-search';

        // Result controls wrapper selector that appears in `datatablesDOM` property.
        this.resultControlsDOMWrapperSelector = '.rdba-datatables-result-controls';
        // Result controls wrapper selector of pagination that appears in `datatablesDOM` property.
        this.resultControlsPaginationDOMWrapperSelector = '.rdba-datatables-result-pagination';
        // Template ID that contain pagination. The selector should begins with # mark.
        this.resultControlsPaginationTemplateSelector = '#rdba-datatables-result-controls-pagination';
        // Template ID that contain result controls (filter, search, reset). The selector should begins with # mark.
        this.resultControlsTemplateSelector = '#rdba-datatables-result-controls';

        if (typeof(options) === 'object') {
            _.defaults(options, this);
        }
    }// constructor


    /**
     * Add actions controls (bulk actions).
     * 
     * @private This method was called from the method that initialize the datatables.
     * @param {object} data
     * @returns {undefined}
     */
    addActionsControls(data) {
        let actionControlsTemplate = document.querySelector(this.actionsControlsTemplateSelector);
        if (actionControlsTemplate) {
            let source = actionControlsTemplate.innerHTML;
            let template = Handlebars.compile(source);

            let controlsElement = document.querySelector(this.actionsControlsDOMWrapperSelector);
            if (controlsElement) {
                controlsElement.insertAdjacentHTML('afterbegin', template(data));
            }
        } else {
            console.warn('action controls template (bulk actions) was not found.' + this.actionsControlsTemplateSelector);
        }
    }// addActionsControls


    /**
     * Add custom result controls (filter, search).
     * 
     * @private This method was called from the method that initialize the datatables.
     * @param {object} data
     * @returns {undefined}
     */
    addCustomResultControls(data) {
        let resultControlsTemplate = document.querySelector(this.resultControlsTemplateSelector);
        if (resultControlsTemplate) {
            let source = resultControlsTemplate.innerHTML;
            let template = Handlebars.compile(source);

            let controlsElement = document.querySelector(this.resultControlsDOMWrapperSelector);
            if (controlsElement) {
                controlsElement.insertAdjacentHTML('afterbegin', template(data));
            }
        } else {
            console.warn('result controls template (filters) was not found. ' + this.resultControlsTemplateSelector);
        }
    }// addCustomResultControls


    /**
     * Add custom result controls event.
     * 
     * This method was called from the method that initialize the datatables, after the table has been drawn.<br>
     * This will listen on key up for input, and add custom search box to server request.
     * 
     * @private This method was called from the method that initialize the datatables.
     * @param {object} dataTable
     * @returns {undefined}
     */
    addCustomResultControlsEvents(dataTable) {
        let $ = jQuery.noConflict();
        let thisClass = this;

        // listen on keyup to search/filter.
        $(thisClass.resultControlsDOMWrapperSelector).off('keydown');
        $(thisClass.resultControlsDOMWrapperSelector).on('keydown', thisClass.inputFilterSearchSelector, function(e) {
            if (
                (
                    typeof(e.key) !== 'undefined' && 
                    (e.key === 'Enter' || e.key === 'NumpadEnter')
                ) ||
                (
                    typeof(e.code) !== 'undefined' && 
                    (e.code === 'Enter' || e.code === 'NumpadEnter')
                )
            ) {
                e.preventDefault();
                $(thisClass.inputFilterButtonSelector).trigger('click');
            }
        });

        // add custom search input into search query on click filter button.
        $(thisClass.resultControlsDOMWrapperSelector).off('click');
        $(thisClass.resultControlsDOMWrapperSelector).on('click', thisClass.inputFilterButtonSelector, function(e) {
            e.preventDefault();
            let searchValue = $(thisClass.inputFilterSearchSelector).val();
            if (typeof(searchValue) !== 'undefined') {
                dataTable.search(searchValue).draw();
            } else {
                console.warn('the search input could not be found. ' + thisClass.inputFilterSearchSelector);
            }
        });
    }// addCustomResultControlsEvents


    /**
     * Add custom result controls information and/or pagination.
     * 
     * @private This method was called from the method that initialize the datatables.
     * @param {object} data
     * @returns {undefined}
     */
    addCustomResultControlsPagination(data) {
        let $ = jQuery.noConflict();
        let thisClass = this;

        Handlebars.registerHelper('ifGE', function (v1, v2, options) {
            if (v1 >= v2) {
                return options.fn(this);
            }
            return options.inverse(this);
        });

        let resultControlsPaginationTemplate = document.querySelector(this.resultControlsPaginationTemplateSelector);
        if (resultControlsPaginationTemplate) {
            let source = resultControlsPaginationTemplate.innerHTML;
            let template = Handlebars.compile(source);
            let paginationDOMWrapper = $(this.resultControlsPaginationDOMWrapperSelector);

            if (paginationDOMWrapper) {
                // remove class that come with datatables, and add `.rd-button` class to pagination.
                paginationDOMWrapper.find('.paginate_button').removeClass('paginate_button').addClass('rd-button');
                // move text from buttons to `aria-label` and add symbol.
                paginationDOMWrapper.find('.rd-button').each(function(index, item) {
                    $(this).attr('aria-label', item.innerText);
                    if (RdbaCommon.isset(() => RdbaUIXhrCommonData.paginationSymbol.first)) {
                        if ($(this).hasClass('first')) {
                            $(this).html(RdbaUIXhrCommonData.paginationSymbol.first);
                        }
                        if ($(this).hasClass('last')) {
                            $(this).html(RdbaUIXhrCommonData.paginationSymbol.last);
                        }
                        if ($(this).hasClass('previous')) {
                            $(this).html(RdbaUIXhrCommonData.paginationSymbol.previous);
                        }
                        if ($(this).hasClass('next')) {
                            $(this).html(RdbaUIXhrCommonData.paginationSymbol.next);
                        }
                    }
                });

                paginationDOMWrapper.find('.rdba-datatables-result-controls-info').remove();
                paginationDOMWrapper.prepend(template(data));
            }
        } else {
            console.warn('result controls pagination template was not found. ' + this.resultControlsPaginationTemplateSelector);
        }
    }// addCustomResultControlsPagination


    /**
     * Set `RdbaXhrDialog` class object to this class's property for easy accesss via `RdbaXhrDialog` property.
     * 
     * @param {object} rdbaXhrDialog
     * @returns {undefined}
     */
    setRdbaXhrDialogObject(rdbaXhrDialog) {
        this.RdbaXhrDialog = rdbaXhrDialog;
    }// setRdbaXhrDialogObject


}// RdbaDatatables