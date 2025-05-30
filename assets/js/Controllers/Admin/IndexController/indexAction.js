/**
 * Admin dashboard JS for its controller.
 */


class RdbaIndexController {


    /**
     * AJAX get admin dashboard widgets HTML.
     * 
     * @returns {undefined}
     */
    ajaxGetWidgetsHTML() {
        let thisClass = this;

        let promiseObj = new Promise(function(resolve, reject) {
            RdbaCommon.XHR({
                'url': RdbaAdminIndex.getDashboardWidgetsUrl,
                'method': RdbaAdminIndex.getDashboardWidgetsMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'dataType': 'json',
            })
            .catch(function(responseObject) {
                console.error('[rdba] ', responseObject);
                let response = (responseObject ? responseObject.response : {});

                reject(response);
            })
            .then(function(responseObject) {
                let response = (responseObject ? responseObject.response : {});
                let promises = [];

                if (RdbaCommon.isset(() => response.widgets) && _.isObject(response.widgets)) {
                    let widgetSourceHero = document.getElementById('rdba-dashboardwidget-rowhero').innerHTML;
                    let templateHero = Handlebars.compile(widgetSourceHero);
                    let widgetSourceNormal = document.getElementById('rdba-dashboardwidget-rownormal').innerHTML;
                    let templateNormal = Handlebars.compile(widgetSourceNormal);

                    // loop each widget.
                    for (let key in response.widgets) {
                        let item = response.widgets[key];
                        item.id = key;

                        if (item.rowHero === true) {
                            // if this widget is position in hero row.
                            let compiledWidget = templateHero(item);
                            document.getElementById('rdba-dashboard-row-hero').insertAdjacentHTML('beforeend', compiledWidget);
                        } else {
                            // if this widget is position in normal row.
                            let compiledWidget = templateNormal(item);
                            document.getElementById('rdba-dashboard-row-normal').insertAdjacentHTML('beforeend', compiledWidget);
                        }

                        if (RdbaCommon.isset(() => item.js) && _.isArray(item.js)) {
                            item.js.forEach(function(jsItem, jsIndex) {
                                promises.push(thisClass.injectJs(item.id, jsItem, jsIndex));
                            });
                        }

                        if (RdbaCommon.isset(() => item.css) && _.isArray(item.css)) {
                            item.css.forEach(function(cssItem, cssIndex) {
                                promises.push(thisClass.injectCss(item.id, cssItem, cssIndex));
                            });
                        }
                    };
                }// endif response contain widgets.

                // check if there is no widget in row hero then hide it.
                let rowHero = document.getElementById('rdba-dashboard-row-hero');
                if (!rowHero || rowHero.innerHTML === '') {
                    rowHero.classList.add('rd-hidden');
                }

                // check if there is no widget in row normal then hide it.
                let rowNormal = document.getElementById('rdba-dashboard-row-normal');
                if (!rowNormal || rowNormal.innerHTML === '') {
                    rowNormal.classList.add('rd-hidden');
                }

                // wait until all promises resolved and dispatch custom event so those widgets can use as event ready.
                Promise.all(promises)
                .then(function() {
                    //console.log('[rdba] injected css, js of widgets.');
                    let event = new Event('rdba.admindashboard.widgets.ready');
                    document.dispatchEvent(event);
                })
                .catch(function(event) {
                    console.error('[rdba] Inject JS, CSS error:', event);
                });

                //console.log('[rdba] finish render dashboard widgets.');
                resolve(response);
            });// end XHR
        });

        return promiseObj;
    }// ajaxGetWidgetsHTML


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        let $ = jQuery.noConflict();
        let thisClass = this;

        $.when(uiXhrCommonData)
        .then(function() {
            return thisClass.ajaxGetWidgetsHTML();
        })
        ;

