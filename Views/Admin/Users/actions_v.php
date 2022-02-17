<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Confirmation required.'); ?></h1>

                        <form id="rdba-actions-users-form" class="rd-form horizontal rdba-edit-form" method="<?php echo (isset($actionsUserMethod) ? strtolower($actionsUserMethod) : 'patch'); ?>" action="<?php if (isset($actionsUserUrl)) {echo htmlspecialchars($actionsUserUrl, ENT_QUOTES);} ?>">
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
                            <input id="bulk-users" type="hidden" name="user_ids" value="<?php if (isset($user_ids)) {echo htmlspecialchars($user_ids, ENT_QUOTES);} ?>">

                            <h3><?php if (isset($actionText)) {echo $actionText;} ?></h3>
                            <?php 
                            if (isset($action) && $action === 'delete') {
                                echo renderAlertHtml(sprintf(__('Please note that this will be mark the users as deleted, the marked users will be really deleted after %1$d days.'), 30), 'alert-warning', false);
                            }//endif; 
                            ?> 
                            <?php if (isset($listUsers['items']) && is_array($listUsers['items'])) { ?> 
                            <ul>
                                <?php foreach ($listUsers['items'] as $row) { ?> 
                                <li><?php echo $row->user_login; ?> <a href="<?php echo $Url->getAppBasedPath(true) . '/admin/users/edit/' . $row->user_id; ?>"><?php echo __('Edit'); ?></a></li>
                                <?php }// endforeach;
                                unset($row);
                                ?> 
                            </ul>
                            <?php }// endif; $listUsers['items'] ?> 

                            <div class="form-group submit-button-row">
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"<?php if (isset($actionsFormOk) && $actionsFormOk === false) {echo ' disabled="disabled"';} ?>><?php echo __('Confirm'); ?></button>
                                </div>
                            </div>
                        </form>
                        