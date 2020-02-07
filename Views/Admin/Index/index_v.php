<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1><?php echo __('Admin home'); ?></h1>
                        <div class="rdba-dashboard-container">
                            <div id="rdba-dashboard-row-hero" class="rdba-dashboard-row-hero rd-block-level-margin-bottom"></div>
                            <div id="rdba-dashboard-row-normal" class="rdba-dashboard-row-normal"></div>
                        </div><!--.rdba-dashboard-container-->

                        <template id="rdba-dashboardwidget-rowhero">
                            <div class="rdba-dashboard-widget-column" data-widgetid="{{id}}">
                                <section id="rdba-dashboard-row-hero-contents-{{id}}" class="rdba-dashboard-row-hero-contents rdba-dashboard-widget-contents{{#if classes}} {{classes}}{{/if}}">
                                    <i class="fas fa-arrows-alt fa-fw drag-icon"></i>
                                    {{{content}}}
                                </section>
                            </div>
                        </template>
                        <template id="rdba-dashboardwidget-rownormal">
                            <div class="rdba-dashboard-widget-column" data-widgetid="{{id}}">
                                <section id="rdba-dashboard-column-normal-contents-{{id}}" class="rdba-dashboard-column-normal-contents rdba-dashboard-widget-contents{{#if classes}} {{classes}}{{/if}}">
                                    <i class="fas fa-arrows-alt fa-fw drag-icon"></i>
                                    {{{content}}}
                                </section>
                            </div>
                        </template>