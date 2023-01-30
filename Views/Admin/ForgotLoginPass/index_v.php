<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
        <div class="rd-columns-flex-container rd-block-level-margin-bottom">
            <div class="column">
                <?php if (isset($pageTitle)) { ?><h1 class="page-header rd-block-level-margin-bottom"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?></h1><?php } ?> 

                <div class="form-result-placeholder"></div>

                <form id="rdba-forgot-form" class="page-form-body rd-form" action="<?php if (isset($forgotLoginPassUrl)) {echo htmlspecialchars($forgotLoginPassUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($forgotLoginPassMethod) ? strtolower($forgotLoginPassMethod) : 'post'); ?>">
                    <p class="form-group">
                        <?php echo __('Enter your email address to reset your password.'); ?> 
                        <?php echo __('You may need to check your spam folder.'); ?> 
                    </p>
                    <div class="form-group">
                        <label class="control-label screen-reader-only" for="user_email" aria-hidden="true"><?php echo __('Username or Email'); ?></label>
                        <div class="control-wrapper">
                            <input id="user_email" class="user_email" type="email" name="user_email" value="<?php if (isset($user_email)) {echo $user_email;} ?>" autofocus="on" maxlength="190" placeholder="<?php echo esc__('Email'); ?>" required="">
                        </div>
                    </div>
                    <?php // antibot field ?> 
                    <div class="form-group rd-hidden" aria-hidden="true">
                        <label class="control-label" for="<?php echo $honeypotName; ?>"><?php echo __('Please skip this field.'); ?></label>
                        <div class="control-wrapper">
                            <input id="<?php echo $honeypotName; ?>" class="<?php echo $honeypotName; ?>" type="text" name="<?php echo $honeypotName; ?>" value="" autocomplete="off" maxlength="50">
                        </div>
                    </div>
                    <?php
                    /*
                     * PluginHook: Rdb\Modules\RdbAdmin\Views\Admin\ForgotLoginPass\index_v.before.submitbutton
                     * PluginHookDescription: Hook at before submit button in forgot login or password page.
                     * PluginHookParam: None.
                     * PluginHookReturn: None.
                     * PluginHookSince: 1.2.0
                     */
                    /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
                    $Plugins = $this->Container->get('Plugins');
                    $Plugins->doHook(
                        'Rdb\Modules\RdbAdmin\Views\Admin\ForgotLoginPass\index_v.before.submitbutton'
                    );
                    unset($Plugins);
                    ?> 
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary rdba-submit-button" type="submit">
                                <?php echo __('Submit'); ?>
                            </button>
                        </div>
                    </div>
                </form><!--.page-form-body-->

                <p class="rdba-links-under-form">
                    <span><a id="link-login-page" href="<?php if (isset($loginUrl)) {echo htmlspecialchars($loginUrl, ENT_QUOTES);} ?>"><?php echo __('Login'); ?></a></span>
                </p>

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