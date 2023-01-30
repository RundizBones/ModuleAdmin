# Plugins actions order

These actions are display by execute order.

## Typical requests
These actions are called for both logged in and non logged in users.  
Not all actions are called on every pages.

| Hook name | Description |
|--------------|---------------|
| `Rdb\Modules\RdbAdmin\Controllers\BaseController->__construct.registeredAllPlugins` | Runs after registered all plugins hooks. |

### User pages

`Rdb\Modules\RdbAdmin\Controllers\Admin\ForgotLoginPassController->submitRequestAction.beforeCheckUser`  
&nbsp; &nbsp; Hook on forgot login or password page, after form validated, before check user exists.

`Rdb\Modules\RdbAdmin\Views\Admin\ForgotLoginPass\index_v.before.submitbutton`  
&nbsp; &nbsp; Hook on forgot login page, before display submit button.

`Rdb\Modules\RdbAdmin\Controllers\Admin\LoginController->doLoginAction.beforeDoLogin`  
&nbsp; &nbsp; Hook on login page, after form validated, before do login.

`Rdb\Modules\RdbAdmin\Views\Admin\Login\index_v.before.loginbutton`  
&nbsp; &nbsp; Hook on login page, before display login button.

`Rdb\Modules\RdbAdmin\Controllers\Admin\RegisterController->doRegisterAction.beforeFormValidation`  
&nbsp; &nbsp; Hook on register page, before form validation.

`Rdb\Modules\RdbAdmin\Views\Admin\Register\index_v.before.registerbutton`  
&nbsp; &nbsp; Hook on register page, before display register button.

## Admin pages
These actions are called for logged in users that is able to access **/admin** pages only. Or the pages that extends `Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController`.  
Not all actions are called on every pages.

| Hook name | Description |
|--------------|---------------|
| `Rdb\Modules\RdbAdmin\Controllers\BaseController->__construct.registeredAllPlugins` | Runs after registered all plugins hooks. |
| `Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController->__construct.adminInit` | Runs at beginning of `AdminBaseController`. |
| `Rdb\Modules\RdbAdmin\Views\common\Admin\emptyLayout_v.before_pageContent` | Hook on empty page layout, before render `$pageContent`. | 
| `Rdb\Modules\RdbAdmin\Views\common\Admin\mainLayout_v.before_pageContent` | Hook on main page layout, before render `$pageContent`. |

### XHR common data controller
`Rdb\Modules\RdbAdmin\Controllers\Admin\UI\XhrCommonDataController->getPageAlertMessages.beforeGetSession`  
&nbsp; &nbsp; Hook on XHR common pages, get alert messages, before get message from session.

`Rdb\Modules\RdbAdmin\Controllers\Admin\UI->getPageAlertMessages.afterGetSession`  
&nbsp; &nbsp; Hook on XHR common pages, get alert messages, after get message from session.

### Settings page

`Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->doUpdateAction.afterMainUpdate`  
&nbsp; &nbsp; Hook after RdbAdmin settings was updated in settings page controller.

`Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->indexAction.afterAddAssets`  
&nbsp; &nbsp; Hook after added assets (such as CSS, JS) in settings page controller.

`Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsNav.last`  
&nbsp; &nbsp; Hook after last tabs navigation (inside `<ul>` element) in settings views.

`Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsContent.tab-1`  
&nbsp; &nbsp; Hook at the bottom of tabs content in tab 1.

`Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsContent.last`  
&nbsp; &nbsp; Hook after last tabs content (inside `<div class="rd-tabs">`) in settings views.

### User pages

`Rdb\Modules\RdbAdmin\Views\Admin\Users\edit_v.bottomOtherInfo`  
&nbsp; &nbsp; Hook on edit user page, at the bottom of other info section.