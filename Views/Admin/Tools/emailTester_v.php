<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                        </h1>

                        <form id="rdba-toolsemailtester-form" class="rd-form horizontal">
                            <div class="form-result-placeholder"></div>

                            <div class="form-group">
                                <label class="control-label" for="rdba-tools-emailtester-toemail"><?php echo __('Target email'); ?></label>
                                <div class="control-wrapper">
                                    <input id="rdba-tools-emailtester-toemail" type="email" name="toemail" placeholder="to@tmail.tld">
                                </div>
                            </div>

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper submit-button-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Submit'); ?></button>
                                </div>
                            </div>
                        </form>