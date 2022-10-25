<?php
/** 
 * @license http://opensource.org/licenses/MIT MIT
 */


/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


$Rc->addGroup('/admin', function(\FastRoute\RouteCollector $Rc) {
    // /admin page (admin dashboard).
    $Rc->addRoute($this->filterMethod('any'), '', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Index:index');

    // UI data. --------------------------------------------------------------------------------------
    // /admin/ui/xhr-common-data REST API (get user interface data for common pages).
    $Rc->addRoute('GET', '/ui/xhr-common-data', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\UI\\XhrCommonData:index');

    // /admin/ui/xhr-dashboard-widgets REST API (get admin dashboard widgets HTML).
    $Rc->addRoute('GET', '/ui/xhr-dashboard-widgets', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\UI\\XhrDashboardWidgets:index');
    // /admin/ui/xhr-dashboard-widgets REST API (update widgets order).
    $Rc->addRoute('PATCH', '/ui/xhr-dashboard-widgets', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\UI\\XhrDashboardWidgets:saveOrder');
    // end UI data. ---------------------------------------------------------------------------------

    // users management. -------------------------------------------------------------------------
    // /admin/users page + REST API (users listing page - get users data via REST).
    $Rc->addRoute('GET', '/users', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Users:index');
    // /admin/users/xx REST API (get a single user data).
    $Rc->addRoute('GET', '/users/{id:\d+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Users:doGetUser');

    // /admin/users/add page.
    $Rc->addRoute('GET', '/users/add', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Add:index');
    // /admin/users REST API (add a user).
    $Rc->addRoute('POST', '/users', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Add:doAdd');

    // /admin/users/edit[/xx], /admin/users/edit page (edit selected user or edit self).
    $Rc->addRoute('GET', '/users/edit[/{id:\d+}]', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Edit:index');
    // /admin/users/xx REST API (update a user).
    $Rc->addRoute('PATCH', '/users/{id:\d+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Edit:doUpdate');

    // /admin/users/xx/avatar REST API.
    $Rc->addRoute('POST', '/users/{id:\d+}/avatar', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Avatar:upload');
    // /admin/users/xx/avatar REST API.
    $Rc->addRoute('DELETE', '/users/{id:\d+}/avatar', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Avatar:delete');

    // /admin/users/actions page (bulk actions confirmation).
    $Rc->addRoute('GET', '/users/actions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Actions:index');
    // /admin/users/actions/xx REST API (update users).
    $Rc->addRoute('PATCH', '/users/actions/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Actions:doUpdate');
    // /admin/users/xx REST API (delete users - use comma for multiple users).
    $Rc->addRoute('DELETE', '/users/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Actions:doDelete');

    // /admin/users/delete/me page (delete self confirmation).
    $Rc->addRoute('GET', '/users/delete/me', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Actions:deleteMe');
    // /admin/users REST API (delete self).
    $Rc->addRoute('DELETE', '/users', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Actions:doDeleteMe');

    // /admin/users/xx/previous-emails page + REST API (list previous emails page - get data via REST).
    $Rc->addRoute('GET', '/users/{id:\d+}/previous-emails', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\PreviousEmails:index');

    // user login sessions. ------------------------------------------
    // /admin/users/xx/sessions page + REST API (user logins sessions page - get data via REST).
    $Rc->addRoute('GET', '/users/{id:\d+}/sessions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Sessions\\Sessions:index');

    // /admin/users/xx/sessions REST API (delete login sessions).
    $Rc->addRoute('DELETE', '/users/{id:\d+}/sessions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Sessions\\Sessions:doDelete');

    // /admin/users/xx/sessions/ping page (ping for check user logged in, can access publicy).
    $Rc->addRoute('GET', '/users/{id:\d+}/sessions/ping', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Users\\Sessions\\Ping:index');
    // end user login sessions. --------------------------------------
    // end users management. ---------------------------------------------------------------------

    // roles management. --------------------------------------------------------------------------
    // /admin/roles page + REST API (roles listing page - get roles data via REST).
    $Rc->addRoute('GET', '/roles', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Roles:index');
    // /admin/roles/xx REST API (get a single role data).
    $Rc->addRoute('GET', '/roles/{id:\d+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Roles:doGetRole');

    // /admin/roles/reorder REST API (update role priority).
    $Rc->addRoute('PATCH', '/roles/reorder', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Reorder:index');

    // /admin/roles/add page.
    $Rc->addRoute('GET', '/roles/add', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Add:index');
    // /admin/roles REST API (add a role).
    $Rc->addRoute('POST', '/roles', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Add:doAdd');

    // /admin/roles/edit/xx page.
    $Rc->addRoute('GET', '/roles/edit/{id:\d+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Edit:index');
    // /admin/roles REST API (edit a role).
    $Rc->addRoute('PATCH', '/roles/{id:\d+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Edit:doUpdate');

    // /admin/roles/actions page (bulk actions confirmation).
    $Rc->addRoute('GET', '/roles/actions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Actions:index');
    // /admin/roles/xx REST API (delete roles - use comma for multiple roles).
    $Rc->addRoute('DELETE', '/roles/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Roles\\Actions:doDelete');
    // end roles management. ---------------------------------------------------------------------

    // permissions management. ------------------------------------------------------------------
    // /admin/permissions page + REST API (permissions listing page - get permissions data via REST).
    $Rc->addRoute('GET', '/permissions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Permissions\\Permissions:index');

    // /admin/permissions REST API (edit permission).
    $Rc->addRoute('PATCH', '/permissions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Permissions\\Edit:doUpdate');

    // /admin/permissions/xx REST API (clear permissions for module).
    $Rc->addRoute('DELETE', '/permissions/{module_system_name}', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Permissions\\Edit:doClear');
    // end permissions management. -------------------------------------------------------------

    // modules management. ----------------------------------------------------------------------
    // modules management page.
    $Rc->addRoute('GET', '/modules', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Modules\\Modules:index');
    $Rc->addRoute('PATCH', '/modules/actions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Modules\\Actions:doUpdate');

    // modules plugins management.
    $Rc->addRoute('GET', '/modules/plugins', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Modules\\Plugins\\Plugins:index');
    $Rc->addRoute('PATCH', '/modules/plugins/actions', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Modules\\Plugins\\Actions:doUpdate');

    // modules assets management.
    $Rc->addRoute('GET', '/modules/assets', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Modules\\Assets\\Assets:index');
    $Rc->addRoute('POST', '/modules/assets', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Modules\\Assets\\Publish:doPublish');
    // end modules management. -----------------------------------------------------------------

    // settings (config) management. -------------------------------------------------------------
    // /admin/settings page + REST API (settings page - get data via REST).
    $Rc->addRoute('GET', '/settings', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Settings\\Settings:index');

    // /admin/settings REST API (edit settings).
    $Rc->addRoute('PATCH', '/settings', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Settings\\Settings:doUpdate');

    // /admin/settings/test-smtp REST API.
    $Rc->addRoute('POST', '/settings/test-smtp', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Settings\\Settings:testSmtp');

    // /admin/settings/favicon REST API (upload, change new favicon).
    $Rc->addRoute('POST', '/settings/favicon', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Settings\\Favicon:update');
    $Rc->addRoute('DELETE', '/settings/favicon', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Settings\\Favicon:delete');
    // end settings (config) management. --------------------------------------------------------

    // admin tools. ----------------------------------------------------------------------------------
    // /admin/tools/cache page + REST API (tools cache page - get data via REST).
    $Rc->addRoute('GET', '/tools/cache', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Tools\\Cache:index');

    // /admin/tools/cache REST API (clear cache).
    $Rc->addRoute('DELETE', '/tools/cache', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Tools\\Cache:clear');

    // /admin/tools/email-tester page.
    $Rc->addRoute('GET', '/tools/email-tester', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Tools\\EmailTester:index');

    // /admin/tools/email-tester REST API (submit test).
    $Rc->addRoute('POST', '/tools/email-tester', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Tools\\EmailTester:doSendEmail');
    // end admin tools. -----------------------------------------------------------------------------

    // the routes that can access publicy. ---------------------------------------------------------
    // /admin/login page.
    $Rc->addRoute('GET', '/login', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Login:index');
    // /admin/login REST API (login).
    $Rc->addRoute('POST', '/login', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Login:doLogin');
    // /admin/login/reset page.
    $Rc->addRoute('GET', '/login/reset', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Login:reset');
    // /admin/login/reset REST API (login).
    $Rc->addRoute('POST', '/login/reset', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Login:doLoginReset');
    // /admin/login/2fa page.
    $Rc->addRoute('GET', '/login/2fa', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Login:mfa');
    // /admin/login/2fa REST API (2 step verification).
    $Rc->addRoute('POST', '/login/2fa', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Login:doMfa');

    // /admin/forgot-login-password page (forgot username or password page).
    $Rc->addRoute('GET', '/forgot-login-password', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\ForgotLoginPass:index');
    // /admin/forgot-login-password REST API (submit request a password reset).
    $Rc->addRoute('POST', '/forgot-login-password', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\ForgotLoginPass:submitRequest');

    // /admin/forgot-login-password/reset page (reset password form).
    $Rc->addRoute('GET', '/forgot-login-password/reset', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\ForgotLoginPass:reset');
    // /admin/forgot-login-password/reset REST API (submit change new password).
    $Rc->addRoute('POST', '/forgot-login-password/reset', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\ForgotLoginPass:submitReset');

    // /admin/register page.
    $Rc->addRoute('GET', '/register', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Register:index');
    // /admin/register REST API (register a new user).
    $Rc->addRoute('POST', '/register', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Register:doRegister');

    // /admin/register/confirm page (confirm register page).
    $Rc->addRoute('GET', '/register/confirm', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Register:confirm');
    // /admin/register/confirm REST API (do confirm register).
    $Rc->addRoute('POST', '/register/confirm', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Register:doConfirm');

    // /admin/logout page.
    $Rc->addRoute('GET', '/logout', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Logout:index');
    // /admin/logout REST API (logout).
    $Rc->addRoute('DELETE', '/logout', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Logout:doLogout');

    // /admin/cron page.
    $Rc->addRoute('GET', '/cron', '\\Rdb\\Modules\\RdbAdmin\\Controllers\\Admin\\Cron:index');
    // end the routes that can access publicy. -----------------------------------------------------
});
