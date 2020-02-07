<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Confirmation required.'); ?></h1>

                        <form id="rdba-actions-roles-form" class="rd-form horizontal rdba-edit-form" method="post" action="<?php if (isset($actionsRolesUrl)) {echo htmlspecialchars($actionsRolesUrl, ENT_QUOTES);} ?>">
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
                            <div class="form-result-placeholder"><?php
                            if (function_exists('renderAlertHtml') && isset($formResultMessage)) {
                                echo renderAlertHtml($formResultMessage, ($formResultStatus ?? ''), false);
                            }
                            ?></div>

                            <input id="bulk-action" type="hidden" name="action" value="<?php if (isset($action)) {echo htmlspecialchars($action, ENT_QUOTES);} ?>">
                            <input id="bulk-userroles" type="hidden" name="userrole_ids" value="<?php if (isset($userrole_ids)) {echo htmlspecialchars($userrole_ids, ENT_QUOTES);} ?>">

                            <h3><?php if (isset($actionText)) {echo $actionText;} ?></h3>
                            <?php if (isset($listSelectedRoles['items']) && is_array($listSelectedRoles['items']) && !empty($listSelectedRoles['items'])) { ?>
                            <ul>
                                <?php foreach ($listSelectedRoles['items'] as $row) { ?> 
                                <li>
                                    <?php echo $row->userrole_name; ?> <a href="<?php echo $Url->getAppBasedPath(true) . '/admin/roles/edit/' . $row->userrole_id; ?>"><?php echo __('Edit'); ?></a>
                                </li>
                                <?php }// endforeach;
                                unset($row);
                                ?> 
                            </ul>
                            <?php }// endif; ?> 

                            <?php if (isset($deleteUserDefaultRole) && $deleteUserDefaultRole === true) { ?> 
                            <h4><?php echo __('Please select user\'s default role'); ?></h4>
                            <div class="rd-alertbox alert-warning"><?php echo __('You are deleting at least a role that is in user\'s default role on register, please select a new one.'); ?></div>
                            <div class="form-group">
                                <label class="control-label" for="config_rdbadmin_UserRegisterDefaultRoles"><?php echo __('User\'s default role'); ?></label>
                                <div class="control-wrapper">
                                    <?php if (isset($listRoles['items']) && is_array($listRoles['items'])) { ?> 
                                    <select id="config_rdbadmin_UserRegisterDefaultRoles" name="config_rdbadmin_UserRegisterDefaultRoles" required="">
                                        <option value=""></option>
                                        <?php 
                                        foreach ($listRoles['items'] as $row) { 
                                            if (
                                                isset($userrole_id_array) && 
                                                is_array($userrole_id_array) && 
                                                !in_array($row->userrole_id, $userrole_id_array)
                                            ) {
                                        ?> 
                                        <option value="<?php echo $row->userrole_id; ?>">
                                            <?php echo $row->userrole_name; ?>
                                        </option>
                                        <?php 
                                            }
                                        }// endforeach;
                                        unset($row);
                                        ?> 
                                    </select>
                                    <?php }// endif; ?> 
                                </div>
                            </div>
                            <?php }//endif; ?> 

                            <?php if (isset($action) && $action === 'delete') { ?> 
                            <h4><?php echo __('Replace user\'s role'); ?></h4>
                            <p><?php echo __('The selected roles maybe in your current user\'s role, please select a new one if they will be deleted.'); ?></p>
                            <div class="form-group">
                                <label class="control-label" for="new_usersroles_id"><?php echo __('Replace user\'s role'); ?></label>
                                <div class="control-wrapper">
                                    <?php if (isset($listRoles['items']) && is_array($listRoles['items'])) { ?> 
                                    <select id="new_usersroles_id" name="new_usersroles_id" required="">
                                        <option value=""></option>
                                        <?php 
                                        foreach ($listRoles['items'] as $row) { 
                                            if (
                                                isset($userrole_id_array) && 
                                                is_array($userrole_id_array) && 
                                                !in_array($row->userrole_id, $userrole_id_array)
                                            ) {
                                        ?> 
                                        <option value="<?php echo $row->userrole_id; ?>">
                                            <?php echo $row->userrole_name; ?>
                                        </option>
                                        <?php 
                                            }
                                        }// endforeach;
                                        unset($row);
                                        ?> 
                                    </select>
                                    <?php }// endif; ?> 
                                </div>
                            </div>
                            <?php }// endif; ?> 

                            <div class="form-group submit-button-row">
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"<?php if (isset($formValidated) && $formValidated === false) {echo ' disabled="disabled"';} ?>><?php echo __('Confirm'); ?></button>
                                </div>
                            </div>
                        </form>
                        