<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $this \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Edit user'); ?></h1>

                        <form id="rdba-edit-user-form" class="rd-form horizontal rdba-edit-form" method="<?php echo (isset($editUserMethod) ? strtolower($editUserMethod) : 'patch'); ?>" action="<?php if (isset($editUserUrl)) {echo htmlspecialchars($editUserUrl, ENT_QUOTES);} ?>">
                            <?php 
                            // use form html CSRF because this page can load via XHR, REST by HTML type and this can reduce double call to get CSRF values in JSON type again.
                            if (
                                isset($csrfName) && 
                                isset($csrfValue) && 
                                isset($csrfKeyPair[$csrfName]) &&
                                isset($csrfKeyPair[$csrfValue])
                            ) {
                            ?> 
                            <input id="rdba-form-csrf-name" type="hidden" name="<?php echo $csrfName; ?>" value="<?php echo $csrfKeyPair[$csrfName]; ?>">
                            <input id="rdba-form-csrf-value" type="hidden" name="<?php echo $csrfValue; ?>" value="<?php echo $csrfKeyPair[$csrfValue]; ?>">
                            <?php
                            }
                            ?>
                            <input id="user_id" type="hidden" name="user_id" value="<?php if (isset($user_id)) {echo $user_id;} ?>" readonly="">
                            <div class="form-result-placeholder"><?php
                            if (function_exists('renderAlertHtml') && isset($formResultMessage)) {
                                echo renderAlertHtml($formResultMessage, ($formResultStatus ?? ''), false);
                            }
                            ?></div>

                            <div class="form-group">
                                <label class="control-label" for="user_login"><?php echo __('Username'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <input id="user_login" class="user_login" type="text" name="user_login" value="<?php if (isset($user_login)) {echo htmlspecialchars($user_login, ENT_QUOTES);} ?>" maxlength="190" readonly="">
                                    <div class="form-description"><?php echo __('Username cannot be changed.'); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="user_email"><?php echo __('Email'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <input id="user_email" class="user_email" type="email" name="user_email" value="<?php if (isset($user_email)) {echo htmlspecialchars($user_email, ENT_QUOTES);} ?>" maxlength="190" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="user_display_name"><?php echo __('Display name'); ?></label>
                                <div class="control-wrapper">
                                    <input id="user_display_name" class="user_display_name" type="text" name="user_display_name" value="<?php if (isset($user_display_name)) {echo htmlspecialchars($user_display_name, ENT_QUOTES);} ?>" maxlength="190">
                                </div>
                            </div>

                            <h3><?php echo __('Security'); ?></h3>
                            <div class="form-group">
                                <label class="control-label" for="user_password"><?php echo __('New password'); ?></label>
                                <div class="control-wrapper">
                                    <input id="user_password" class="user_password" type="password" name="user_password" value="" maxlength="160">
                                    <div class="form-description"><?php echo __('Only fill this form if you want to change the password.'); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="confirm_password"><?php echo __('Confirm new password'); ?></label>
                                <div class="control-wrapper">
                                    <input id="confirm_password" class="confirm_password" type="password" name="confirm_password" value="" maxlength="160">
                                    <div class="form-description"><?php echo __('Only fill this form if you want to change the password.'); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="user_fields_rdbadmin_uf_securitysimultaneouslogin"><?php echo __('Simultaneous logins'); ?></label>
                                <div class="control-wrapper">
                                    <select id="user_fields_rdbadmin_uf_securitysimultaneouslogin" name="user_fields[rdbadmin_uf_securitysimultaneouslogin]">
                                        <option value="allow"><?php echo esc__('Allow simultaneous login'); ?></option>
                                        <option value="onlyLast"><?php echo esc__('Allow only last success login (if there is new success login session, the older sessions will be logged out).'); ?></option>
                                        <option value="allOut"><?php echo esc__('Log out all sessions and send login link to email'); ?></option>
                                    </select>
                                    <div class="form-description"><?php echo __('How to handle with simultaneous logins?'); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="user_fields_rdbadmin_uf_login2stepverification"><?php echo __('2 Step verification') ?></label>
                                <div class="control-wrapper">
                                    <select id="user_fields_rdbadmin_uf_login2stepverification" name="user_fields[rdbadmin_uf_login2stepverification]">
                                        <option value=""><?php echo esc__('Not in use'); ?></option>
                                        <option value="email"><?php echo esc__('Send verification code to my email'); ?></option>
                                    </select>
                                    <div class="form-description"><?php echo __('Select method to use for second verification step after entering your password.') ?></div>
                                </div>
                            </div>

                            <h3><?php echo __('Roles and status'); ?></h3>
                            <div class="form-group">
                                <label class="control-label" for="user_roles"><?php echo __('Role'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <select id="user_roles" name="user_roles[]" multiple="" size="3" required="">
                                        <?php 
                                        if (!isset($user_roles)) {
                                            $user_roles = [];
                                        }

                                        if (isset($listRoles['items']) && is_array($listRoles['items'])) {
                                            foreach ($listRoles['items'] as $row) {
                                                echo '<option value="' . $row->userrole_id . '"';
                                                echo \Rdb\System\Libraries\Form::staticSetSelected($row->userrole_id, $user_roles);
                                                echo '>';
                                                echo $row->userrole_name;
                                                echo '</option>' . PHP_EOL;
                                            }// endforeach;
                                            unset($row);
                                        }
                                        unset($listRoles, $user_roles);
                                        ?> 
                                    </select>
                                </div>
                            </div>
                            <div id="form-group-user_status" class="form-group">
                                <label class="control-label" for="user_status"><?php echo __('Status'); ?></label>
                                <div class="control-wrapper">
                                    <select id="user_status" name="user_status">
                                        <option value="0"<?php echo \Rdb\System\Libraries\Form::staticSetSelected('0', ($user_status ?? 1)); ?>><?php echo __('Disabled'); ?></option>
                                        <option value="1"<?php echo \Rdb\System\Libraries\Form::staticSetSelected('1', ($user_status ?? 1)); ?>><?php echo __('Enabled'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div id="form-group-user_statustext" class="form-group" style="display: none;">
                                <label class="control-label" for="user_statustext"><?php echo __('Status description'); ?></label>
                                <div class="control-wrapper">
                                    <input id="user_statustext" class="user_statustext" list="predefinedStatusTexts" type="text" name="user_statustext" value="<?php if (isset($user_statustext)) {echo htmlspecialchars($user_statustext, ENT_QUOTES);} ?>" maxlength="190">
                                    <div class="form-description"><?php echo __('If status is disabled, please describe the reason.'); ?></div>
                                    <datalist id="predefinedStatusTexts">
                                        <?php 
                                        if (isset($predefinedStatusTexts) && is_array($predefinedStatusTexts)) {
                                            foreach ($predefinedStatusTexts as $statusText) {
                                                echo '<option value="' . $statusText . '">' . __($statusText) . '</option>';
                                            }
                                        }
                                        ?> 
                                    </datalist>
                                </div>
                            </div>

                            <h3><?php echo __('Other info.'); ?></h3>
                            <div class="form-group">
                                <label class="control-label"><?php echo __('Profile picture'); ?></label>
                                <div class="control-wrapper">
                                    <div class="rdbadmin-avatar-type">
                                        <label>
                                            <input class="user_fields_rdbadmin_uf_avatar_type" type="radio" name="user_fields[rdbadmin_uf_avatar_type]" value="upload" checked="">
                                            <?php echo __('Use upload profile picture'); ?>
                                        </label>
                                        <label>
                                            <input class="user_fields_rdbadmin_uf_avatar_type" type="radio" name="user_fields[rdbadmin_uf_avatar_type]" value="gravatar">
                                            <?php echo __('Use Gravatar'); ?>
                                        </label>
                                    </div><!--.rdbadmin-avatar-type-->
                                    <div class="rdbadmin-avatar-type-gravatar rdbadmin-avatar-type-form rd-hidden">
                                        <div id="rdbadmin-avatar-current-gravatar"></div>
                                        <template id="rdbadmin-current-gravatar-display-template">
                                            <div class="rdbadmin-current-gravatar-container">
                                                <img src="{{gravatarUrl}}" alt="">
                                            </div>
                                            <a href="https://gravatar.com" target="gravatar"><?php echo __('Change your profile picture on Gravatar.'); ?></a>
                                        </template>
                                    </div><!--.rdbadmin-avatar-type-gravatar-->
                                    <div class="rdbadmin-avatar-type-upload rdbadmin-avatar-type-form">
                                        <div id="rdbadmin-current-avatar" class="rdbadmin-current-avatar"></div><!--.rdbadmin-current-avatar-->
                                        <template id="rdbadmin-current-avatar-display-template">
                                            <div class="rdbadmin-current-avatar-container">
                                                <img id="rdbadmin-current-avatar-image" class="fluid current-avatar-image" src="{{this.rdbadmin_uf_avatar}}" alt="">
                                            </div>
                                            <button id="rdbadmin-delete-current-avatar-button" class="rd-button warning" type="button">
                                                <i class="fa-solid fa-xmark"></i>
                                                <?php echo __('Delete this profile picture'); ?>
                                            </button>
                                            <span id="rdbadmin-delete-avatar-status-placeholder"></span>
                                        </template>
                                        <div id="rdbadmin-select-avatar-dropzone" class="rdbadmin-select-avatar-dropzone">
                                            <span class="rd-button info rd-inputfile" tabindex="0">
                                                <span class="label"><?php echo __('Choose image'); ?></span>
                                                <input id="user_fields_rdbadmin_uf_avatar" type="file" name="user_fields[rdbadmin_uf_avatar]" tabindex="-1">
                                            </span>
                                            <span class="rd-input-files-queue"></span>
                                            <template class="rd-inputfile-reset-button">
                                                <button class="rd-button tiny" type="button" onclick="return RundizTemplateAdmin.resetInputFile(this);" title="<?php echo esc__('Remove file'); ?>"><i class="fa-solid fa-xmark"></i><span class="screen-reader-only"><?php echo esc__('Remove file'); ?></span></button>
                                            </template>
                                            <div id="rdbadmin-avatar-upload-status-placeholder"></div>
                                        </div><!--.rdbadmin-select-avatar-dropzone-->
                                        <div class="form-description"><?php echo __('Click on choose image or drop image into the area above to upload profile picture.'); ?></div>
                                    </div><!--.rdbadmin-avatar-type-upload-->
                                </div><!--.control-wrapper-->
                            </div><!--.form-group-->
                            <div class="form-group">
                                <label class="control-label" for="user_fields_rdbadmin_uf_website"><?php echo __('Website'); ?></label>
                                <div class="control-wrapper">
                                    <input id="user_fields_rdbadmin_uf_website" type="url" name="user_fields[rdbadmin_uf_website]" value="<?php if (isset($user_fields['website'])) {echo htmlspecialchars($user_fields['website'], ENT_QUOTES);} ?>" maxlength="255">
                                </div>
                            </div>
                            <?php 
                            if ($this->Container->has('Plugins')) {
                                /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
                                $Plugins = $this->Container->get('Plugins');
                                /*
                                 * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\Users\EditController->indexAction.bottomOtherInfo
                                 * PluginHookDescription: Hook to display contents at the bottom of other info section.
                                 * PluginHookSince: 0.2.4
                                 */
                                $Plugins->doHook($this->controllerMethod.'.bottomOtherInfo');
                                unset($Plugins);
                            }
                            /*<div class="form-group">
                                <label class="control-label">Select multiple.</label>
                                <div class="control-wrapper">
                                    <select id="user_fields_rdbadmin_uf_selectmultiple" name="user_fields[rdbadmin_uf_selectmultiple][]" multiple="" size="3">
                                        <option value="a">A</option>
                                        <option value="b">B</option>
                                        <option value="c">C</option>
                                        <option value="d">D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Checkbox.</label>
                                <div class="control-wrapper">
                                    <label><input class="user_fields_rdbadmin_uf_checkbox" type="checkbox" name="user_fields[rdbadmin_uf_checkbox][]" value="Aa">CAa</label>
                                    <label><input class="user_fields_rdbadmin_uf_checkbox" type="checkbox" name="user_fields[rdbadmin_uf_checkbox][]" value="Bb">CBb</label>
                                    <label><input class="user_fields_rdbadmin_uf_checkbox" type="checkbox" name="user_fields[rdbadmin_uf_checkbox][]" value="Cc">CCc</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Radio.</label>
                                <div class="control-wrapper">
                                    <label><input class="user_fields_rdbadmin_uf_radio" type="radio" name="user_fields[rdbadmin_uf_radio]" value="RAa">RAa</label>
                                    <label><input class="user_fields_rdbadmin_uf_radio" type="radio" name="user_fields[rdbadmin_uf_radio]" value="RBb">RBb</label>
                                    <label><input class="user_fields_rdbadmin_uf_radio" type="radio" name="user_fields[rdbadmin_uf_radio]" value="RCc">RCc</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Radio multiple.</label>
                                <div class="control-wrapper">
                                    <label><input class="user_fields_rdbadmin_uf_radio_multiple" type="radio" name="user_fields[rdbadmin_uf_radio_multiple][]" value="RRAa">RRAa</label>
                                    <label><input class="user_fields_rdbadmin_uf_radio_multiple" type="radio" name="user_fields[rdbadmin_uf_radio_multiple][]" value="RRBb">RRBb</label>
                                    <label><input class="user_fields_rdbadmin_uf_radio_multiple" type="radio" name="user_fields[rdbadmin_uf_radio_multiple][]" value="RRCc">RRCc</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Textarea.</label>
                                <div class="control-wrapper">
                                    <textarea id="user_fields_rdbadmin_uf_textarea" name="user_fields[rdbadmin_uf_textarea]"></textarea>
                                </div>
                            </div>*/// just demo ?>

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label class="control-label"><?php echo __('Created date'); ?></label>
                                <div class="control-wrapper">
                                    <div id="user_create"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo __('Last update'); ?></label>
                                <div class="control-wrapper">
                                    <div id="user_lastupdate"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo __('Last login'); ?></label>
                                <div class="control-wrapper">
                                    <div id="user_lastlogin"></div>
                                    <div class="form-description">
                                        <a href="<?php echo $Url->getAppBasedPath(true) . '/admin/users/' . ($user_id ?? '') . '/sessions'; ?>"><?php echo __('View logins'); ?></a>
                                    </div>
                                </div>
                            </div>

                            <div class="list-email-changed-history rd-hidden">
                                <h3><?php echo __('Previous emails'); ?></h3>
                                <div class="rd-datatable-wrapper">
                                    <table id="list-email-changed-history-table" class="rd-datatable h-border">
                                        <thead>
                                            <tr>
                                                <th><?php echo __('Email'); ?></th>
                                                <th><?php echo __('Changed date'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center" colspan="2"><a href="<?php echo $Url->getAppBasedPath(true) . '/admin/users/' . ($user_id ?? '') . '/previous-emails'; ?>"><?php echo __('View all'); ?></a></td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th><?php echo __('Email'); ?></th>
                                                <th><?php echo __('Changed date'); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div><!--.rd-datatable-wrapper-->
                                <template id="list-email-changed-history-table-row-template">
                                    {{#each field_value}}
                                    <tr>
                                        <td>{{this.email}}</td>
                                        <td>{{#formatDate this.gmtdate}}{{/formatDate}}</td>
                                    </tr>
                                    {{/each}}
                                </template>
                            </div><!--.list-email-changed-history-->

                            <?php 
                            if (
                                isset($user_id) && 
                                isset($my_user_id) && 
                                $user_id === $my_user_id &&
                                isset($configDb['rdbadmin_UserDeleteSelfGrant']) && 
                                $configDb['rdbadmin_UserDeleteSelfGrant'] == '1'
                            ) { 
                            ?> 
                            <fieldset>
                                <legend><?php echo __('Danger zone'); ?></legend>
                                <div class="form-group submit-button-row">
                                    <label class="control-label"><?php echo __('Delete account'); ?></label>
                                    <div class="control-wrapper">
                                        <a class="rd-button danger" href="<?php if (isset($deleteMeUrl)) {echo $deleteMeUrl;} ?>"><?php echo __('Delete my account'); ?></a>
                                    </div>
                                </div>
                            </fieldset>
                            <?php 
                            }// endif; 
                            ?> 
                        </form>
                        