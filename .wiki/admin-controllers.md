# Admin (back-end) controller
Before you continue on this document, please read about **Controller and route** on [front-end document][1] first.

Admin controller is needed for logged in users only.

## Create controller
The controller class should extends `\Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController` to make it check for logged in every request.
This `AdminBaseController` is extended on `\Rdb\Modules\RdbAdmin\Controllers\BaseController`.

Example:

```php
<?php

namespace Rdb\Modules\ModuleName\Controllers;

class MyAdminController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{
    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;

    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        // bind text domain file and you can use translation with functions that work for specific domain such as `d__()`.
        $this->Languages->bindTextDomain(
            'modulename', 
            MODULE_PATH . DIRECTORY_SEPARATOR . 'ModuleName' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('modulename', 'My module admin');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            // get RdbAdmin module's assets data for render page correctly.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            // Assets class for add CSS and JS.
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            // add CSS and JS assets to make basic functional and style on admin page works correctly.
            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['pageContent'] = $this->Views->render('Admin/Index/index_v', $output);

            unset($Assets, $rdbAdminAssets);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }
}
```

### Required `$output` keys

* `pageHtmlTitle` - Should have (string value). This is page title in `<title>` HTML element.
* `Assets` - Must have (`\Rdb\Modules\RdbAdmin\Libraries\Assets` class value). For main layout use as assets (CSS, JS) renderer.
* `pageContent` - Must have (string value). Your views must render into this output data so it can be display properly in admin page.

### More features

There are more features such as permission check, add asset files (CSS, JS), etc. 

#### Permissions
Please read on **Modules/RdbAdmin/Interfaces/ModuleAdmin.php** file and `definePermissions()` method about define permission.
Once permission was defined then you can use `$this->checkPermission('ModuleName', 'PermissionPage', ['permissionAction']);` in your controller.

#### Assets
Please read on **Modules/RdbAdmin/Libraries/Assets.php** file and `addMultipleAssets()` method about format of assets data for your module.
Once you write your module's assets data then you can add multiple assets at once using `$Assets->addMultipleAssets('css', ['my-css-handle'], $myModuleAssetsData);` for CSS 
and `$Assets->addMultipleAssets('js', ['my-js-handle'], $myModuleAssetsData);` for JS.

---

Read more about [views for admin][2] controller.


[1]: frontend-controllers.md
[2]: admin-views.md