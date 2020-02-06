<?php
/* @var $Assets \Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \System\Modules */
/* @var $Views \System\Views */
/* @var $Url \System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Delete my account'); ?></h1>

                        <form id="rdba-delete-me-confirm-form" class="rd-form horizontal rdba-edit-form">
                            <div class="form-result-placeholder"></div>

                            <div class="form-group">
                                <label class="control-label" for="user_login"><?php echo __('Username'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <div id="user_login"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="user_email"><?php echo __('Email'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <div id="user_email"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="user_display_name"><?php echo __('Display name'); ?></label>
                                <div class="control-wrapper">
                                    <div id="user_display_name"></div>
                                </div>
                            </div>

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
                                </div>
                            </div>

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <?php
                                    echo renderAlertHtml(__('Once you had confirmed, you will be unable to login and use this account again.'), 'alert-warning', false);
                                    ?> 
                                    <button class="rd-button danger rdba-submit-button" type="submit"><?php echo __('Confirm delete'); ?></button>
                                    <a class="rd-button" href="<?php if (isset($editUserPageUrlBase)) {echo $editUserPageUrlBase;} ?>"><?php echo __('Cancel'); ?></a>
                                </div>
                            </div>
                        </form>
                        