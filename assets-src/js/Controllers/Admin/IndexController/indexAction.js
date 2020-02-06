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
                'dataType': 'json',
            })
            .catch(function(responseObject) {
                console.error(responseObject);
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
                                promises.push(thisClass.injectJs(item.id, jsItem));
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
                .catch(function(event) {
                    console.error('Inject JS error', event);
                })
                .then(function() {
                    let event = new Event('rdba.admindashboard.widgets.ready');
                    document.dispatchEvent(event);
                });

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
        .then(function() {
            return thisClass.makeWidgetsSortable();
        });
    }// init


    /**
     * Inject JS to before end body.
     * 
     * @private This method was called from `ajaxGetWidgetsHTML()` method.
     * @param {string} jsId The JS ID.
     * @param {string} jsPath The JS file path.
     * @returns {Promise}
     */
    injectJs(jsId, jsPath) {
        return new Promise(function(resolve, reject) {
            let injectJs = document.createElement('script');
            injectJs.id = 'rdba-dashboard-widget-js-' + jsId;
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
     * Make widget sortable.
     * 
     * @returns {Promise}
     */
    makeWidgetsSortable() {
        let promiseObj = new Promise(function(resolve, reject) {
            let rowNormalElement = document.getElementById('rdba-dashboard-row-normal');
            let rowHeroElement = document.getElementById('rdba-dashboard-row-hero');

            sortable(rowNormalElement, 'normal');
            sortable(rowHeroElement, 'hero');

            resolve();
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
                'onEnd': function(event) {
                    if (event.item && event.item.style) {
                        event.item.style = '';// remove stuck transform translateZ for sure.
                    }
                },
                'store': {
                    'set': function(sortable) {
                        let sortableArray = sortable.toArray();
                        if (sortableArray && sortableArray.length <= 1) {
                            // if only one item moved, no need to update.
                            return ;
                        }

                        let formData = 'updateData=' + JSON.stringify(sortable.toArray());
                        formData += '&' + RdbaAdminIndex.csrfName + '=' + RdbaAdminIndex.csrfKeyPair[RdbaAdminIndex.csrfName];
                        formData += '&' + RdbaAdminIndex.csrfValue + '=' + RdbaAdminIndex.csrfKeyPair[RdbaAdminIndex.csrfValue];
                        formData += '&widgetsType=' + widgetsType

                        RdbaCommon.XHR({
                            'url': RdbaAdminIndex.orderDashboardWidgetsUrl,
                            'method': RdbaAdminIndex.orderDashboardWidgetsMethod,
                            'data': formData,
                            'dataType': 'json'
                        })
                        .catch(function(responseObject) {
                            // XHR failed.
                            console.error(responseObject);
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