/**
 * Common JS class/functions.
 */


class RdbaCommon {


    constructor() {
        
    }// constructor


    /**
     * Toggle all checkbox in a table.
     * 
     * This is for fix datatables JS with 'fixed header' that cause checkbox cant toggle the same value properly.<br>
     * Usage example:
     * <pre>
     * &lt;input type=&quot;checkbox&quot; onclick=&quot;RdbaCommon.dataTableCheckboxToggler(jQuery('.table-class-selector'), jQuery(this));&quot;&gt;
     * </pre>
     * 
     * @param {jQuery}
     * @param {jQuery} `jQuery(this)`
     * @returns {undefined}
     */
    static dataTableCheckboxToggler(jQueryObject, thisObj) {
        jQueryObject.find('input[type="checkbox"]').attr('checked', thisObj.is(':checked'));
        jQueryObject.find('input[type="checkbox"]').prop('checked', thisObj.is(':checked'));
    }// dataTableCheckboxToggler


    /**
     * Display alert box fixed to web page.
     * 
     * @param {string|object} message The alert content.
     * @param {string} status The alert status. Example: 'success', 'error', 'info', 'warning', or can be alert class based on RDTA alert box class name such as 'alert-success', 'alert-danger', etc. Default is 'warning'.
     * @param {bool} dismissable Make alert dismissable if it is `true`, if `false` then it is not. Default is `true`.
     * @param {string} position Fixed position. Accept value 'bottom' or 'top'. Default is 'bottom'.
     * @returns {undefined}
     */
    static displayAlertboxFixed(message, status = 'warning', dismissable = true, position = 'bottom') {
        let timeoutAlertFade, timeoutAlertRemove;

        // validate argument type is correct.
        if (typeof(status) !== 'string') {
            status = 'warning';
        }
        if (dismissable !== false && dismissable !== true) {
            dismissable = true;
        }
        if (position !== 'top' && position !== 'bottom') {
            position = 'bottom';
        }

        let alertClass = RdbaCommon.getAlertClassFromStatus(status);
        let alertContent = RdbaCommon.renderAlertContent(message);
        let alertBox = RdbaCommon.renderAlertHtml(alertClass, alertContent, dismissable, position);

        // alert box can only be display one box at a time.
        // remove previous alert box.
        if (document.querySelector('.rd-alertbox.fixed-' + position)) {
            document.querySelector('.rd-alertbox.fixed-' + position).remove();
        }

        // put alert box HTML into before end of body.
        document.body.insertAdjacentHTML('beforeend', alertBox);
        // also add class for fade out when close.
        document.querySelector('.rd-alertbox.fixed-' + position).classList.add('rd-animation');
        document.querySelector('.rd-alertbox.fixed-' + position).classList.add('fade');

        // clear old timeout meter.
        clearTimeout(timeoutAlertFade);
        clearTimeout(timeoutAlertRemove);

        // make alert box disappear after few seconds. number of seconds is based on rd-animation fade.
        timeoutAlertFade = setTimeout(function() {
            if (document.querySelector('.rd-alertbox.fixed-' + position)) {
                document.querySelector('.rd-alertbox.fixed-' + position).classList.add('fade-out');
            }
        }, 5000);
        // also remove alert box after disappeared. number of seconds is based on rd-animation fade.
        timeoutAlertRemove = setTimeout(function() {
            if (document.querySelector('.rd-alertbox.fixed-' + position)) {
                document.querySelector('.rd-alertbox.fixed-' + position).remove();
            }
        }, 5400);
    }// displayAlertboxFixed


