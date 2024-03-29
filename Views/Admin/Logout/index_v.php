<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
        <div class="rd-columns-flex-container rd-block-level-margin-bottom">
            <div class="column">
                <?php if (isset($pageTitle)) { ?><h1 class="page-header rd-block-level-margin-bottom"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?></h1><?php } ?> 

                <div class="form-result-placeholder"></div>

                <form id="rdba-logout-form" class="page-form-body rd-form" action="<?php if (isset($logoutUrl)) {echo htmlspecialchars($logoutUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($logoutMethod) ? strtolower($logoutMethod) : 'post') ?>">
                    <div class="user-icon-wrapper">
                        <i class="fa-solid fa-user fontawesome-icon user-icon" aria-hidden="true"></i>
                    </div>
                    <div class="form-group">
                        <div class="control-wrapper">
                            <label>
                                <input type="checkbox" name="logoutAllDevices" value="1"<?php if (isset($logoutAllDevices) && $logoutAllDevices === '1') {echo ' checked="checked"';} ?>> 
                                <?php echo __('Logout on all devices'); ?> 
                            </label>
                        </div>
                    </div>
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary rdba-submit-button" type="submit">
                                <?php echo __('Logout'); ?>
                            </button>
                        </div>
                    </div>
                </form><!--.page-form-body-->

                <?php if (isset($urlAdminLogin)) { ?> 
                <p class="rdba-links-under-form">
                    <span><a href="<?php echo htmlspecialchars($urlAdminLogin, ENT_QUOTES); ?>"><?php echo __('Go to login page'); ?></a></span>
                </p>
                <?php }// endif; ?> 
            </div><!--.column-->
        </div><!--.rd-columns-flex-container-->