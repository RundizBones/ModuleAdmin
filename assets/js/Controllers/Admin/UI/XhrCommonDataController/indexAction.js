/**
 * /ui/xhr-common-data controller. For get UI common data via Rest API.
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
     * Mark current menu item on matched current link.
     * 
     * This method was called from `renderMenuItems()`.
     * 
     * @private For access from other method in this class.
     * @param {object} MenuItems
     * @returns {undefined}
     */
    markCurrentMenuItem(MenuItems) {
        let $ = jQuery.noConflict();
        let thisClass = this;

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
                //console.log(item.linksCurrent);
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
     * This method was called from `markCurrentMenuItem()`.
     * 
     * @private For access from other method in this class.
     * @param {string} url The URL.
     * @param {string} rule The rule, for example: /admin/users/* will be match /admin/users/edit, /admin/users/edit/2
     * @returns {Boolean} Return true on success, false on failure.
     */
    matchUrlRule(url, rule) {
        return new RegExp("^" + rule.split("*").join(".*") + "$").test(url);
    }// matchUrlRule


    /**
     * Detect on change language on the navbar.
     * 
     * @returns {undefined}
     */
    onChangeLanguage() {
        let $ = jQuery.noConflict();

        // detect on navbar listbox.
        $('#rdba-languages-navbar-list').on('click', 'a', function(e) {
            e.preventDefault();
            let thisListbox = $('#rdba-languages-navbar-list');
            let thisListItem = $(this).closest('li');// $(this) refer to a inside the list (ul).

            $.ajax({
                'url': thisListbox.data('setLanguageUrl'),
                'method': thisListbox.data('setLanguageMethod'),
                data: 'currentUrl=' + RdbaUIXhrCommonData.currentUrl + '&rundizbones-languages=' + thisListItem.data('locale'),
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
        });
    }// onChangeLanguage


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
                    'sessionKey': userData.userlogin_session_key
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
                console.error('stop pinging.');
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
     * This method was called from `renderMenuItems()`.
     * 
     * @private For access from other method in this class.
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
     * This method was called from `recursiveSetCurrentUp()`.
     * 
     * @private For access from other method in this class.
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
     * This method was called from `setUrlsMenuItems()`.
     * 
     * @private For access from other method in this class.
     * @param {object} urlsMenuItems
     * @returns {undefined}
     */
    renderMenuItems(urlsMenuItems) {
        let $ = jQuery.noConflict();

        // reset sidebar menu items
        $('.sm-rdta.rd-sidebar-item-list').html('');
        // prepare template.
        let source = document.getElementById('rdba-sidebar-menu-items').innerHTML;

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
    }// renderMenuItems


    /**
     * Set app version to footer.
     * 
     * @param {object} appVersion
     * @returns {undefined}
     */
    setAppversion(appVersion) {
        let $ = jQuery.noConflict();

        $('.rdba-app-name').html(appVersion.name);
        $('.rdba-app-version').html(appVersion.version);
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
            let source = document.getElementById('rdba-languages-navbar-item').innerHTML;

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
            this.onChangeLanguage();
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

        let source = document.getElementById('rdba-user-navbar-items').innerHTML;
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
            $('#rdba-user-navbar .rdba-user-icon').replaceWith('<img class="display-picture" src="' + avatarUrl + '" alt="">');
        }

        $('.sm-rdta.navbar').smartmenus('refresh');
    }// setUserData


}// RdbaUiXhrCommonDataController


// define variable for global use with other JS.
// rdbaUserLoggedIn is for check that it is `true` (logged in) or `false` (not logged in).
var rdbaUserLoggedIn = true;
var uiXhrCommonData;
var datatablesTranslation = {};


document.addEventListener('DOMContentLoaded', function() {
    let $ = jQuery.noConflict();
    let rdbaUiXhrCommonData = new RdbaUiXhrCommonDataController;
    //let xhrDeferred;

    // ajax get UI common data.
    rdbaUiXhrCommonData.ajaxGetUiCommonData();
    uiXhrCommonData.fail(function(jqXHR, textStatus, errorThrown) {
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

        if (
            typeof(response) !== 'undefined' && 
            typeof(response.loggedIn) !== 'undefined' && 
            typeof(response.loginUrlBaseDomain) !== 'undefined' &&
            typeof(response.loginUrl) !== 'undefined' &&
            response.loggedIn === false
        ) {
            // if not logged in (response from AdminBaseController).
            // redirect to login page.
            console.log('not logged in. redirecting to login page.');
            window.location.href = response.loginUrlBaseDomain + response.loginUrlBase + '?goback=' + encodeURI(window.location.href) + '&fastlogout=true';
        }
    })// .fail, it is end here if failed.
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
        
        // if there is any page alert then code the display alert (dismissable = false) here.
        if (typeof(response) !== 'undefined') {
            if (typeof(response.pageAlertMessages) !== 'undefined') {
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
                document.querySelector('.rdba-page-alert-placeholder').insertAdjacentHTML('beforeend', alertBox);
            }
        }
    })// .always
    .done(function(response, textStatus, jqXHR) {
        // if there is anything to do before `.then()` when ajax was successfully, write it here.
    })// .done, it will be called if done and be called before `.then()`.
    .then(function(response) {
        if (typeof(response.configDb) === 'object') {
            // set site config.
            rdbaUiXhrCommonData.setSiteConfig(response.configDb);
        }

        if (typeof(response.languages) === 'object') {
            // set languages list.
            rdbaUiXhrCommonData.setLanguages(response.languages);
        }

        // @todo [rdb] there is no notification system yet.
        $('#rdba-notification-navbar-list').html('');
        $('#rdba-notification-navbar').remove();

        if (typeof(response.userData) === 'object') {
            // set user data.
            rdbaUiXhrCommonData.setUserData(response.userData);
        }

        if (typeof(response.urlsMenuItems) === 'object') {
            // set URLs and menu items.
            rdbaUiXhrCommonData.setUrlsMenuItems(response.urlsMenuItems);
        }

        if (typeof(response.appVersion) === 'object') {
            // set app version.
            rdbaUiXhrCommonData.setAppversion(response.appVersion);
        }

        if (typeof(response.datatablesTranslation) === 'object') {
            datatablesTranslation = response.datatablesTranslation;
        }

        return response;
    })
    .then(function(response) {
        if (typeof(response.userData) === 'object' && typeof(response.urlsMenuItems) === 'object') {
            // ping logged in and online.
            rdbaUiXhrCommonData.pingLogin(response.userData, response.urlsMenuItems);
        }

        return response;
    })
    //.then(function() {
        //var d = new $.Deferred();
        //setTimeout(function() {
            // do something
            //d.resolve('done');
        //}(xhrDeferred), 1000);
        //return d.promise();
    //})// an example of using deferred with normal function that is not ajax.
    ;
    // end ajax get common UI data.

}, false);