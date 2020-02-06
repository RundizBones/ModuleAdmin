# Front-end controller.
Front-end controller is no login needed, anyone can access it.  

## Controller and route
Your controller in the **config/routes.php** should be `\\Modules\\ModuleName\\Controllers\\MyFront:index`
and the controller file should be in **Modules/ModuleName/Controllers/MyFrontController.php**.<br>
The controller file can be anywhere, it will be loaded using PSR-4 autoload. 
The controller file must have **Controller** suffix in the file name and its class.<br>
The controller action (method) must have **Action** suffix, in this case it should be `indexAction()`.

## Create controller
The controller class should extends `\Modules\RdbAdmin\Controllers\BaseController` to easily access `Languages`, `Input` properties and some methods that might be necessary for your code.

Example:

```php
<?php

namespace Modules\ModuleName\Controllers;

class MyFrontController extends \Modules\RdbAdmin\Controllers\BaseController
{
    public function indexAction(): string
    {
        // bind text domain file and you can use translation with functions that work for specific domain such as `d__()`.
        $this->Languages->bindTextDomain(
            'modulename', 
            MODULE_PATH . DIRECTORY_SEPARATOR . 'ModuleName' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
        $this->Languages->getHelpers();

        $output = [];
        $output['pageTitle'] = d__('modulename', 'My module front-end');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            // add asset file (CSS, JS) only if you have to use it.
            $Assets = new \Modules\RdbAdmin\Libraries\Assets($this->Container);
            $Url = new \System\Libraries\Url($this->Container);
            $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
            unset($Url);

            $Assets->addAsset('css', 'modulename', $publicModuleUrl . '/assets/css/my-module-style.css');

            $output['Assets'] = $Assets;

            unset($Assets);
            // end add asset file.

            return $this->Views->render('FrontEnd/Index/index_v', $output);
        }
    }
}
```

### Create views
This document is not focus on views but front-end views can design freely, different from admin page that needs more regulation. So, this example is only to show what should be used.

Example:

```php
<?php
// Begins doc helper ------------------------------------------------------------------------
// These variable doc is useful in IDE such as NetBeans.
// You can follow to the class very easy by control and click on the class name in the line @var.
// Some of these variables also useful while you writing the code in the IDE, it will be show the drop down helper for you.

/* @var $Assets \Modules\RdbAdmin\Libraries\Assets */
// End doc helper ---------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html class="rd-template-admin<?php if (isset($pageHtmlClasses)) {echo ' ' . $pageHtmlClasses;} ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php
        if (isset($pageHtmlTitle) && is_scalar($pageHtmlTitle)) {
            echo htmlspecialchars($pageHtmlTitle, ENT_QUOTES);
        }
        ?></title>

        <?php echo $Assets->renderAssets('css'); ?> 
    </head>
    <body ontouchstart="">
        <h1><?=$pageTitle; ?></h1>
        <p>
            <?php echo d__('modulename', 'Hello world.'); ?>
        </p>
        <?php echo $Assets->renderAssets('js'); ?> 
    </body>
</html>
```