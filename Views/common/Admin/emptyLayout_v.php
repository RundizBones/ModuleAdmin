<?php
/**
 * The empty layout is has nothing but HTML head and foot.
 * It is best for forgot login or password page, login, logout, register, confirm register pages.
 */

/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
if (isset($pageHtmlClasses) && stripos($pageHtmlClasses, 'rdba-empty-layout') === false) {
    $pageHtmlClasses .= ' rdba-empty-layout';
}
?>
<?php include dirname(__DIR__) . '/htmlHead_v.php'; ?> 
        <?php
        if (isset($pageContent)) {
            echo "\n\n";

            /*
             * PluginHook: Modules/RdbAdmin/Views/common/Admin/emptyLayout_v.php.before_pageContent
             * PluginHookDescription: Hook before render `$pageContent`.
             * PluginHookParam: None.<br>
             * PluginHookSince: 1.2.6
             */
            /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
            $Plugins = $this->Container->get('Plugins');
            $Plugins->doHook('Modules/RdbAdmin/Views/common/Admin/emptyLayout_v.php.before_pageContent');
            unset($Plugins);

            echo '<!--begins main layout page content-->'."\n";
            echo $pageContent."\n";
            echo '<!--end main layout page content-->'."\n";
            echo "\n\n";
        }
        unset($pageContent);
        ?>
<?php include dirname(__DIR__) . '/htmlFoot_v.php'; ?>