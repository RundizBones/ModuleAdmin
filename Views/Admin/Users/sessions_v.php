<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Login sessions'); ?></h1>
                        <p><?php echo sprintf(__('Login sessions of the user %s.'), '<a href="' . ($editUserUrl ?? '') . '"><strong id="user_login"></strong></a>') ?></p>

                        <form id="rdba-loginsessions-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="userLoginsTable" class="userLoginsTable rdba-datatables-js responsive hover">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.userLoginsTable', this);"></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('User agent'); ?></th>
                                        <th><?php echo __('IP address'); ?></th>
                                        <th><?php echo __('Date/time'); ?></th>
                                        <th><?php echo __('Result'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.userLoginsTable', this);"></th>
                                        <th class="column-primary" data-priority="1"><?php echo __('User agent'); ?></th>
                                        <th><?php echo __('IP address'); ?></th>
                                        <th><?php echo __('Date/time'); ?></th>
                                        <th><?php echo __('Result'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>

                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <?php echo __('Result'); ?>
                                    <select id="rdba-filter-result" class="rdba-datatables-selectbox" name="filter-result">
                                        <option value=""><?php echo __('All'); ?></option>
                                        <option value="0"><?php echo __('Failed'); ?></option>
                                        <option value="1"><?php echo __('Succeeded'); ?></option>
                                    </select>
                                </label>
                                <label>
                                    <?php echo __('Search'); ?>
                                    <input id="rdba-filter-search" class="rdba-datatables-input-search" type="search" name="search" aria-control="userLoginsTable">
                                </label>
                                <div class="rd-button-group">
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="button"><?php echo __('Filter'); ?></button>
                                    <button class="rd-button dropdown-toggler" type="button" data-placement="bottom right">
                                        <i class="fa-solid fa-caret-down fontawesome-icon"></i>
                                        <span class="sr-only"><?php echo __('More'); ?></span>
                                    </button>
                                    <ul class="rd-dropdown">
                                        <li><a href="#reset" onclick="return RdtaSessionsController.resetDataTable();"><?php echo __('Reset'); ?></a></li>
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
                                        <option value="delete"><?php echo __('Delete/Logout'); ?></option>
                                        <option value="empty"><?php echo __('Delete/Logout all'); ?></option>
                                    </select>
                                </label>
                                <button id="rdba-user-action-button" class="rd-button rdba-submit-button" type="submit"><?php echo __('Apply'); ?></button>
                            </div>
                        </template>