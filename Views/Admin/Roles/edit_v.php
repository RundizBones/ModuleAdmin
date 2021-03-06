<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Edit role'); ?></h1>

                        <form id="rdba-edit-role-form" class="rd-form horizontal rdba-edit-form" method="post" action="">
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
                            <input id="userrole_id" type="hidden" name="userrole_id" value="<?php if (isset($userrole_id)) {echo $userrole_id;} ?>">
                            <div class="form-result-placeholder"><?php
                            if (function_exists('renderAlertHtml') && isset($formResultMessage)) {
                                echo renderAlertHtml($formResultMessage, ($formResultStatus ?? ''), false);
                            }
                            ?></div>

                            <div class="form-group">
                                <label class="control-label" for="userrole_name"><?php echo __('Role'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <input id="userrole_name" class="userrole_name" type="text" name="userrole_name" value="<?php if (isset($userrole_name)) {echo htmlspecialchars($userrole_name, ENT_QUOTES);} ?>" maxlength="190" required="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="userrole_description"><?php echo __('Description'); ?></label>
                                <div class="control-wrapper">
                                    <textarea id="userrole_description" name="userrole_description" maxlength="500"><?php if (isset($userrole_description)) {echo htmlspecialchars($userrole_description, ENT_QUOTES);} ?></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label"><?php echo __('Created date'); ?></label>
                                <div class="control-wrapper">
                                    <div id="userrole_create"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo __('Last update'); ?></label>
                                <div class="control-wrapper">
                                    <div id="userrole_lastupdate"></div>
                                </div>
                            </div>

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                </div>
                            </div>
                        </form>
                        