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
        // The class name of result controls of filter/search.
        this.resultControlsFilterSearchClassName = 'rdba-datatables-result-filtersearch';
        // Result controls wrapper selector of pagination that appears in `datatablesDOM` property.
        this.resultControlsPaginationDOMWrapperSelector = '.rdba-datatables-result-pagination';
        // Template ID that contain pagination. The selector should begins with # mark.
        this.resultControlsPaginationTemplateSelector = '#rdba-datatables-result-controls-pagination';
        // Template ID that contain result controls (filter, search, reset). The selector should begins with # mark.
        this.resultControlsTemplateSelector = '#rdba-datatables-result-controls';

        if (typeof(options) === 'object') {
            _.defaults(options, this);
        }

        // Listen pagination events using event delegation.
        this.#listenPaginationEvents();
    }// constructor


    /**
     * Listen pagination events such as keydown (Enter) and prevent it.
     * 
     * This will be call once use event delegation.  
     * This method was called from `constructor()`.
     * 
     * @since 1.2.8
     * @returns {undefined}
     */
    #listenPaginationEvents() {
        // listen on keydown (Enter) and prevent form submit where enter in pagination input.
        document.addEventListener('keydown', (event) => {
            const thisTarget = event.target;
            if (
                thisTarget.classList.contains('paginate_input') &&
                (
                    (event?.key === 'Enter' || event?.key === 'NumpadEnter')
                    ||
                    (event?.code === 'Enter' || event?.code === 'NumpadEnter')
                )
            ) {
                // if found pagination input and key is enter.
                event.preventDefault();
            }// endif; found pagination input and key is enter.
        });
    }// #listenPaginationEvents


    /**
     * Add actions controls (bulk actions) to the actions controls element.
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
            console.warn('[rdba] action controls template (bulk actions) was not found.' + this.actionsControlsTemplateSelector);
        }
    }// addActionsControls


    /**
     * Add custom result controls (filter, search) to the controls element.
     * 
     * @private This method was called from the method that initialize the datatables.
     * @param {object} data
     * @returns {undefined}
     */
    addCustomResultControls(data) {
        let resultControlsTemplate = document.querySelector(this.resultControlsTemplateSelector);
        if (resultControlsTemplate) {
            let source = resultControlsTemplate.innerHTML;
            // add custom required class to filter, search element. ------------
            const div = document.createElement('div');
            div.innerHTML = source;
            div?.firstElementChild?.classList.add(this.resultControlsFilterSearchClassName);
            source = div.innerHTML;
            // end add custom required class to filter, search element. --------
            let template = Handlebars.compile(source);

            let controlsElement = document.querySelector(this.resultControlsDOMWrapperSelector);
            if (controlsElement) {
                controlsElement.insertAdjacentHTML('afterbegin', template(data));
            }
        } else {
            console.warn('[rdba] result controls template (filters) was not found. ' + this.resultControlsTemplateSelector);
        }
    }// addCustomResultControls


    /**
     * Add custom result controls events.
     * 
     * This method was called from the method that initialize the datatables, after the table has been drawn.<br>
     * This will listen on **Enter** key inside filter form, and call data table search use filter search box (`dataTable.search('keyword').draw()`).
     * 
     * @private This method was called from the method that initialize the datatables.
     * @param {object} dataTable The data table object.
     * @returns {undefined}
     */
    addCustomResultControlsEvents(dataTable) {
        let thisClass = this;

        /**
         * Enter key to click event handler.
         * 
         * @since 1.2.8
         * @param {object} event
         * @returns {undefined}
         */
        function enterToClickFilter(event) {
            const thisTarget = event.target;
            let isEnter = false;
            if (thisTarget.closest(thisClass.resultControlsDOMWrapperSelector)) {
                // if found selector of result control area (filter, search, maybe pagination input).
                if (
                    (event?.key === 'Enter' || event?.key === 'NumpadEnter')
                    ||
                    (event?.code === 'Enter' || event?.code === 'NumpadEnter')
                ) {
                    // if user hit enter in result controls area (filter area, pagination input).
                    // prevent submit.
                    event.preventDefault();
                    isEnter = true;
                }// endif; enter
            }// endif; found selector of result control area where this event is started.

            if (true === isEnter && thisTarget.closest('.' + thisClass.resultControlsFilterSearchClassName)) {
                // if found filter, search area from where this event is started.
                // trigger click on filter button.
                document.querySelector(thisClass.inputFilterButtonSelector).click();
            }// endif; found filter, search area from where this event is started.
        }// enterToClickFilter

        // listen on enter to anything in filter form.
        document.removeEventListener('keydown', enterToClickFilter, {'capture': false});
        document.addEventListener('keydown', enterToClickFilter, {'capture': false});

        /**
         * Click filter button to search DataTable.
         * 
         * Add custom search value to DataTable `search()` and re-draw the table.
         * 
         * @since 1.2.8
         * @param {object} event
         * @returns {undefined}
         */
        function clickFilterToSearchDT(event) {
            const thisTarget = event.target;
            if (thisTarget.closest(thisClass.inputFilterButtonSelector)) {
                // if found selector of filter button.
                event.preventDefault();
                const searchValue = document.querySelector(thisClass.inputFilterSearchSelector)?.value;
                if (typeof(searchValue) === 'string') {
                    // if found search (filter) input value and correct type.
                    // remove all listened events in this method.
                    document.removeEventListener('keydown', enterToClickFilter, {'capture': false});
                    document.removeEventListener('click', clickFilterToSearchDT, {'capture': false});
                    // call DataTable search and draw.
                    dataTable.search(searchValue).draw();
                } else {
                    console.warn('[rdba] the search input could not be found. ' + thisClass.inputFilterSearchSelector);
                }
            }// endif; found selector of filter button.
        }// clickFilterToSearchDT

        // listen on click to filter button.
        document.removeEventListener('click', clickFilterToSearchDT, {'capture': false});
        document.addEventListener('click', clickFilterToSearchDT, {'capture': false});
    }// addCustomResultControlsEvents


    /**
     * Add custom result controls pagination and/or information.
     * 
     * The information is the number of total result found on the database. For example: (Found 24 items).
     * 
     * @private This method was called from the method that initialize the datatables.
     * @param {object} data
     * @returns {undefined}
     */
    addCustomResultControlsPagination(data) {
        Handlebars.registerHelper('ifGE', function (v1, v2, options) {
            if (v1 >= v2) {
                return options.fn(this);
            }
            return options.inverse(this);
        });

        const paginationDOMWrappers = document.querySelectorAll(this.resultControlsPaginationDOMWrapperSelector);
        if (paginationDOMWrappers) {
            // if found pagination HTML wrapper.
            paginationDOMWrappers.forEach((eachPaginationDW) => {
                // remove class for buttons that come with datatables, and add `.rd-button` class to pagination.
                eachPaginationDW.querySelectorAll('.paginate_button')?.forEach((eachBtn) => {
                    eachBtn.classList.remove('paginate_button');
                    eachBtn.classList.add('rd-button');
                });
                // end remove class for buttons. ---------------------

                // move text from buttons to `aria-label` and add symbols.
                eachPaginationDW.querySelectorAll('.rd-button')?.forEach((item) => {
                    item.setAttribute('aria-label', item.innerText);
                    if (RdbaCommon.isset(() => RdbaUIXhrCommonData.paginationSymbol.first)) {
                        if (item.classList.contains('first')) {
                            item.innerHTML = RdbaUIXhrCommonData.paginationSymbol.first;
                        }
                        if (item.classList.contains('last')) {
                            item.innerHTML = RdbaUIXhrCommonData.paginationSymbol.last;
                        }
                        if (item.classList.contains('previous')) {
                            item.innerHTML = RdbaUIXhrCommonData.paginationSymbol.previous;
                        }
                        if (item.classList.contains('next')) {
                            item.innerHTML = RdbaUIXhrCommonData.paginationSymbol.next;
                        }
                    }
                });// endforEach pagination's buttons.
                // end move text and add symbols. ---------------------
            });// endforEach paginationDomWrappers.
        }// endif; found pagination HTML wrapper.

        const resultControlsPaginationTemplate = document.querySelector(this.resultControlsPaginationTemplateSelector);
        if (resultControlsPaginationTemplate) {
            if (paginationDOMWrappers) {
                // if found pagination HTML wrapper.
                let source = resultControlsPaginationTemplate.innerHTML;
                let template = Handlebars.compile(source);

                paginationDOMWrappers.forEach((eachPaginationDW) => {
                    // remove any information that may already exists before prepend new one.
                    eachPaginationDW.querySelectorAll('.rdba-datatables-result-controls-info')?.forEach((item) => {
                        item.remove();
                    });
                    // prepend information to the pagination HTML wrapper.
                    eachPaginationDW.insertAdjacentHTML('afterbegin', template(data));
                });// endforEach paginationDomWrappers.
            }// endif; found pagination HTML wrapper.
        } else {
            console.warn('[rdba] result controls pagination template was not found. ' + this.resultControlsPaginationTemplateSelector);
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