    /**
     * Display alert box float to web page.
     * 
     * @since 1.0.4
     * @param {string|object} message The alert content.
     * @param {string} status The alert status. Example: 'success', 'error', 'info', 'warning', or can be alert class based on RDTA alert box class name such as 'alert-success', 'alert-danger', etc. Default is 'warning'.
     * @param {bool} dismissable Make alert dismissable if it is `true`, if `false` then it is not. Default is `true`.
     * @param {string} position Float position. Accept value 'bottom' or 'top'. Default is 'bottom'.
     * @returns {undefined}
     */
    static displayAlertboxFloat(message, status = 'warning', dismissable = true, position = 'bottom') {
        let timeoutAlertFade, timeoutAlertRemove;

        // validate argument type is correct.
        if (typeof(status) !== 'string') {
            status = 'warning';
        }
        if (dismissable !== false && dismissable !== true) {
            dismissable = true;
        }
        if (position !== 'top' && position !== 'bottom') {
            position = 'bottom';
        }

        let alertClass = RdbaCommon.getAlertClassFromStatus(status);
        let alertContent = RdbaCommon.renderAlertContent(message);
        let alertBox = RdbaCommon.renderAlertHtml(alertClass, alertContent, dismissable, position, 'float');

        // alert box can only be display one box at a time.
        // remove previous alert box.
        if (document.querySelector('.rd-alertbox.float-' + position)) {
            document.querySelector('.rd-alertbox.float-' + position).remove();
        }

        // put alert box HTML into before end of body.
        document.body.insertAdjacentHTML('beforeend', alertBox);
        // also add class for fade out when close.
        document.querySelector('.rd-alertbox.float-' + position).classList.add('rd-animation');
        document.querySelector('.rd-alertbox.float-' + position).classList.add('fade');

        // clear old timeout meter.
        clearTimeout(timeoutAlertFade);
        clearTimeout(timeoutAlertRemove);

        // make alert box disappear after few seconds. number of seconds is based on rd-animation fade.
        timeoutAlertFade = setTimeout(function() {
            if (document.querySelector('.rd-alertbox.float-' + position)) {
                document.querySelector('.rd-alertbox.float-' + position).classList.add('fade-out');
            }
        }, 5000);
        // also remove alert box after disappeared. number of seconds is based on rd-animation fade.
        timeoutAlertRemove = setTimeout(function() {
            if (document.querySelector('.rd-alertbox.float-' + position)) {
                document.querySelector('.rd-alertbox.float-' + position).remove();
            }
        }, 5400);
    }// displayAlertboxFloat


    /**
     * Escape HTML.
     * 
     * @link https://stackoverflow.com/a/4835406/128761 Original source code.
     * @param {string} string
     * @returns {string}
     */
    static escapeHtml(string) {
        let map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        if (string && string !== null) {
            return string.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        return string;
    }// escapeHtml


    /**
     * Get alert class from status.
     * 
     * @param {string} status Acceptable status is 'success', 'error', 'info', 'warning'. Anything else will be return as 'alert-warning'.
     * @returns {String} Return RDTA alert box class (alert-xxx).
     */
    static getAlertClassFromStatus(status) {
        if (status === 'alert-danger' || status === 'alert-info' || status === 'alert-success' || status === 'alert-warning') {
            // if already contain class for RDTA alertbox.
            // don't do anything here.
            return status;
        }

        let alertClass;

        if (status === 'success') {
            alertClass = 'alert-success';
        } else if (status === 'error' || status === 'danger') {
            alertClass = 'alert-danger';
        } else if (status === 'info') {
            alertClass = 'alert-info';
        } else {
            alertClass = 'alert-warning';
        }

        return alertClass;
    }// getAlertClassFromStatus


    /**
     * Convert from bytes to human readable file size.
     * 
     * @link https://physics.nist.gov/cuu/Units/binary.html SI unit.
     * @link https://stackoverflow.com/a/14919494/128761 Original source code.
     * @param {int} bytes Number of bytes.
     * @param {bool} si True to use metric (SI) units, aka powers of 1000. False to use 
     *           binary (IEC), aka powers of 1024.
     * @param {int} dp Number of decimal places to display.
     * @returns {String} Return human readable file size.
     */
    static humanFileSize(bytes, si = false, dp=1) {
        const thresh = si ? 1000 : 1024;

        if (Math.abs(bytes) < thresh) {
            return bytes + ' B';
        }

        const units = si 
            ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] 
            : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        let u = -1;
        const r = 10**dp;

        do {
            bytes /= thresh;
            ++u;
        } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);


