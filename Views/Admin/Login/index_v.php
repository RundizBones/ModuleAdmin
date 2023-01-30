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
                    if (function_exists('renderAlertHtml') && isset($pageAlertMessage)) {
                        echo renderAlertHtml(
                            $pageAlertMessage, 
                            ($pageAlertStatus ?? ''), 
                            (isset($pageAlertDismissable) && is_bool($pageAlertDismissable) ? $pageAlertDismissable : true)
                        );
                    }
                    ?></div>

                <form id="rdba-login-form" class="page-form-body rd-form" action="<?php if (isset($loginUrl)) {echo htmlspecialchars($loginUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($loginMethod) ? strtolower($loginMethod) : 'post'); ?>">
                    <div class="form-group">
                        <label class="control-label screen-reader-only" for="user_login_or_email" aria-hidden="true"><?php echo __('Username or Email'); ?></label>
                        <div class="control-wrapper">
                            <input id="user_login_or_email" class="user_login_or_email" type="text" name="user_login_or_email" value="<?php if (isset($user_login_or_email)) {echo $user_login_or_email;} ?>" autofocus="on" maxlength="190" placeholder="<?php echo esc__('Username or Email'); ?>" required="">
                        </div>
                    </div>
                    <div class="form-group form-group-password">
                        <label class="control-label screen-reader-only" for="user_password" aria-hidden="true"><?php echo __('Password'); ?></label>
                        <div class="control-wrapper">
                            <input id="user_password" class="user_password" type="password" name="user_password" value="" maxlength="160" placeholder="<?php echo esc__('Password'); ?>" required="">
                        </div>
                    </div>
                    <?php // antibot field ?> 
                    <div class="form-group rd-hidden" aria-hidden="true">
                        <label class="control-label" for="<?php echo $honeypotName; ?>"><?php echo __('Please skip this field.'); ?></label>
                        <div class="control-wrapper">
                            <input id="<?php echo $honeypotName; ?>" class="<?php echo $honeypotName; ?>" type="text" name="<?php echo $honeypotName; ?>" value="" autocomplete="off" maxlength="50">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="control-wrapper">
                            <label>
                                <input type="checkbox" name="remember" value="1"<?php if (isset($remember) && $remember === '1') {echo ' checked="checked"';} ?>> 
                                <?php echo __('Remember me'); ?> 
                            </label>
                        </div>
                    </div>
                    <?php
                    /*
                     * PluginHook: Rdb\Modules\RdbAdmin\Views\Admin\Login\index_v.before.loginbutton
                     * PluginHookDescription: Hook on login page, before display login button.
                     * PluginHookParam: None.
                     * PluginHookReturn: None.
                     * PluginHookSince: 1.2.0
                     */
                    /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
                    $Plugins = $this->Container->get('Plugins');
                    $Plugins->doHook(
                        'Rdb\Modules\RdbAdmin\Views\Admin\Login\index_v.before.loginbutton'
                    );
                    unset($Plugins);
                    ?> 
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary rdba-login-button" type="submit">
                                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> <?php echo __('Login'); ?>
                            </button>
                        </div>
                    </div>
                </form><!--.page-form-body-->

                <p class="rdba-links-under-form">
                    <span><a id="link-forgot-loginpassword" href="<?php if (isset($forgotLoginPassUrl)) {echo htmlspecialchars($forgotLoginPassUrl, ENT_QUOTES);} ?>"><?php echo __('Forgot username or password?'); ?></a></span>
                    <span><a id="link-register-new-account" href="<?php if (isset($registerUrl)) {echo htmlspecialchars($registerUrl, ENT_QUOTES);} ?>"><?php echo __('Create new account'); ?></a></span>
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