<?php
// Begins doc helper ------------------------------------------------------------------------
// These variable doc is useful in IDE such as NetBeans.
// You can follow to the class very easy by control and click on the class name in the line @var.
// Some of these variables also useful while you writing the code in the IDE, it will be show the drop down helper for you.

/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Url \Rdb\System\Libraries\Url */
/* @var $Views \Rdb\System\Views */
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
    <body>