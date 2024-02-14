<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                        </h1>

                        <form id="rdba-modules-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="modulesTable" class="modulesTable rdba-datatables-js responsive hover" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.modulesTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Module'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Location'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Description'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.modulesTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Module'); ?></th>
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
                                    {{#if module_enabled}}
                                        {{RdbaModulesObject.txtEnabled}}
                                    {{else}}
                                        {{RdbaModulesObject.txtDisabled}}
                                    {{/if}}
                                </span>
                                {{#if module_version}}
                                <span class="action"><?php echo __('Version'); ?>: {{module_version}}</span>
                                {{/if}}
                            </div>
                        </template>

                        <template id="rdba-datatables-row-desctiption-secondary">
                            <div class="data-secondary-row">
                                {{#if module_author}}
                                <span class="action"><?php echo __('Author'); ?>: {{module_author}}</span>
                                {{/if}}
                                {{#if module_requires_php}}
                                <span class="action"><?php echo __('Requires PHP'); ?>: {{module_requires_php}}</span>
                                {{/if}}
                                {{#if module_requires_modules}}
                                <span class="action"><?php echo __('Requires modules'); ?>: {{module_requires_modules}}</span>
                                {{/if}}
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
                                    <select id="rdba-module-actions" class="rdba-module-actions rdba-actions-selectbox" name="module-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        {{#if RdbaModulesObject.permissions.manageModules}}
                                        <option value="enable"><?php echo __('Enable'); ?></option>
                                        <option value="disable"><?php echo __('Disable'); ?></option>
                                        <option value="update"><?php echo __('Update module'); ?></option>
                                        {{/if}}
                                    </select>
                                </label>
                                <button id="rdba-module-action-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                            </div>
                        </template>