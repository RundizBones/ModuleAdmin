<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo __('Manage cache'); ?> 
                        </h1>

                        <form id="rdba-toolscache-form" class="rd-form horizontal">
                            <div class="form-result-placeholder"></div>

                            <div class="form-group cache-driver">
                                <label class="control-label"><?php echo __('Cache driver'); ?></label>
                                <div class="control-wrapper">
                                </div>
                            </div>
                            <div class="form-group cache-basePath rd-hidden">
                                <label class="control-label"><?php echo __('Base path'); ?></label>
                                <div class="control-wrapper">
                                </div>
                            </div>
                            <div class="form-group cache-totalSize rd-hidden">
                                <label class="control-label"><?php echo __('Total size'); ?></label>
                                <div class="control-wrapper">
                                </div>
                            </div>
                            <div class="form-group cache-totalFilesFolders rd-hidden">
                                <label class="control-label"><?php echo __('Total files and folders'); ?></label>
                                <div class="control-wrapper">
                                </div>
                            </div>
                            <div class="form-group cache-totalItems rd-hidden">
                                <label class="control-label"><?php echo __('Total items'); ?></label>
                                <div class="control-wrapper">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label" for="rdba-tools-cachecommand"><?php echo __('Please choose a command'); ?></label>
                                <div class="control-wrapper">
                                    <select id="rdba-tools-cachecommand" name="cache-command">
                                        <option value=""></option>
                                        <option value="clear"><?php echo esc__('Clear'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div id="rdba-cache-local-form-group" class="form-group rd-hidden">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <label><input type="checkbox" name="clear-local-session-storage" value="1"> <?php echo __('Delete session storage that was set by the framework and this module.'); ?></label>
                                    <?php
                                    // Omit local storage word because we don't use it in both framework, RdbAdmin. So, nothing to clear.
                                    // However, local storage and session storage should not be using **clear** but remove individually that was set by the framework or this module (if available).
                                    ?> 
                                </div>
                            </div>

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper submit-button-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Submit'); ?></button>
                                </div>
                            </div>
                        </form>