        document.addEventListener('rdba.admindashboard.widgets.ready', function(e) {
            thisClass.makeWidgetsMasonry()
                .then(function() {
                    return thisClass.makeWidgetsSortable();
                })
            ;
        });
    }// init


    /**
     * Inject CSS to before end head.
     * 
     * @private This method was called from `ajaxGetWidgetsHTML()` method.
     * @param {string} cssId The CSS ID.
     * @param {string} cssPath The CSS file path.
     * @param {int} indexNumber The array index number for append to id.
     * @returns {Promise}
     */
    injectCss(cssId, cssPath, indexNumber) {
        return new Promise(function(resolve, reject) {
            let injectCss = document.createElement('link');
            injectCss.id = 'rdba-dashboard-widget-css-' + cssId + (indexNumber ? '-' + indexNumber : '');
            injectCss.rel = 'stylesheet';
            injectCss.type = 'text/css';
            injectCss.href = cssPath;
            injectCss.addEventListener('load', resolve);
            injectCss.addEventListener('error', () => reject('Error loading CSS'));
            injectCss.addEventListener('abort', () => reject('Abort loading CSS'));

            if (!document.querySelector('#rdba-dashboard-widget-css-' + cssId)) {
                document.head.appendChild(injectCss);
            }
        });
    }// injectCss


    /**
     * Inject JS to before end body.
     * 
     * @private This method was called from `ajaxGetWidgetsHTML()` method.
     * @param {string} jsId The JS ID.
     * @param {string} jsPath The JS file path.
     * @param {int} indexNumber The array index number for append to id.
     * @returns {Promise}
     */
    injectJs(jsId, jsPath, indexNumber) {
        return new Promise(function(resolve, reject) {
            let injectJs = document.createElement('script');
            injectJs.id = 'rdba-dashboard-widget-js-' + jsId + (indexNumber ? '-' + indexNumber : '');
            injectJs.src = jsPath;
            injectJs.type = 'text/javascript';
            injectJs.addEventListener('load', resolve);
            injectJs.addEventListener('error', () => reject('Error loading script'));
            injectJs.addEventListener('abort', () => reject('Abort loading script'));

            if (!document.querySelector('#rdba-dashboard-widget-js-' + jsId)) {
                document.body.appendChild(injectJs);
            }
        });
    }// injectJs


    /**
     * Make dashboard widgets masonry.
     * 
     * @since 1.1.5
     * @private This method was called from `init()` method.
     * @link https://medium.com/@andybarefoot/a-masonry-style-layout-using-css-grid-8c663d355ebb Original source code.
     * @returns {Promise}
     */
    makeWidgetsMasonry() {
        let promiseObj = new Promise(function(resolve, reject) {
            function resizeAllGridItems() {
                //console.log('[rdba] resizing grid items.');
                let allItems = document.getElementsByClassName("rdba-dashboard-widget-item");
                for(let x=0;x<allItems.length;x++){
                  resizeGridItem(allItems[x]);
                }
            }

            function resizeGridItem(item) {
                let grid = document.getElementsByClassName("rdba-dashboard-row-normal")[0];
                let rowHeight = parseInt(window.getComputedStyle(grid).getPropertyValue('grid-auto-rows'));
                let rowGap = parseInt(window.getComputedStyle(grid).getPropertyValue('row-gap'));
                let rowSpan = Math.ceil((item.querySelector('.rdba-dashboard-widget-contents').getBoundingClientRect().height + rowGap) / (rowHeight + rowGap));
                item.style.gridRowEnd = "span " + rowSpan;
            }

            // this class will be called on dom loaded. let's start
            resizeAllGridItems();
            window.addEventListener('resize', resizeAllGridItems);

            resolve();
            //console.log('[rdba] finish make widgets masonry.');
        });

        return promiseObj;
    }// makeWidgetsMasonry


    /**
     * Make widget sortable.
     * 
     * @private This method was called from `init()` method.
     * @returns {Promise}
     */
    makeWidgetsSortable() {
        let promiseObj = new Promise(function(resolve, reject) {
            let rowNormalElement = document.getElementById('rdba-dashboard-row-normal');
            let rowHeroElement = document.getElementById('rdba-dashboard-row-hero');

            sortable(rowNormalElement, 'normal');
            sortable(rowHeroElement, 'hero');

            resolve();
            //console.log('[rdba] finish make widgets sortable.');
        });

        return promiseObj;


        /**
         * Do sortable widgets for certain type.
         * 
         * @param {HTMLElement} element
         * @param {string} widgetsType
         * @returns {undefined}
         */
        function sortable(element, widgetsType) {
            if (widgetsType !== 'hero' && widgetsType !== 'normal') {
                widgetsType = 'normal';
            }

            let sortableJS = new Sortable(element, {
                'animation': 150,
                'dataIdAttr': 'data-widgetid',
                'handle': '.drag-icon',
                'store': {
                    'set': function(sortable) {
                        let sortableArray = sortable.toArray();
                        if (sortableArray && sortableArray.length <= 1) {
                            // if only one item moved, no need to update.
                            return ;
                        }

                        let formData = new FormData();
                        formData.append('updateData', JSON.stringify(sortable.toArray()));
                        formData.append('widgetsType', widgetsType);
                        formData.append(RdbaAdminIndex.csrfName, RdbaAdminIndex.csrfKeyPair[RdbaAdminIndex.csrfName]);
                        formData.append(RdbaAdminIndex.csrfValue, RdbaAdminIndex.csrfKeyPair[RdbaAdminIndex.csrfValue]);

                        RdbaCommon.XHR({
                            'url': RdbaAdminIndex.orderDashboardWidgetsUrl,
                            'method': RdbaAdminIndex.orderDashboardWidgetsMethod,
                            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'data': new URLSearchParams(formData).toString(),
                            'dataType': 'json'
                        })
                        .catch(function(responseObject) {
                            // XHR failed.
                            console.error('[rdba] ', responseObject);
                            let response = responseObject.response;

                            if (typeof(response) !== 'undefined') {
                                if (typeof(response.formResultMessage) !== 'undefined') {
                                    RDTAAlertDialog.alert({
                                        'type': 'danger',
                                        'html': response.formResultMessage
                                    });
                                }
                            }

                            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                                RdbaAdminIndex.csrfKeyPair = response.csrfKeyPair;
                            }

                            return Promise.reject(responseObject);
                        })
                        .then(function(responseObject) {
                            // XHR success.
                            let response = responseObject.response;

                            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                                RdbaAdminIndex.csrfKeyPair = response.csrfKeyPair;
                            }

                            return Promise.resolve(responseObject);
                        });
                    }
                }
            });
        }// sortable
    }// makeWidgetsSortable


}


document.addEventListener('DOMContentLoaded', function() {
    let rdbaIndexController = new RdbaIndexController();

    // init class.
    rdbaIndexController.init();
}, false);