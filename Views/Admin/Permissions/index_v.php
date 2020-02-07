<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Manage permissions'); ?></h1>

                        <div class="form-result-placeholder"></div>

                        <form id="rdba-permissions-filter-form" class="rdba-datatables-form">
                            <div class="rdba-datatables-result-controls rd-columns-flex-container">
                                <div class="col-xs-12 col-sm-6">
                                    <label>
                                        <?php echo __('Module'); ?> 
                                        <select id="rdba-filter-permissionModule" name="permissionModule"></select>
                                    </label>
                                    <label>
                                        <?php echo __('Permission for'); ?> 
                                        <select id="rdba-filter-permissionFor" name="permissionFor">
                                            <option value="roles"><?php echo esc__('Roles'); ?></option>
                                            <option value="users"><?php echo esc__('Users'); ?></option>
                                        </select>
                                    </label>
                                    <label id="rdba-filter-label-permissionForUserId" class="rd-hidden">
                                        <?php echo __('Username'); ?> 
                                        <input id="rdba-filter-permissionForUserId" type="hidden" name="permissionForUserId" value="">
                                        <input id="rdba-filter-permissionForUserName" class="rdba-datatables-input-search" type="text" value="" list="rdba-filter-permissionForUserName-dataList" autocomplete="off">
                                        <datalist id="rdba-filter-permissionForUserName-dataList"></datalist>
                                    </label>
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="submit"><?php echo __('Filter'); ?></button>
                                </div><!--.col-->
                            </div><!--.rd-columns-flex-container-->
                        </form>

                        <form id="rdba-permissions-form" class="rdba-datatables-form">
                            <div class="rd-datatable-wrapper">
                                <table id="permissionsTable" class="permissionsTable rd-datatable">
                                    <thead>
                                        <tr>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <button id="rdba-permission-reset" class="rd-button danger" type="button" data-toggle="tooltip" title="<?php echo esc__('Clear all permissions for this module (including roles and users).'); ?>" data-placement="right"><?php echo __('Clear'); ?></button>
                        </form>

                        <template id="rdba-datatable-header-cells">
                            {{#each this}}
                            <th>
                                {{#if editLink}}
                                <a href="{{editLink}}">
                                {{/if}}
                                {{name}}
                                {{#if editLink}}
                                </a>
                                {{/if}}
                            </th>
                            {{/each}}
                        </template>
                        <script id="rdba-datatable-row-data" type="text/x-handlebars-template">
                            {{#each this}}
                            <tr>
                                {{#each this}}
                                    {{#if type}}
                                    <td{{#if totalActions}} rowspan="{{totalActions}}"{{/if}}>
                                        {{#if name}}
                                        {{name}}
                                        {{/if}}
                                        {{#ifEquals type 'checkbox'}}
                                        <input 
                                            class="rdba-permission-checkbox"
                                            type="checkbox" 
                                            name="{{identityName}}[]" 
                                            value="{{identityValue}}" 
                                            data-permission_page="{{permissionPageData}}" 
                                            data-permission_action="{{permissionActionData}}"
                                            {{#ifEquals alwaysChecked true}}
                                            data-alwayschecked="true"
                                            {{/ifEquals}}
                                            {{#ifEquals checked true}}
                                            checked="checked"
                                            {{/ifEquals}}
                                        >
                                        {{/ifEquals}}
                                        <span class="rdba-permission-checkbox-action-status"></span>
                                    </td>
                                    {{/if}}
                                {{/each}}
                            </tr>
                            {{/each}}
                        </script>