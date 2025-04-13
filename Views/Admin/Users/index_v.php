<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo __('Manage users'); ?> 
                            <?php if (isset($permissions['add']) && $permissions['add'] === true) { ?> 
                            <a class="rd-button rdba-listpage-addnew" href="<?php echo $Url->getAppBasedPath(true) . '/admin/users/add' ?>">
                                <i class="fa-solid fa-circle-plus fontawesome-icon"></i> <?php echo __('Add'); ?>
                            </a>
                            <?php }// endif; ?> 
                        </h1>

                        <form id="rdba-users-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="usersTable" class="usersTable rdba-datatables-js responsive hover">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.usersTable', this);"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Username'); ?></th>
                                        <th><?php echo __('Display name'); ?></th>
                                        <th><?php echo __('Email'); ?></th>
                                        <th><?php echo __('Roles'); ?></th>
                                        <th><?php echo __('Status'); ?></th>
                                        <th><?php echo __('Last login'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.usersTable', this);"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('Username'); ?></th>
                                        <th><?php echo __('Display name'); ?></th>
                                        <th><?php echo __('Email'); ?></th>
                                        <th><?php echo __('Roles'); ?></th>
                                        <th><?php echo __('Status'); ?></th>
                                        <th><?php echo __('Last login'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>

                        <div id="rdba-users-dialog" class="rd-dialog-modal" data-click-outside-not-close="true">
                            <div class="rd-dialog rd-dialog-size-large" data-esc-key-not-close="true" aria-labelledby="rdba-users-dialog-label">
                                <div class="rd-dialog-header">
                                    <h4 id="rdba-users-dialog-label" class="rd-dialog-title"></h4>
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
                                <span class="action"><?php echo __('ID'); ?> {{user_id}}</span>
                                {{#if RdbaUsers.permissions.edit}}
                                <span class="action"><a class="rdba-listpage-edit" href="{{RdbaUsers.editUserPageUrlBase}}/{{user_id}}"><?php echo __('Edit'); ?></a></span> 
                                {{/if}}
                                {{#if RdbaUsers.permissions.viewLogins}}
                                <span class="action"><a href="{{#replace '{{user_id}}' user_id}}{{RdbaUsers.viewLoginsUrl}}{{/replace}}"><?php echo __('View logins'); ?></a></span>
                                {{/if}}
                                {{#if RdbaUsers.permissions.managePermissions}}
                                <span class="action"><a href="{{RdbaUsers.getPermissionsUrl}}?permissionFor=users&permissionForUserId={{user_id}}"><?php echo __('Manage permissions'); ?></a></span>
                                {{/if}}
                            </div>
                        </template>

                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <?php echo __('Status'); ?>
                                    <select id="rdba-filter-status" class="rdba-datatables-selectbox" name="filter-status">
                                        <option value=""><?php echo __('All'); ?></option>
                                        <option value="0"><?php echo __('Disabled'); ?></option>
                                        <option value="1"><?php echo __('Enabled'); ?></option>
                                    </select>
                                </label>
                                <label>
                                    <?php echo __('Roles'); ?>
                                    <select id="rdba-filter-roles" class="rdba-datatables-selectbox" name="filter-roles">
                                        <option value=""><?php echo __('All'); ?></option>
                                        {{#each listRoles.items}}
                                        <option value="{{userrole_id}}">{{{userrole_name}}}</option>
                                        {{/each}}
                                    </select>
                                </label>
                                <label>
                                    <?php echo __('Search'); ?>
                                    <input id="rdba-filter-search" class="rdba-datatables-input-search" type="search" name="search" aria-control="usersTable">
                                </label>
                                <div class="rd-button-group">
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="button"><?php echo __('Filter'); ?></button>
                                    <button class="rd-button dropdown-toggler" type="button" data-placement="bottom right">
                                        <i class="fa-solid fa-caret-down fontawesome-icon"></i>
                                        <span class="sr-only"><?php echo __('More'); ?></span>
                                    </button>
                                    <ul class="rd-dropdown">
                                        <li><a href="#reset" onclick="return RdbaUsersController.resetDataTable();"><?php echo __('Reset'); ?></a></li>
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
                                    <select id="rdba-user-actions" class="rdba-user-actions rdba-actions-selectbox" name="user-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        {{#if RdbaUsers.permissions.delete}}
                                        <option value="delete"><?php echo __('Delete'); ?></option>
                                        {{/if}}
                                        {{#if RdbaUsers.permissions.edit}}
                                        <option value="enable"><?php echo __('Enable'); ?></option>
                                        <option value="disable"><?php echo __('Disable'); ?></option>
                                        {{/if}}
                                    </select>
                                </label>
                                <button id="rdba-user-action-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                            </div>
                        </template>