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

                <form id="rdba-confirm-register-form" class="page-form-body rd-form" action="<?php if (isset($registerConfirmUrl)) {echo htmlspecialchars($registerConfirmUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($registerConfirmMethod) ? strtolower($registerConfirmMethod) : 'post'); ?>">
                    <input type="hidden" name="tokenValue" value="<?php if (isset($tokenValue)) {echo htmlspecialchars($tokenValue, ENT_QUOTES);} ?>">
                    <?php if (isset($showSetPasswordFields) && $showSetPasswordFields === true) { ?> 
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
                    <?php }// endif ; ?> 
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary large rdba-submit-button" type="submit">
                                <?php echo __('Confirm'); ?>
                            </button>
                        </div>
                    </div>
                </form><!--.page-form-body-->

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