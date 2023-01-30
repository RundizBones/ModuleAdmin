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
                    if ($formResultStatus === 'error') {
                        $alertClass = 'alert-danger';
                    } elseif ($formResultStatus === 'success') {
                        $alertClass = 'alert-success';
                    } else {
                        $alertClass = 'alert-warning';
                    }
                    echo '<div class="rd-alertbox ' . $alertClass . '">' . PHP_EOL;
                    echo $formResultMessage . PHP_EOL;
                    echo '</div>' . PHP_EOL;
                }
                ?></div>

                <?php if (!isset($hideForm) || (isset($hideForm) && $hideForm !== true)) { ?> 
                <form id="rdba-register-form" class="page-form-body rd-form" action="<?php if (isset($registerUrl)) {echo htmlspecialchars($registerUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($registerMethod) ? strtolower($registerMethod) : 'post'); ?>">
                    <div class="form-group">
                        <label class="control-label" for="user_login"><?php echo __('Username'); ?></label>
                        <div class="control-wrapper">
                            <input id="user_login" class="user_login" type="text" name="user_login" value="<?php if (isset($user_login)) {echo $user_login;} ?>" maxlength="190" required="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="user_email"><?php echo __('Email'); ?></label>
                        <div class="control-wrapper">
                            <input id="user_email" class="user_email" type="email" name="user_email" value="<?php if (isset($user_email)) {echo $user_email;} ?>" maxlength="190" required="">
                        </div>
                    </div>
                    <div class="rd-block-level-margin-bottom"></div>
                    <div class="form-group">
                        <label class="control-label" for="user_password"><?php echo __('Password'); ?></label>
                        <div class="control-wrapper">
                            <input id="user_password" class="user_password" type="password" name="user_password" value="" maxlength="160" required="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="confirm_password"><?php echo __('Confirm password'); ?></label>
                        <div class="control-wrapper">
                            <input id="confirm_password" class="confirm_password" type="password" name="confirm_password" value="" maxlength="160" required="">
                        </div>
                    </div>
                    <?php // antibot field ?> 
                    <div class="form-group rd-hidden" aria-hidden="true">
                        <label class="control-label" for="<?php echo $honeypotName; ?>"><?php echo __('Please skip this field.'); ?></label>
                        <div class="control-wrapper">
                            <input id="<?php echo $honeypotName; ?>" class="<?php echo $honeypotName; ?>" type="text" name="<?php echo $honeypotName; ?>" value="" autocomplete="off" maxlength="50">
                        </div>
                    </div>
                    <?php if (isset($configDb['rdbadmin_UserRegisterVerification']) && $configDb['rdbadmin_UserRegisterVerification'] !== '0') { ?> 
                    <div class="form-group">
                        <?php
                        if ($configDb['rdbadmin_UserRegisterVerification'] === '1') {
                            echo __('The confirmation email will be sent to you and you have to click on confirm link in your email.');
                        } elseif ($configDb['rdbadmin_UserRegisterVerification'] === '2') {
                            echo __('You have to wait for administrator to confirm your registration.');
                        }
                        ?> 
                    </div>
                    <?php }// endif; 
                    /*
                     * PluginHook: Rdb\Modules\RdbAdmin\Views\Admin\Register\index_v.before.registerbutton
                     * PluginHookDescription: Hook at before register button in register page.
                     * PluginHookParam: None.
                     * PluginHookReturn: None.
                     * PluginHookSince: 1.2.0
                     */
                    /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
                    $Plugins = $this->Container->get('Plugins');
                    $Plugins->doHook(
                        'Rdb\Modules\RdbAdmin\Views\Admin\Register\index_v.before.registerbutton'
                    );
                    unset($Plugins);
                    ?> 
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary rdba-submit-button" type="submit">
                                <?php echo __('Register'); ?>
                            </button>
                        </div>
                    </div>
                </form><!--.page-form-body-->
                <?php }// endif; ?> 

                <p class="rdba-links-under-form">
                    <span><a id="link-login-page" href="<?php if (isset($loginUrl)) {echo htmlspecialchars($loginUrl, ENT_QUOTES);} ?>"><?php echo __('Login'); ?></a></span>
                </p>

                <hr>

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
                            <input id="rdbaCurrentUrl" type="hidden" name="currentUrl" value="<?php echo $Url->getCurrentUrl() . $Url->getQuerystring(); ?>">
                            <input id="rdbaCurrentLanguageID" type="hidden" name="currentLanguageID" value="<?php echo htmlspecialchars(($_SERVER['RUNDIZBONES_LANGUAGE'] ?? ''), ENT_QUOTES); ?>">
                            <input id="rdbaSetLanguage_url" type="hidden" name="setLanguage_url" value="<?php if (isset($languagesResult['setLanguage_url'])) {echo htmlspecialchars($languagesResult['setLanguage_url'], ENT_QUOTES);} ?>">
                            <input id="rdbaSetLanguage_method" type="hidden" name="setLanguage_method" value="<?php if (isset($languagesResult['setLanguage_method'])) {echo htmlspecialchars($languagesResult['setLanguage_method'], ENT_QUOTES);} ?>">
                            <?php 
                            }// endif; 
                            unset($languagesResult);
                            ?> 
                        </div>
                    </div>
                </div><!--.rdba-language-switch-form-->
            </div><!--.column-->
        </div><!--.rd-columns-flex-container-->