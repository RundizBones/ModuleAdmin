<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
        <div class="rd-columns-flex-container rd-block-level-margin-bottom">
            <div class="column">
                <?php if (isset($pageTitle)) { ?><h1 class="page-header rd-block-level-margin-bottom"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?></h1><?php } ?> 

                <div class="form-result-placeholder"><?php
                if (isset($formResultStatus) && isset($formResultMessage)) {
                    echo renderAlertHtml($formResultMessage, $formResultStatus);
                }
                ?></div>

                <?php if (!isset($hideForm) || (isset($hideForm) && $hideForm !== true)) { ?> 
                <form id="rdba-forgot-form-reset" class="page-form-body rd-form" action="<?php if (isset($forgotLoginPassResetUrl)) {echo htmlspecialchars($forgotLoginPassResetUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($forgotLoginPassResetMethod) ? strtolower($forgotLoginPassResetMethod) : 'post'); ?>">
                    <div class="form-group">
                        <p class="control-wrapper">
                            <?php echo __('Your account: %1$s', '<strong>' . ($user_email ?? '') . '</strong>'); ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="control-label screen-reader-only" for="new_password" aria-hidden="true"><?php echo __('New password'); ?></label>
                        <div class="control-wrapper">
                            <input id="new_password" class="new_password" type="password" name="new_password" value="" autofocus="on" maxlength="160" placeholder="<?php echo esc__('New password'); ?>" required="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label screen-reader-only" for="confirm_new_password" aria-hidden="true"><?php echo __('Confirm new password'); ?></label>
                        <div class="control-wrapper">
                            <input id="confirm_new_password" class="confirm_new_password" type="password" name="confirm_new_password" value="" maxlength="160" placeholder="<?php echo esc__('Confirm new password'); ?>">
                        </div>
                    </div>
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary rdba-submit-button" type="submit">
                                <?php echo __('Submit'); ?>
                            </button>
                        </div>
                    </div>
                </form><!--.page-form-body-->

                <form id="rdba-login-form" class="page-form-body rd-form rd-hidden" action="<?php if (isset($loginUrl)) {echo htmlspecialchars($loginUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($loginMethod) ? strtolower($loginMethod) : 'post'); ?>">
                    <?php 
                    if (isset($login2StepVerification) && $login2StepVerification === true) {
                    ?> 
                    <a class="rd-button primary" href="<?php echo ($loginPageUrl ?? ''); ?>"> <i class="fas fa-sign-in-alt" aria-hidden="true"></i> <?php echo __('Proceed to login'); ?></a>
                    <?php
                    } else {
                    ?> 
                    <input id="user_login_or_email" type="hidden" name="user_login_or_email" value="<?php echo htmlspecialchars(($user_email ?? ''), ENT_QUOTES); ?>">
                    <input id="user_password" type="hidden" name="user_password">
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary rdba-submit-button" type="submit">
                                <i class="fas fa-sign-in-alt" aria-hidden="true"></i> <?php echo __('Proceed to login'); ?>
                            </button>
                        </div>
                    </div>
                    <?php
                    }// endif; $login2StepVerification
                    ?> 
                </form>
                <?php }// endif; hideform ?> 

                <hr class="rdba-hr-form-separator">

                <div class="rd-form rdba-language-switch-form">
                    <div class="form-group">
                        <label class="control-label" for="rundizbones-languages"><?php echo __('Change language'); ?></label>
                        <div class="control-wrapper">
                            <?php
                            $languagesResult = $Modules->execute('Rdb\\Modules\\Languages\\Controllers\\Languages:index', []);
                            $languagesResult = unserialize($languagesResult);
                            if (isset($languagesResult['languages']) && is_array($languagesResult['languages']) && !empty($languagesResult['languages'])) {
                            ?>
                            <select id="rundizbones-languages-selectbox" class="rundizbones-languages-selectbox" name="rundizbones-languages">
                                <?php foreach ($languagesResult['languages'] as $locale => $item) { ?> 
                                <option value="<?php echo $locale; ?>"<?php if (isset($languagesResult['currentLanguage']) && $languagesResult['currentLanguage'] === $locale) { ?> selected="selected"<?php } ?>>
                                    <?php echo ($item['languageName'] ?? ''); ?> 
                                </option>
                                <?php }// endforeach;
                                unset($item, $locale);
                                ?> 
                            </select>
                            <input id="currentUrl" type="hidden" name="currentUrl" value="<?php echo $Url->getCurrentUrl() . $Url->getQuerystring(); ?>">
                            <input id="setLanguage_url" type="hidden" name="setLanguage_url" value="<?php if (isset($languagesResult['setLanguage_url'])) {echo htmlspecialchars($languagesResult['setLanguage_url'], ENT_QUOTES);} ?>">
                            <input id="setLanguage_method" type="hidden" name="setLanguage_method" value="<?php if (isset($languagesResult['setLanguage_method'])) {echo htmlspecialchars($languagesResult['setLanguage_method'], ENT_QUOTES);} ?>">
                            <?php 
                            }// endif; 
                            unset($languagesResult);
                            ?> 
                        </div>
                    </div>
                </div><!--.rdba-language-switch-form-->
            </div><!--.column-->
        </div><!--.rd-columns-flex-container-->