        return bytes.toFixed(dp) + ' ' + units[u];
    }// humanReadableFileSize


    /**
     * Isset in JS.
     * 
     * When calling this, it must be call with function wrapped the object/variable.<br>
     * For example:
     * <pre>
     * let some = {
     *     nested: {
     *         value: 'hello'
     *     }
     * }
     * RdbaCommon.isset(some.nested);// true
     * RdbaCommon.isset(some.nested.deeper);// false
     * RdbaCommon.isset(some.nested.deeper.notExists);// error
     * 
     * // with these it will not showing any error.
     * RdbaCommon.isset(() => some.nested);// true, using arrow function in JS ES6.
     * RdbaCommon.isset(() => some.nested.deeper.notExists);// false, using arrow function in JS ES6.
     * RdbaCommon.isset(function () {return some.nested});// true, using anonymous function.
     * RdbaCommon.isset(function () {return some.nested.deeper.notExists});// false, using anonymous function.
     * </pre>
     * 
     * @link https://stackoverflow.com/a/46256973/128761 Original source code.
     * @param {mixed} accessor Function that returns our value
     * @returns {Boolean} Return `true` if it was set, `false` for failure.
     */
    static isset(accessor) {
        try {
            // Note we're seeing if the returned value of our function is not
            // undefined
            return typeof accessor() !== 'undefined'
        } catch (e) {
            // And we're able to catch the Error it would normally throw for
            // referencing a property of undefined
            return false
        }
    }// isset


    /**
     * Detect on change language and set new language.
     * 
     * @return {undefined}
     */
    static onChangeLanguage() {
        let $ = jQuery.noConflict();

        // detect on selectbox.
        $('#rundizbones-languages-selectbox').on('change', function(e) {
            e.preventDefault();
            let thisSelectbox = $(this);

            $.ajax({
                'url': $('#setLanguage_url').val(),
                'method': $('#setLanguage_method').val(),
                data: 'currentUrl=' + $('#currentUrl').val() + '&rundizbones-languages=' + thisSelectbox.val(),
                dataType: 'json'
            })
            .done(function(data, textStatus, jqXHR) {
                let response = data;

                if (typeof(response.redirectUrl) !== 'undefined') {
                    window.location.href = response.redirectUrl;
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error(jqXHR);
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
            });
        });// selectbox on change.

        // detect on navbar listbox.
        // please look at js/Controllers/Admin/Ui/XhrCommonDataController/indexAction.js
    }// onChangeLanguage


    /**
     * Render alert content if it is not string then make it unordered list.
     * 
     * @param {string|object} content
     * @returns {unresolved}
     */
    static renderAlertContent(content) {
        let $ = jQuery.noConflict();

        if (typeof(content) === 'object') {
            let newContent = '<ul class="rd-alert-list">';
            $.each(content, function(index, value) {
                newContent += '<li>' + value + '</li>';
            });
            newContent += '</ul>';

            content = newContent;
        }

        return content;
    }// renderAlertContent


    /**
    * Generate alert box HTML.
    * 
    * @param {string} alertClass The alert class based on RDTA alert box class name. Example: 'alert-success', 'alert-danger'.
    * @param {string|object} content The alert content.
    * @param {bool} dismissable Make alert dismissable if it is `true`, if `false` then it is not. Default is `true`.
    * @param {string} position Fixed or float position. Accept value 'bottom' or 'top'. Default is empty.
    * @param {string} alertBoxType Alert box type. Accept value 'fixed' or 'float'. 
    * @returns {string} Return generated alert box HTML.
    */
    static renderAlertHtml(alertClass, content, dismissable = true, position = '', alertBoxType = 'fixed') {
        let $ = jQuery.noConflict();

        content = this.renderAlertContent(content);

        if (typeof(dismissable) === 'undefined' || dismissable !== false) {
            dismissable = true;
        }

        let classPosition;
        if (alertBoxType === 'fixed') {
            classPosition = ' fixed-';
        } else if (alertBoxType === 'float') {
            classPosition = ' float-';
        }

        if (position === 'bottom' || position === 'top') {
            classPosition += position;
        } else {
            classPosition = '';
        }

        if (dismissable === true) {
            return '<div class="rd-alertbox ' + alertClass + classPosition + ' is-dismissable">'+
                '<button class="close" type="button" aria-label="Close" onclick="return RundizTemplateAdmin.closeAlertbox(this);"><span aria-hidden="true">&times;</span></button>'+
                content+
                '</div>';
        } else {
            return '<div class="rd-alertbox ' + alertClass + classPosition + '">'+
                content+
                '</div>';
        }
    }// renderAlertHtml


    /**
     * Remove unsafe URL characters but not URL encode.
     * 
     * This will not remove new line (if `alphanumOnly` is `false`).
     * 
     * @link https://www.w3.org/Addressing/URL/url-spec.html URL specific.
     * @link https://help.marklogic.com/Knowledgebase/Article/View/251/0/using-url-encoding-to-handle-special-characters-in-a-document-uri Reference.
     * @link https://perishablepress.com/stop-using-unsafe-characters-in-urls/ Reference.
     * @link https://stackoverflow.com/questions/12317049/how-to-split-a-long-regular-expression-into-multiple-lines-in-javascript Multiple line regular expression reference.
     * @param {string} name The URL name.
     * @param {bool} alphanumOnly Alpha-numeric only or not. Default is `false` (not).
     * @returns {string} Return formatted URL name.
     */
    static removeUnsafeUrlCharacters(name, alphanumOnly = false) {
        if (!_.isString(name)) {
            // if not string, don't waste time.
            return '';
        }

        if (!_.isBoolean(alphanumOnly)) {
            alphanumOnly = false;
        }

        // replace multiple spaces, tabs, new lines.
        // @link https://stackoverflow.com/questions/1981349/regex-to-replace-multiple-spaces-with-a-single-space Reference.
        name = name.replace(/\s\s+/g, ' ');
        // replace space to dash (-).
        name = name.replace(/ /g, '-');

        if (alphanumOnly === true) {
            // if alpha-numeric only.
            name = name.replace(/[^a-zA-Z0-9\-_\.]/g, '');
            return name;
        }

        let pattern = [
            '$@&+', // w3 - safe
            '!*"\'(),', // w3 - extra
            '=;/#?:', // w3 - reserved
            '%', // w3 - escape
            '{}[]\\^~', // w3 - national
            '<>', // w3 - punctuation
            '|', // other unsafe characters.
        ].join('');
        let regex = new RegExp('[' + _.escapeRegExp(pattern) + ']', 'g');

        name = name.replace(regex, '');
        return name;
    }// removeUnsafeUrlCharacters


    /**
     * Strip HTML tags.
     * 
     * @link https://stackoverflow.com/a/5002618/128761 Original source code.
     * @param {string} string
     * @returns {string}
     */
    static stripTags(string) {
        let div = document.createElement('div');
        div.innerHTML = string;
        let text = div.textContent || div.innerText || '';
        return text;
    }// stripTags


    /**
     * Shorten string to limited length.
     * 
     * @link https://stackoverflow.com/a/53637828/128761 Original source code.
     * @param {string} string
     * @param {int} length
     * @returns {String}
     */
    static truncateString(string, length) {
        if (string !== null && string.length > length) {
            return string.slice(0, length) + "...";
        } else {
            return string;
        }
    }// truncateString


    /**
     * Un-escape HTML.
     * 
     * @link https://stackoverflow.com/a/34064434/128761 Original source code.
     * @param {string} string
     * @returns {string}
     */
    static unEscapeHtml(string) {
        var doc = new DOMParser().parseFromString(string, "text/html");

        if (doc && doc.documentElement) {
            return doc.documentElement.textContent;
        }
        return '';
    }// unEscapeHtml


    /**
     * Make XHR from native JS function with `Promise`.
     * 
     * @link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Spread_syntax Spread syntax.
     * @link https://flaviocopes.com/how-to-merge-objects-javascript/ Merge JS object properties using spread operator.
     * @link https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/responseType XHR response type property option.
     * @link https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/getAllResponseHeaders Get all response headers.
     * @param {object} options. Accepted options: 
     *                                          `url` The request URL.<br>
     *                                          `method` The request method. Default is 'GET'.<br>
     *                                          `data` The request data.
     *                                          `dataType` The data type such as 'json', 'html', 'xml', 'binary', 'blob', 'octet-stream', 'text', 'plain'. Default is 'json'.<br>
     *                                          `responseType` The special response type property for XHR class. Read more at https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/responseType<br>
     *                                          `accept` The custom accept type for response for 'blob', 'binary', 'octet-stream'.<br>
     *                                          `contentType` The content type of data sending to server. It can be 'application/x-www-form-urlencoded;charset=UTF-8', 'multipart/form-data' (must use with boundary), 'text/plain;charset=UTF-8'. Default is not set.<br>
     * @returns {Promise}
     */
    static XHR(options = {}) {
        let defaultOptions = {
            'url': '',
            'method': 'GET',
            'data': '',
            'dataType': 'json',
        };
        options = _.defaults(options, defaultOptions);
        defaultOptions = undefined;

        switch (options.dataType) {
            case 'html':
                options.accept = 'text/html';
                options.responseTargetType = 'response'
                break;
            case 'xml':
                options.accept = 'application/xml';
                options.responseTargetType = 'responseXML';
                break;
            case 'json':
                options.accept = 'application/json';
                options.responseTargetType = 'responseText';
                break;
            case 'binary':
            case 'blob':
            case 'octet-stream':
                if (!options.accept) {
                    options.accept = 'application/octet-stream';
                }
                options.responseTargetType = 'response';
                options.responseType = 'blob';
                break;
            case 'script':
                // @link https://stackoverflow.com/questions/23370892/type-text-ecmascript-vs-type-text-javascript xxxx/ecmascript reference.
                options.accept = 'text/javascript, application/javascript, application/ecmascript, application/x-ecmascript';
                options.responseTargetType = 'responseText';
                break;
            case 'text':
            case 'plain':
            default:
                options.accept = 'text/plain';
                options.responseTargetType = 'responseText';
                break;
        }

        let promiseObj = new Promise(function(resolve, reject) {
            let Xhr = new XMLHttpRequest();

            if (options.responseType) {
                Xhr.responseType = options.responseType;
            }

            Xhr.addEventListener('error', function(event) {
                reject({'response': '', 'status': (event.currentTarget ? event.currentTarget.status : ''), 'event': event});
            });
            Xhr.addEventListener('loadend', function(event) {
                let response = (event.currentTarget ? event.currentTarget[options.responseTargetType] : '');
                if (options.dataType === 'json') {
                    try {
                        if (response) {
                            response = JSON.parse(response);
                        }
                    } catch (exception) {
                        console.error(exception.message, response);
                    }
                }

                let headers = Xhr.getAllResponseHeaders();
                let headerMap = {};
                if (headers) {
                    let headersArray = headers.trim().split(/[\r\n]+/);
                    headersArray.forEach(function (line) {
                        let parts = line.split(': ');
                        let header = parts.shift();
                        let value = parts.join(': ');
                        headerMap[header] = value;
                    });
                    headersArray = undefined;
                }
                headers = undefined;

                if (event.currentTarget && event.currentTarget.status >= 200 && event.currentTarget.status < 300) {
                    resolve({'response': response, 'status': event.currentTarget.status, 'event': event, 'headers': headerMap});
                } else if (event.currentTarget && event.currentTarget.status >= 400 && event.currentTarget.status < 600) {
                    reject({'response': response, 'status': event.currentTarget.status, 'event': event, 'headers': headerMap});
                }
            });
            
            Xhr.open(options.method, options.url);
            Xhr.setRequestHeader('Accept', options.accept);
            if (options.contentType) {
                Xhr.setRequestHeader('Content-Type', options.contentType);
            }
            Xhr.send(options.data);
        });

        return promiseObj;
    }// XHR


}// RdbaCommon