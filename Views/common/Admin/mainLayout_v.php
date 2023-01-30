<?php
/**
 * The main layout is for web pages that logged in users will be use.
 * It contain HTML head and foot, navbar (top bar), sidebar menu items, breadcrumb, page footer.
 */

/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
<?php include dirname(__DIR__) . '/htmlHead_v.php'; ?> 
        <header class="rd-navbar">
            <button class="rd-sidebar-toggler" data-target=".rd-page-wrapper" title="<?php echo esc__('Toggle side bar menu'); ?>">
                <i class="fa-solid fa-bars fa-fw"></i> 
                <span class="screen-reader-only" aria-hidden="true"><?php echo __('Toggle side bar menu'); ?></span>
            </button>
            <div class="rd-site-brand"><a href=""><?php echo __('Loading &hellip;'); ?></a></div><!--.rd-site-brand-->
            <nav class="nav-right">
                <ul class="sm sm-rdta navbar">
                    <li id="rdba-languages-navbar"><a href="#" onclick="return false;"><i class="fa-solid fa-globe fa-fw"></i></a>
                        <ul id="rdba-languages-navbar-list">
                            <li><a><?php echo __('Loading &hellip;'); ?></a></li>
                        </ul>
                    </li>
                    <li id="rdba-notification-navbar"><a href="#" onclick="return false;"><i class="fa-solid fa-bell fa-fw"></i></a>
                        <ul id="rdba-notification-navbar-list">
                            <li><a><?php echo __('Loading &hellip;'); ?></a></li>
                        </ul>
                    </li>
                    <li id="rdba-user-navbar" class="user"><a href="#" onclick="return false;"><i class="fa-regular fa-circle-user rdba-user-icon"></i></a>
                        <ul id="rdba-user-navbar-list">
                            <li><a><?php echo __('Loading &hellip;'); ?></a></li>
                        </ul>
                    </li>
                </ul>
            </nav><!--.nav-right-->
        </header><!--.rd-navbar--> 

        <div class="rd-page-wrapper">
            <div class="rd-sidebar-back"></div>
            <section class="rd-sidebar">
                <div class="rd-sidebar-inner">
                    <ul class="rd-sidebar-item-list sm sm-vertical sm-rdta">
                        <li><a href="#" onclick="return false;"><i class="sidebar-icon fa-solid fa-spinner fa-pulse"></i> <span class="rd-sidebar-menu-text"><?php echo __('Loading &hellip;'); ?></span></a></li>
                    </ul>
                    <ul class="rd-sidebar-item-list rd-sidebar-expand-collapse-controls">
                        <li>
                            <a data-target=".rd-page-wrapper" title="<?php echo esc__('Expane/collapse menu'); ?>">
                                <i class="sidebar-icon faicon fa-solid fa-chevron-left fa-fw" data-toggle-icon="fa-chevron-left fa-chevron-right"></i> 
                                <span class="screen-reader-only" aria-hidden="true"><?php echo __('Expane/collapse menu'); ?></span>
                            </a>
                            <hr>
                        </li>
                    </ul>
                </div><!--.rd-sidebar-inner-->
            </section><!--.rd-sidebar-->
            <main>
                <nav>
                    <ul class="rd-breadcrumb">
                        <?php
                        if (isset($pageBreadcrumb) && is_scalar($pageBreadcrumb)) {
                            // if page breadcrumb was set via controller. (it must be string, li tags.)
                            echo $pageBreadcrumb;
                        }
                        ?> 
                    </ul>
                </nav>
                <div class="rd-page-content">
                    <div class="rdba-page-alert-placeholder"><?php
                    if (function_exists('renderAlertHtml') && isset($pageAlertMessage)) {
                        echo renderAlertHtml(
                            $pageAlertMessage, 
                            ($pageAlertStatus ?? ''), 
                            (isset($pageAlertDismissable) && is_bool($pageAlertDismissable) ? $pageAlertDismissable : true)
                        );
                    }
                    ?></div><!--.rdba-page-alert-placeholder-->
                    <div class="rdba-page-content-wrapper">
                        <?php
                        /*
                         * PluginHook: Modules/RdbAdmin/Views/common/Admin/mainLayout_v.php.before_pageContent
                         * PluginHookDescription: Hook before render `$pageContent`.
                         * PluginHookParam: None.<br>
                         * PluginHookSince: 1.2.6
                         */
                        /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
                        $Plugins = $this->Container->get('Plugins');
                        $Plugins->doHook('Modules/RdbAdmin/Views/common/Admin/mainLayout_v.php.before_pageContent');
                        unset($Plugins);

                        if (isset($pageContent) && is_scalar($pageContent)) {
                            echo "\n\n";
                            echo '<!--begins main layout page content-->'."\n";
                            echo $pageContent."\n";
                            echo '<!--end main layout page content-->'."\n";
                            echo "\n\n";
                        }
                        unset($pageContent);
                        ?>
                    </div><!--.rdba-page-content-wrapper-->
                </div><!--.rd-page-content-->
            </main>
            <footer>
                <div class="rd-page-footer-left"></div>
                <div class="rd-page-footer-right">
                    <a class="rdba-app-name" href="https://github.com/RundizBones/ModuleAdmin" target="_blank"><!--ui/xhr-common-data--></a>
                    <span class="rdba-app-version"><!--ui/xhr-common-data--></span>
                </div>
            </footer> 
        </div><!--.rd-page-wrapper-->


        <!--template for html page layout-->
        <script id="rdba-languages-navbar-item" type="text/x-handlebars-template">
            <!--
            This must be script tag with type="text/x-handlebars-template" because it contain condition for HTML attribute. 
            If not using script tag then it will be parse error (Expecting ..., got 'EQUALS')
            -->
            {{#each languages}}
            <li
                {{#ifEquals @key ../currentLanguage}} class="is-active"{{/ifEquals}}
                data-locale="{{@key}}" 
                data-languageLocale="{{this.languageLocale}}" 
                data-languageName="{{this.languageName}}" 
                data-languageDefault="{{this.languageDefault}}"
            >
                <a href="#{{@key}}">{{this.languageName}}</a>
            </li>
            {{/each}}
        </script>
        <template id="rdba-user-navbar-items">
            <li><a><?php echo sprintf(__('Hello, %1$s'), '<span class="display-name">{{user_display_name}}</span>'); ?></a></li>
            <li><a class="url-edit-your-account" href="{{UrlEditUser}}"><?php echo __('Edit your account'); ?></a></li>
            <li><a class="url-logout" href="{{UrlLogout}}"><?php echo __('Logout'); ?></a></li>
        </template>
        <script id="rdba-sidebar-menu-items" type="text/x-handlebars-template">
            {{#each menuItems}}
            <li
                id="rdba-sidebar-menu-item_{{#if this.id}}{{this.id}}{{else}}{{@index}}{{/if}}"
                {{#each this.liAttributes}}
                {{@key}}="{{this}}"
                {{/each}}
                data-rdbadmin-menu-item-index="index-{{@key}}"
            >
                <a
                    id="rdba-sidebar-menu-item-link_{{#if this.id}}{{this.id}}{{else}}{{@index}}{{/if}}"
                    {{#if this.subMenu}}class="has-submenu"{{/if}}
                    href="{{this.link}}"
                    {{#each this.aAttributes}}
                    {{@key}}="{{this}}"
                    {{/each}}
                >
                    {{#if this.icon}}<i class="sidebar-icon {{this.icon}}"></i> {{/if}}
                    <span class="rd-sidebar-menu-text">{{this.name}}</span>
                </a>
                {{#if this.subMenu}}
                <ul>
                    {{#each this.subMenu}}
                    <li
                        id="rdba-sidebar-menu-item_{{#if this.id}}{{this.id}}{{else}}{{@index}}{{/if}}"
                        {{#each this.liAttributes}}
                        {{@key}}="{{this}}"
                        {{/each}}
                        data-rdbadmin-submenu-item-index="index-{{@key}}"
                    >
                        <a
                            id="rdba-sidebar-menu-item-link_{{#if this.id}}{{this.id}}{{else}}{{@index}}{{/if}}"
                            {{#if this.subMenu}}class="has-submenu"{{/if}}
                            {{#if this.link}}href="{{this.link}}"{{/if}}
                            {{#each this.aAttributes}}
                            {{@key}}="{{this}}"
                            {{/each}}
                        >
                            {{this.name}}
                        </a>
                    </li>
                    {{/each}}
                </ul>
                {{/if}}
            </li>
            {{/each}}
        </script>
<?php include dirname(__DIR__) . '/htmlFoot_v.php'; ?>