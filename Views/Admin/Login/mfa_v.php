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

                <form id="rdba-loginmfa-form" class="page-form-body rd-form" action="<?php if (isset($loginMfaUrl)) {echo htmlspecialchars($loginMfaUrl, ENT_QUOTES);} ?>" method="<?php echo (isset($loginMfaMethod) ? strtolower($loginMfaMethod) : 'post'); ?>">
                    <div class="form-group">
                        <label class="control-label screen-reader-only" for="login2stepverification_key" aria-hidden="true"><?php echo __('2 Step verification code'); ?></label>
                        <div class="control-wrapper">
                            <input id="login2stepverification_key" class="login2stepverification_key" type="password" name="login2stepverification_key" value="" autofocus="on" maxlength="160" placeholder="<?php echo esc__('2 Step verification code'); ?>" required="">
                        </div>
                    </div>
                    <div class="form-group submit-button-row">
                        <div class="control-wrapper">
                            <button class="rd-button primary rdba-submit-button" type="submit">
                                <?php echo __('Verify'); ?>
                            </button>
                        </div>
                    </div>
                </form><!--.page-form-body-->

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