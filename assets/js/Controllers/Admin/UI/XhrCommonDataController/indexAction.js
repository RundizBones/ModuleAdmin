/**
 * /ui/xhr-common-data controller. For get UI common data via Rest API.
 * 
 * This JS class file is for admin logged in pages that use common user interface.
 */


class RdbaUiXhrCommonDataController {


    /**
     * Ajax get UI common data.
     * 
     * @returns {undefined}
     */
    ajaxGetUiCommonData() {
        let $ = jQuery.noConflict();

        uiXhrCommonData = $.ajax({
            url: RdbaUIXhrCommonData.uiXhrCommonDataUrl,
            method: RdbaUIXhrCommonData.uiXhrCommonDataMethod,
            data: 'getData[]=all',
            dataType: 'json'
        });
    }// ajaxGetUiCommonData


    /**
     * Display page alert message after Ajax get UI common data.
     * 
     * @since 1.1.5
     * @private This method was called from `init()` method.
     * @param {object} response
     * @returns {undefined}
     */
    ajaxGetUiCommonDataDisplayPageAlertMessage(response) {
        if (response && typeof(response.pageAlertMessages) !== 'undefined') {
            let alertClass = '',
            alertBox = '';

            response.pageAlertMessages.forEach(function(item, index) {
                if (item.status) {
                    alertClass = RdbaCommon.getAlertClassFromStatus(item.status);
                } else {
                    alertClass = 'alert-warning';
                }
                if (item.message) {
                    alertBox += RdbaCommon.renderAlertHtml(alertClass, item.message, false);
                }
            });

            let pageAlertPlaceholder = document.querySelector('.rdba-page-alert-placeholder');
            if (pageAlertPlaceholder) {
                pageAlertPlaceholder.insertAdjacentHTML('beforeend', alertBox);
            }
        }
    }// ajaxGetUiCommonDataDisplayPageAlertMessage


    /**
     * Set response data to UI.
     * 
     * @since 1.1.5
     * @private This method was called from `init()` method.
     * @param {object} response
     * @returns {undefined}
     */
    ajaxGetUiCommonDataSetResponse(response) {
        if (typeof(response.configDb) === 'object') {
            // set site config.
            this.setSiteConfig(response.configDb);
        }

        if (typeof(response.languages) === 'object') {
            // set languages list.
            this.setLanguages(response.languages);
        }

        if (typeof(response.urlsMenuItems) === 'object') {
            // set URLs and menu items.
            this.setUrlsMenuItems(response.urlsMenuItems);
        }

        if (typeof(response.appVersion) === 'object') {
            // set app version.
            this.setAppversion(response.appVersion);
        }

        if (typeof(response.datatablesTranslation) === 'object') {
            // set datatables translation.
            datatablesTranslation = response.datatablesTranslation;
        }

        if (typeof(response.otherTranslation) === 'object') {
            rdbaOtherTranslation = response.otherTranslation;
        }
    }// ajaxGetUiCommonDataSetResponse


