<?php
/* @var $Assets \Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \System\Modules */
/* @var $Views \System\Views */
/* @var $Url \System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <form id="rdba-add-user-form" class="rd-form horizontal rdba-edit-form" method="<?php echo (isset($addUserMethod) ? strtolower($addUserMethod) : 'post'); ?>" action="<?php if (isset($addUserUrl)) {echo htmlspecialchars($addUserUrl, ENT_QUOTES);} ?>">
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
                            <div class="form-result-placeholder"></div>

                            <div class="form-group">
                                <label class="control-label" for="user_login"><?php echo __('Username'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <input id="user_login" class="user_login" type="text" name="user_login" value="<?php if (isset($user_login)) {echo htmlspecialchars($user_login, ENT_QUOTES);} ?>" maxlength="190" required="">
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
                                <label class="control-label" for="user_password"><?php echo __('Password'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <input id="user_password" class="user_password" type="password" name="user_password" value="" maxlength="160" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="confirm_password"><?php echo __('Confirm password'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <input id="confirm_password" class="confirm_password" type="password" name="confirm_password" value="" maxlength="160" required="">
                                </div>
                            </div>

                            <h3><?php echo __('Roles and status'); ?></h3>
                            <div class="form-group">
                                <label class="control-label" for="user_roles"><?php echo __('Role'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <select id="user_roles" name="user_roles[]" multiple="" size="3" required="">
                                        <?php 
                                        if (isset($configDb['rdbadmin_UserRegisterDefaultRoles'])) {
                                            $roles = explode(',', $configDb['rdbadmin_UserRegisterDefaultRoles']);
                                            $roles = array_map('trim', $roles);
                                        } else {
                                            $roles = [];
                                        }

                                        if (isset($listRoles['items']) && is_array($listRoles['items'])) {
                                            foreach ($listRoles['items'] as $row) {
                                                echo '<option value="' . $row->userrole_id . '"';
                                                echo \System\Libraries\Form::staticSetSelected($row->userrole_id, $roles);
                                                echo '>';
                                                echo $row->userrole_name;
                                                echo '</option>' . PHP_EOL;
                                            }// endforeach;
                                            unset($row);
                                        }
                                        unset($listRoles, $roles);
                                        ?> 
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo __('Send user notification'); ?></label>
                                <div class="control-wrapper">
                                    <label>
                                        <input type="checkbox" name="notify_user" value="1"<?php echo \System\Libraries\Form::staticSetChecked('1', ($notify_user ?? 1)); ?>>
                                        <?php echo __('Send an email to notify user about their account.'); ?>
                                    </label>
                                    <div class="form-description"><?php echo __('If this were checked, the status will be disabled and this user have to click on the activation link.'); ?></div>
                                </div>
                            </div>
                            <div id="form-group-user_status" class="form-group" style="display: none;">
                                <label class="control-label" for="user_status"><?php echo __('Status'); ?></label>
                                <div class="control-wrapper">
                                    <select id="user_status" name="user_status">
                                        <option value="0"<?php echo \System\Libraries\Form::staticSetSelected('0', ($user_status ?? 1)); ?>><?php echo __('Disabled'); ?></option>
                                        <option value="1"<?php echo \System\Libraries\Form::staticSetSelected('1', ($user_status ?? 1)); ?>><?php echo __('Enabled'); ?></option>
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

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                </div>
                            </div>
                        </form>
                        