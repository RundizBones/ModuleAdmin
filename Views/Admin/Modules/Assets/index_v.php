<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                        </h1>

                        <form id="rdba-modulesassets-form" class="rdba-datatables-form rd-form">
                            <div class="form-result-placeholder"></div>
                            <table id="moduleAssetsTable" class="moduleAssetsTable rdba-datatables-js responsive hover">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.moduleAssetsTable', this);"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Module'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Number of assets'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Location'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.moduleAssetsTable', this);"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Module'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Number of assets'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Location'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>

                        <template id="rdba-datatables-row-actions">
                            <div class="row-actions">
                                <span class="action">
                                    <?php echo __('Status'); ?>: 
                                    {{#if enabled}}
                                        {{RdbaModulesAssetsObject.txtEnabled}}
                                    {{else}}
                                        {{RdbaModulesAssetsObject.txtDisabled}}
                                    {{/if}}
                                </span>
                                {{#if module_version}}
                                <span class="action"><?php echo __('Version'); ?>: {{module_version}}</span>
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
                                    <select id="rdba-modulesassets-actions" class="rdba-modulesassets-actions rdba-actions-selectbox" name="modulesassets-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        {{#if RdbaModulesAssetsObject.permissions.publishAssets}}
                                        <option value="publish"><?php echo __('Publish'); ?></option>
                                        {{/if}}
                                    </select>
                                </label>
                                <button id="rdba-modulesassets-action-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                                <span class="action-status-placeholder"></span>
                                <p class="form-description">
                                    <?php echo __('The publish action will be copy asset files and folders into the public folder where anyone can access via web address.'); ?> 
                                </p>
                            </div>
                        </template>