    /**
     * Initialize the class.
     * 
     * @since 1.1.5
     * @link https://stackoverflow.com/a/41443378/128761 Original source code of session storage expiration.
     * @link https://stackoverflow.com/a/48184777/128761 Original source code of session storage expiration.
     * @returns {undefined}
     */
    init() {
        let thisClass = this;
        let $ = jQuery.noConflict();

        let storageName = 'rdba_UiXhrCommonData';
        storageName += '_' + RdbaUIXhrCommonData.currentLanguage;
        let currentDate = new Date();
        let sessionObject = JSON.parse(sessionStorage.getItem(storageName));
        let storageData = {};
        let cacheMenuItem;

        if (sessionObject && Date.parse(currentDate) >= Date.parse(sessionObject.expires)) {
            // if session storage is expired.
            console.log('[rdba] session storage name ' + storageName + ' was expired. removing it.');
            sessionStorage.removeItem(storageName);
        } else {
            // if session storage maybe not expired.
            if (sessionObject) {
                // if session object was set and not expired, set value to variable to use later.
                storageData = sessionObject.value;
            }
        }

        if (!_.isEmpty(storageData)) {
            // if storage data was set.
            //console.log('[rdba] session storage ' + storageName + ' was set, use this to set UI.', storageData);
            // use it to render.
            this.ajaxGetUiCommonDataSetResponse(storageData.response);
        }

        /**
         * Get response object (JSON or text) from response data.
         * 
         * @param {object|string} response
         * @returns {object}
         */
        function getResponseObject(response) {
            if (typeof(response) === 'object') {
                if (typeof(response.responseJSON) !== 'undefined') {
                    response = response.responseJSON;
                } else if (typeof(response.responseText) !== 'undefined') {
                    response = response.responseText;
                }
            }
            if (typeof(response) === 'undefined' || response === null || response === '') {
                response = {};
            }

            return response;
        }// getResponseObject

        // always retrieve data from server using XHR. ---------------------------
        this.ajaxGetUiCommonData();

        uiXhrCommonData.fail(function(jqXHR, textStatus, errorThrown) {
            let response = getResponseObject(jqXHR);

            if (
                typeof (response) !== 'undefined' &&
                typeof (response.loggedIn) !== 'undefined' &&
                typeof (response.loginUrlBaseDomain) !== 'undefined' &&
                typeof (response.loginUrl) !== 'undefined' &&
                response.loggedIn === false
            ) {
                // if not logged in (response from AdminBaseController).
                // redirect to login page.
                console.log('[rdba] not logged in. redirecting to login page.');
                window.location.href = response.loginUrlBaseDomain + response.loginUrlBase + '?goback=' + encodeURI(window.location.href) + '&fastlogout=true';
            }
            // jQuery .ajax.fail will send to .always and end process.
        })// .fail
        .always(function(data, textStatus, jqXHR) {
            let response = getResponseObject(data);
            //console.log('[rdba] Ajax get UI common data completed.');

            if (typeof(response) !== 'undefined') {
                if (RdbaCommon.isset(() => response.urlsMenuItems.cacheMenuItem)) {
                    cacheMenuItem = response.urlsMenuItems.cacheMenuItem;
                }
                
                // if there is any page alert then code the display alert (dismissable = false) here.
                thisClass.ajaxGetUiCommonDataDisplayPageAlertMessage(response);
            }
            // in case of success request, .always will be called before .done.
            // in case of failed request, .always will be called and end process.
        })// .always
        .done(function(response, textStatus, jqXHR) {
            response = getResponseObject(response);

            if (cacheMenuItem === true && _.isEmpty(storageData)) {
                // if storage data is not set.
                // set session storage data here.
                let expirationMinute = 30;// expires in minutes
                let expirationDate = new Date(new Date().getTime() + (60000 * expirationMinute));
                let storageObject = {
                    'expires': expirationDate,
                    'value': {response}
                };
                sessionStorage.setItem(storageName, JSON.stringify(storageObject));
            } else if (cacheMenuItem === false) {
                // if config was set to no cache menu item.
                // session storage data for menu item must not set as well.
                storageData = {};
                sessionStorage.removeItem(storageName);
            }
            // jQuery .ajax when success request, .done will be called after .always but before .then
        })// .done
        .then(function(response) {
            if (_.isEmpty(storageData)) {
                // if storage data is not set.
                // try to set data from XHR.
                thisClass.ajaxGetUiCommonDataSetResponse(response);
            }// endif storageData

            // @todo [rdb] there is no notification system yet.
            $('#rdba-notification-navbar-list').html('');
            $('#rdba-notification-navbar').remove();

            // always set user data.
            if (typeof(response.userData) === 'object') {
                // set user data.
                thisClass.setUserData(response.userData);
            }

            return response;
        })// .then
        .then(function(response) {
            if (typeof(response.userData) === 'object' && typeof(response.urlsMenuItems) === 'object') {
                // ping logged in and online.
                thisClass.pingLogin(response.userData, response.urlsMenuItems);
            }

            return response;
        })// .then
        ;// end uiXhrCommonData .ajax
        // end always retrieve data from server using XHR. ----------------------
    }// init


