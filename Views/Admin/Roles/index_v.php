<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo __('Manage roles'); ?> 
                            <a class="rd-button rdba-listpage-addnew" href="<?php echo $Url->getAppBasedPath(true) . '/admin/roles/add' ?>">
                                <i class="fa-solid fa-circle-plus fontawesome-icon"></i> <?php echo __('Add'); ?>
                            </a>
                        </h1>

                        <form id="rdba-roles-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="rolesTable" class="rolesTable rdba-datatables-js responsive hover" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="all column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.rolesTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="all column-primary" data-priority="1"><?php echo __('Role'); ?></th>
                                        <th class="min-tablet-p"><?php echo __('Description'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Created date'); ?></th>
                                        <th class="min-tablet-l"><?php echo __('Last update'); ?></th>
                                        <th class="min-tablet-p"><?php echo __('Priority'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.rolesTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Role'); ?></th>
                                        <th><?php echo __('Description'); ?></th>
                                        <th><?php echo __('Created date'); ?></th>
                                        <th><?php echo __('Last update'); ?></th>
                                        <th><?php echo __('Priority'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>

                        <div id="rdba-roles-dialog" class="rd-dialog-modal" data-click-outside-not-close="true">
                            <div class="rd-dialog rd-dialog-size-large" data-esc-key-not-close="true" aria-labelledby="rdba-roles-dialog-label">
                                <div class="rd-dialog-header">
                                    <h4 id="rdba-roles-dialog-label" class="rd-dialog-title"></h4>
                                    <button class="rd-dialog-close" type="button" aria-label="Close" data-dismiss="dialog">
                                        <i class="fa-solid fa-xmark fontawesome-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="rd-dialog-body">
                                </div>
                            </div>
                        </div><!--.rd-dialog-modal-->

                        <template id="rdba-datatables-row-actions">
                            <div class="row-actions">
                                <span class="action"><?php echo __('ID'); ?> {{userrole_id}}</span>
                                <span class="action"><a class="rdba-listpage-edit" href="{{RdbaRoles.editRolePageUrlBase}}/{{userrole_id}}"><?php echo __('Edit'); ?></a></span> 
                            </div>
                        </template>

                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <?php echo __('Search'); ?>
                                    <input id="rdba-filter-search" class="rdba-datatables-input-search" type="search" name="search" aria-control="rolesTable">
                                </label>
                                <div class="rd-button-group">
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="button"><?php echo __('Filter'); ?></button>
                                    <button class="rd-button dropdown-toggler" type="button" data-placement="bottom right">
                                        <i class="fa-solid fa-caret-down fontawesome-icon"></i>
                                        <span class="sr-only"><?php echo __('More'); ?></span>
                                    </button>
                                    <ul class="rd-dropdown">
                                        <li><a href="#reset" onclick="return RdbaRolesController.resetDataTable();"><?php echo __('Reset'); ?></a></li>
                                    </ul>
                                </div>
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
                                    <select id="rdba-role-actions" class="rdba-role-actions rdba-actions-selectbox" name="role-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        <option value="delete"><?php echo __('Delete'); ?></option>
                                    </select>
                                </label>
                                <button id="rdba-role-action-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                            </div>
                        </template>