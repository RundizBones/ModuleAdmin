<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Add new role'); ?></h1>

                        <form id="rdba-add-role-form" class="rd-form horizontal rdba-edit-form" method="<?php echo (isset($addRoleMethod) ? strtolower($addRoleMethod) : 'post'); ?>" action="<?php if (isset($addRoleSubmitUrl)) {echo htmlspecialchars($addRoleSubmitUrl, ENT_QUOTES);} ?>">
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

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                </div>
                            </div>
                        </form>
                        