    /**
     * Detect on change language on the navbar.
     * 
     * @since 1.1.5 Renamed from `onChangeLanguage()`.
     * @private This method was called from `setLanguages()`.
     * @returns {undefined}
     */
    listenOnChangeLanguageNavbar() {
        let $ = jQuery.noConflict();

        // detect on navbar listbox.
        $('#rdba-languages-navbar-list').on('click', 'a', function(e) {
            e.preventDefault();
            let thisListbox = $('#rdba-languages-navbar-list');
            let thisListItem = $(this).closest('li');// $(this) refer to a inside the list (ul).

            $.ajax({
                'url': thisListbox.data('setLanguageUrl'),
                'method': thisListbox.data('setLanguageMethod'),
                data: 'currentUrl=' + encodeURIComponent(RdbaUIXhrCommonData.currentUrl) 
                    + '&rundizbones-languages=' + encodeURIComponent(thisListItem.data('locale'))
                    + '&currentLanguageID=' + thisListbox.data('currentLanguage'),
                dataType: 'json'
            })
            .done(function(data, textStatus, jqXHR) {
                let response = data;

                if (typeof(response.redirectUrl) !== 'undefined') {
                    window.location.href = response.redirectUrl;
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('[rdba] ', jqXHR);
            })
        });
    }// listenOnChangeLanguageNavbar


    /**
     * Mark current menu item on matched current link.
     * 
     * @private This method was called from `renderMenuItems()`.
     * @param {object} MenuItems
     * @returns {undefined}
     */
    markCurrentMenuItem(MenuItems) {
        let $ = jQuery.noConflict();
        let thisClass = this;

        //console.debug('[rdba] current URL in `RdbaUIXhrCommonData`: ', RdbaUIXhrCommonData.currentUrl);
        //console.debug('[rdba] current URL raw in `RdbaUIXhrCommonData`: ', RdbaUIXhrCommonData.currentUrlRaw);

        $.each(MenuItems, function(indexMenu, item) {
            if (
                typeof(RdbaUIXhrCommonData.currentUrl) !== 'undefined' && 
                item.link == RdbaUIXhrCommonData.currentUrl
            ) {
                $('#rdba-sidebar-menu-item-link_' + item.id).addClass('current currentlink-match-currenturl');
            } else if (
                typeof(RdbaUIXhrCommonData.currentUrlRaw) !== 'undefined' && 
                item.link == RdbaUIXhrCommonData.currentUrlRaw
            ) {
                $('#rdba-sidebar-menu-item-link_' + item.id).addClass('current currentlink-match-currenturlraw');
            } else if (
                typeof(RdbaUIXhrCommonData.currentUrl) !== 'undefined' && 
                typeof(item.linksCurrent) !== 'undefined'
            ) {
                $.each(item.linksCurrent, function(indexLinksCurrent, eachLink) {
                    let currentUrlNoQuerystring = (RdbaUIXhrCommonData.currentUrl.split('?') ? RdbaUIXhrCommonData.currentUrl.split('?')[0] : RdbaUIXhrCommonData.currentUrl);
                    if (thisClass.matchUrlRule(currentUrlNoQuerystring, eachLink)) {
                        $('#rdba-sidebar-menu-item-link_' + item.id).addClass('current linkscurrent-match-currenturl');
                    }
                });
            }

            if (typeof(item.subMenu) !== 'undefined') {
                thisClass.markCurrentMenuItem(item.subMenu);
            }
        });
    }// markCurrentMenuItem


    /**
     * Match URL rule.
     * 
     * @private This method was called from `markCurrentMenuItem()`.
     * @param {string} url The URL.
     * @param {string} rule The rule, for example: /admin/users/* will be match /admin/users/edit, /admin/users/edit/2 but NOT /admin/users
     * @returns {Boolean} Return true on success, false on failure.
     */
    matchUrlRule(url, rule) {
        return new RegExp("^" + rule.split("*").join(".*") + "$").test(url);
    }// matchUrlRule


    /**
     * Ping user logged in and online.
     * 
     * Ping to server every 'x' seconds to notice that this user is online and logged in.<br>
     * If user is logged out for any reason then redirect to login page.
     * 
     * @param {object} userData The user data get from /admin/ui/xhr-common-data ajax. 
     * @param {object} urlsMenuItems The URLs menu items.
     * @returns {undefined}
     */
    pingLogin(userData, urlsMenuItems) {
        let $ = jQuery.noConflict();
        let thisClass = this;

        let intervalId = window.setInterval(
            function() {
                ajaxPingLogin(userData, urlsMenuItems);
            }, 
            60000// (xxxx/1000) = xx seconds
        );

        function ajaxPingLogin(userData, urlsMenuItems) {
            $.ajax({
                url: urlsMenuItems.urls.adminHome + '/users/' + userData.user_id + '/sessions/ping',
                method: 'GET',
                dataType: 'json',
                headers: {
                    'sessionKey': userData.userlogin_session_key,
                    'rundizbones-no-profiler': 'true',
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

                rdbaUserLoggedIn = false;

                clearInterval(intervalId);
                console.error('[rdba] stop pinging.');
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
                        let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage, false);
                        $('.rdba-page-alert-placeholder').append(alertBox);
                    }
                }
            })// .always
            ;
        }
    }// pingLogin


    /**
     * Recursive set current menu until root.
     * 
     * This method will be trying to call render breadcrumb if it was not set via controller.
     * 
     * @private This method was called from `renderMenuItems()`.
     * @returns {undefined}
     */
    recursiveSetCurrentUp() {
        let $ = jQuery.noConflict();
        let $breadcrumb = new Object();
        let bci = 0;
        let stopBreadcrumb = false;
        $breadcrumb.data = [];

        let $currentA = $('.sm-rdta.rd-sidebar-item-list').find('a.current');

        $.each($currentA, function(aIndex, aElement) {
            let $parentsLi = $(aElement).parents('li');

            if (typeof($parentsLi) === 'object') {
                $.each($parentsLi, function(index, liElement) {
                    $(liElement).children('a').addClass('current rdba-recursive-set-current');

                    let $thisLink = $(liElement).children('a').attr('href');

                    if (
                        stopBreadcrumb === false && 
                        (
                            typeof(RdbaUIXhrCommonData.breadcrumbBased) !== 'undefined' &&
                            RdbaUIXhrCommonData.breadcrumbBased[0] !== $thisLink
                        )
                    ) {
                        // if enough of breadcrumb.
                        $breadcrumb.data[bci] = [];
                        $breadcrumb.data[bci]['name'] = $(liElement).children('a').text();
                        $breadcrumb.data[bci]['link'] = $thisLink;
                    }
                    bci++;
                });// end .each for parents li of a.current.
            }

            if ($breadcrumb.data.length > 0) {
                stopBreadcrumb = true;
            }
        });// end .each for a.current.

        $breadcrumb.data.reverse();
        this.renderBreadcrumb($breadcrumb.data);
    }// recursiveSetCurrentUp


    /**
     * Render breadcrumb.
     * 
     * If breadcrumb did not set via controller, this method will set it from current deepest menu item up to root.
     * 
     * @private This method was called from `recursiveSetCurrentUp()`.
     * @param {array} breadcrumb
     * @returns {undefined}
     */
    renderBreadcrumb(breadcrumb) {
        let $ = jQuery.noConflict();

        if ($('.rd-breadcrumb').html().trim().length === 0) {
            // if breadcrumb did not set via controller (html.length is 0).
            if (typeof(breadcrumb) === 'object' && jQuery.isArray(breadcrumb)) {
                let breadcrumbHtmlObject = $('<ul class="rd-breadcrumb"></ul>');

                if (typeof(RdbaUIXhrCommonData.breadcrumbBased) !== 'undefined') {
                    breadcrumbHtmlObject.append("\n"+'<li><a href="'+RdbaUIXhrCommonData.breadcrumbBased[0].trim()+'">'+RdbaUIXhrCommonData.breadcrumbBased[1].trim()+'</a></li>'+"\n");
                }

                if (breadcrumb.length > 0) {
                    $.each(breadcrumb, function(index, data) {
                        if (typeof(data) !== 'undefined') {
                            breadcrumbHtmlObject.append('<li><a href="'+data.link.trim()+'">'+data.name.trim()+'</a></li>'+"\n");
                        }
                    });
                }

                breadcrumbHtmlObject.find('li').last().addClass('current');
                breadcrumbHtmlObject.find('a').last().removeAttr('href');
                $('.rd-breadcrumb').replaceWith(breadcrumbHtmlObject[0].outerHTML);
            } else {
                $('.rd-breadcrumb').remove();
            }
        }
    }// renderBreadcrumb


    /**
     * Render menu items.
     * 
     * This method mark current in menu item or sub menu item up to root.<br>
     * It is also set the breadcrumb from deepest menu item (If the breadcrumb did not set via controller).
     * 
     * @private This method was called from `setUrlsMenuItems()`.
     * @param {object} urlsMenuItems
     * @returns {undefined}
     */
    renderMenuItems(urlsMenuItems) {
        let $ = jQuery.noConflict();

        // reset sidebar menu items
        $('.sm-rdta.rd-sidebar-item-list').html('');
        // prepare template.
        let sidebarElement = document.getElementById('rdba-sidebar-menu-items');
        if (sidebarElement) {
            let source = sidebarElement.innerHTML;

            Handlebars.registerHelper('ifEquals', function (v1, v2, options) {
                if (v1 === v2) {
                    return options.fn(this);
                }
                return options.inverse(this);
            });

            let template = Handlebars.compile(source);
            let htmlRendered = template(urlsMenuItems);
            $('.sm-rdta.rd-sidebar-item-list').append(htmlRendered);

            // mark current menu item.
            this.markCurrentMenuItem(urlsMenuItems.menuItems);
            // recursive mark current item up to root.
            this.recursiveSetCurrentUp();

            // check sub menu must exists in main menu container otherwise remove it.
            let allMenuContainer = document.querySelectorAll('.rd-sidebar-item-list [data-mainmenucontainer="true"]');
            if (allMenuContainer && _.isObject(allMenuContainer)) {
                allMenuContainer.forEach(function(item, index) {
                    let subMenu = item.querySelector('ul');
                    if (!subMenu) {
                        // if this menu container (main menu item) has no sub menu.
                        // remove this main manu item.
                        item.remove();
                    }
                });
            }
        }
    }// renderMenuItems


    /**
     * Set app version to footer.
     * 
     * @param {object} appVersion
     * @returns {undefined}
     */
    setAppversion(appVersion) {
        let appNameElement = document.querySelector('.rdba-app-name');
        if (appNameElement) {
            appNameElement.innerHTML = appVersion.name;
        }
        let appVersionElement = document.querySelector('.rdba-app-version');
        if (appVersionElement) {
            appVersionElement.innerHTML = appVersion.version;
        }
    }// setAppversion


    /**
     * Set languages to switcher.
     * 
     * @param {object} languages The languages object get from ajax `response.languages`.
     * @returns {undefined}
     */
    setLanguages(languages) {
        let $ = jQuery.noConflict();

        if (typeof(languages.languages) !== 'undefined') {
            // reset the list (ul).
            $('#rdba-languages-navbar-list').html('');
            // prepare template.
            let navbarElement = document.getElementById('rdba-languages-navbar-item');
            if (navbarElement) {
                let source = navbarElement.innerHTML;

                Handlebars.registerHelper('ifEquals', function (v1, v2, options) {
                    if (v1 === v2) {
                        return options.fn(this);
                    }
                    return options.inverse(this);
                });

                let template = Handlebars.compile(source);
                let htmlRendered = template(languages);
                $('#rdba-languages-navbar-list').append(htmlRendered);

                // set data attribute to language list (ul).
                // it must set both `.data()` and `.attr()`. See reference https://stackoverflow.com/a/26022907/128761
                $('#rdba-languages-navbar-list')
                    .attr('data-defaultLanguage', languages.defaultLanguage)
                    .attr('data-currentLanguage', languages.currentLanguage)
                    .attr('data-languageDetectMethod', languages.languageDetectMethod)
                    .attr('data-languageUrlDefaultVisible', languages.languageUrlDefaultVisible)
                    .attr('data-setLanguageMethod', languages.setLanguage_method)
                    .attr('data-setLanguageUrl', languages.setLanguage_url)
                    .data('defaultLanguage', languages.defaultLanguage)
                    .data('currentLanguage', languages.currentLanguage)
                    .data('languageDetectMethod', languages.languageDetectMethod)
                    .data('languageUrlDefaultVisible', languages.languageUrlDefaultVisible)
                    .data('setLanguageMethod', languages.setLanguage_method)
                    .data('setLanguageUrl', languages.setLanguage_url);

                $('.sm-rdta.navbar').smartmenus('refresh');
                this.listenOnChangeLanguageNavbar();
            }
        }
    }// setLanguages


    /**
     * Set site config.
     * 
     * @param {object} configDb The site config object get from ajax `response.configDb`.
     * @returns {undefined}
     */
    setSiteConfig(configDb) {
        let $ = jQuery.noConflict();

        $('.rd-site-brand a').html(configDb.rdbadmin_SiteName);
    }// setSiteConfig


    /**
     * Set URLs and menu items.
     * 
     * @param {object} urlsMenuItems The URLs and menu items object get from ajax `response.urlsMenuItems`.
     * @returns {undefined}
     */
    setUrlsMenuItems(urlsMenuItems) {
        let $ = jQuery.noConflict();

        $('.rd-site-brand a').attr('href', urlsMenuItems.urls.adminHome);

        // destroy menu items (sidebar) before add and then re-activate again.
        $('.sm-rdta.sm-vertical').off('show.smapi hideAll.smapi');// turn off events in hotfix long sidebar sub menus.
        $('.sm-rdta.sm-vertical').smartmenus('destroy');

        // render menu items.
        this.renderMenuItems(urlsMenuItems);

        // activate smartmenus again with manually set options.
        // see reference at rundiz-template-for-admin/assets/js/rdta/rundiz-template-admin.js in `RundizTemplateAdmin.smartMenusSidebar()`
        $('.sm-rdta.sm-vertical').smartmenus({
            markCurrentItem: false,// override value on rundiz-template-admin to let's this function manually set current.
            markCurrentTree: false,// override value on rundiz-template-admin to let's this function manually set current.
            noMouseOver: true,
            subIndicatorsPos: 'append',
            subMenusSubOffsetY: -1,// border top contain 1 px
        });

        let rdtaClass = new RundizTemplateAdmin();
        // hotfix long sidebar sub menus.
        rdtaClass.hotfixLongSidebarSubmenus();
    }// setUrlsMenuItems


    /**
     * Set user data on navbar.
     * 
     * @param {object} userData The user data object get from ajax `response.userData`.
     * @returns {undefined}
     */
    setUserData(userData) {
        let $ = jQuery.noConflict();

        RdbaUIXhrCommonData.userData = userData;

        let navbarElement = document.getElementById('rdba-user-navbar-items');
        if (navbarElement) {
            let source = navbarElement.innerHTML;
            let template = Handlebars.compile(source);
            let htmlRendered = template(userData);

            $('#rdba-user-navbar ul').html(htmlRendered);

            if (userData.user_avatar) {
                let avatarUrl;
                if (userData.user_avatar.indexOf('//') !== -1) {
                    avatarUrl = userData.user_avatar;
                } else {
                    avatarUrl = userData.UrlAppBased + '/' + userData.user_avatar;
                }
                $('#rdba-user-navbar .rdba-user-icon').replaceWith('<img class="display-picture rdba-user-profilepicture" src="' + avatarUrl + '" alt="">');
            }

            $('.sm-rdta.navbar').smartmenus('refresh');
        }
    }// setUserData


}// RdbaUiXhrCommonDataController


// define variable for global use with other JS.
// rdbaUserLoggedIn is for check that it is `true` (logged in) or `false` (not logged in).
var rdbaUserLoggedIn = true;
var uiXhrCommonData;
var datatablesTranslation = {};
var rdbaOtherTranslation = {};


document.addEventListener('DOMContentLoaded', function() {
    let $ = jQuery.noConflict();
    let rdbaUiXhrCommonDataClass = new RdbaUiXhrCommonDataController;
    // initialize the class.
    rdbaUiXhrCommonDataClass.init();

}, false);