<?php
/**
 * The empty layout is has nothing but HTML head and foot.
 * It is best for forgot login or password page, login, logout, register, confirm register pages.
 */

/* @var $Assets \Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \System\Modules */
/* @var $Views \System\Views */
/* @var $Url \System\Libraries\Url */
?>
<?php include dirname(__DIR__) . '/htmlHead_v.php'; ?> 
        <?php
        if (isset($pageContent)) {
            echo "\n\n";
            echo '<!--begins main layout page content-->'."\n";
            echo $pageContent."\n";
            echo '<!--end main layout page content-->'."\n";
            echo "\n\n";
        }
        unset($pageContent);
        ?>
<?php include dirname(__DIR__) . '/htmlFoot_v.php'; ?>