<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                        </h1>

                        <form id="rdba-modulesplugins-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="modulePluginsTable" class="modulePluginsTable rdba-datatables-js responsive hover" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.modulePluginsTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Plugin'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Location'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Description'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.modulePluginsTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Plugin'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Location'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Description'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>

                        <template id="rdba-datatables-row-actions">
                            <div class="row-actions">
                                <span class="action">
                                    <?php echo __('Status'); ?>: 
                                    {{#if enabled}}
                                        {{RdbaModulesPlugins.txtEnabled}}
                                    {{else}}
                                        {{RdbaModulesPlugins.txtDisabled}}
                                    {{/if}}
                                </span>
                            </div>
                        </template>

                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>&nbsp;<!-- for preserve vertical space when pagination is displaying just number of pages (less than xx items that can start pagination) --></label>
                            </div>
                        </template>
                        <template id="rdba-datatables-result-controls-pagination">
                            <span class="rdba-datatables-result-controls-info">
                            {{#ifGE recordsFiltered 2}}
                                {{recordsFiltered}} <?php echo __('items'); ?>
                            {{else}}
                                {{recordsFiltered}} <?php echo __('item'); ?>
                            {{/ifGE}}
                            </span>
                        </template>
                        <template id="rdba-datatables-actions-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <select id="rdba-moduleplugin-actions" class="rdba-plugin-actions rdba-actions-selectbox" name="moduleplugin-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        {{#if RdbaModulesPlugins.permissions.managePlugins}}
                                        <option value="enable"><?php echo __('Enable'); ?></option>
                                        <option value="disable"><?php echo __('Disable'); ?></option>
                                        {{/if}}
                                    </select>
                                </label>
                                <button id="rdba-plugin-action-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                            </div>
                        </template>