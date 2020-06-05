<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo __('Main settings'); ?> 
                        </h1>

                        <form id="rdba-search-settings-form" class="text-center">
                            <input id="rdba-search-settings-input" type="text" name="rdba-search-settings-input" placeholder="<?php echo esc__('Search settings'); ?>">
                        </form>

                        <form id="rdba-settings-form" class="rd-form horizontal">
                            <div class="form-result-placeholder"></div>

                            <div class="tabs rd-tabs">
                                <ul class="rd-tabs-nav">
                                    <li><a href="#tab-1"><?php echo __('Website'); ?></a></li>
                                    <li><a href="#tab-2"><?php echo __('User'); ?></a></li>
                                    <li><a href="#tab-3"><?php echo __('Email'); ?></a></li>
                                </ul>
                                <div id="tab-1" class="rd-tabs-content">
                                    <div class="form-group">
                                        <label class="control-label" for="rdbadmin_SiteName"><?php echo __('Website name'); ?> <em>*</em></label>
                                        <div class="control-wrapper">
                                            <input id="rdbadmin_SiteName" type="text" name="rdbadmin_SiteName" value="" maxlength="255" required="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label" for="rdbadmin_SiteTimezone"><?php echo __('Timezone'); ?></label>
                                        <div class="control-wrapper">
                                            <select id="rdbadmin_SiteTimezone" name="rdbadmin_SiteTimezone">
                                                <?php
                                                if (isset($timezones) && is_array($timezones)) {
                                                    foreach ($timezones as $optgroup => $items) {
                                                        echo '<optgroup label="' . $optgroup . '">' . PHP_EOL;
                                                        if (is_array($items)) {
                                                            foreach ($items as $key => $item) {
                                                                echo '    <option value="' . $key . '">' . $item['name'] . ' (UTC ' . $item['offset'] . ')</option>' . PHP_EOL;
                                                            }// endforeach;
                                                            unset($item, $key);
                                                        }
                                                        echo '</optgroup>' . PHP_EOL;
                                                    }// endforeach;
                                                    unset($items, $optgroup);
                                                }
                                                ?> 
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group rd-columns-flex-container">
                                        <label class="control-label" for="rdbadmin_AdminItemsPerPage"><?php echo __('Number of items per page'); ?></label>
                                        <div class="control-wrapper col-md-3">
                                            <input id="rdbadmin_AdminItemsPerPage" type="number" name="rdbadmin_AdminItemsPerPage" value="" min="1" max="200">
                                        </div>
                                    </div>
                                </div><!--#tab-1-->

                                <div id="tab-2" class="rd-tabs-content">
                                    <div class="rd-columns-flex-container fix-columns-container-edge">
                                        <div class="column col-md-6">
                                            <h2><?php echo __('Registration'); ?></h2>
                                            <div class="form-group">
                                                <div class="control-wrapper">
                                                    <label>
                                                        <input id="rdbadmin_UserRegister" type="checkbox" name="rdbadmin_UserRegister" value="1">
                                                        <?php echo __('Anyone can be register'); ?> 
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="control-wrapper">
                                                    <label>
                                                        <input id="rdbadmin_UserRegisterNotifyAdmin" type="checkbox" name="rdbadmin_UserRegisterNotifyAdmin" value="1">
                                                        <?php echo __('Send notification email to administrators'); ?> 
                                                        <span class="form-description">(<?php echo __('A notification email still be sent if registration verification must be done by an administrator.') ?>)</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserRegisterVerification"><?php echo __('Registration verification'); ?></label>
                                                <div class="control-wrapper">
                                                    <select id="rdbadmin_UserRegisterVerification" name="rdbadmin_UserRegisterVerification">
                                                        <option value="0"><?php echo esc__('No verification'); ?></option>
                                                        <option value="1"><?php echo esc__('Verify by the link in user\'s email'); ?></option>
                                                        <option value="2"><?php echo esc__('Verify by an administrator'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserRegisterWaitVerification"><?php echo __('Registration verification wait time'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserRegisterWaitVerification" class="rd-input-control" type="number" name="rdbadmin_UserRegisterWaitVerification" value="" min="1">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo __('days'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description"><?php echo __('How many days that user needs to take action to verify their email on register or added a new user by admin?') ?></div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserRegisterNotifyAdminEmails"><?php echo __('Admin notification emails'); ?></label>
                                                <div class="control-wrapper">
                                                    <input id="rdbadmin_UserRegisterNotifyAdminEmails" type="text" name="rdbadmin_UserRegisterNotifyAdminEmails" value="" maxlength="255">
                                                    <div class="form-description">
                                                        <?php 
                                                        echo __('The administrator\'s emails to notify where there is new user registration.'); 
                                                        echo ' ';
                                                        echo __('Use comma %s to separate multiple emails.', '(<code>,</code>)');
                                                        ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserRegisterDisallowedName"><?php echo __('Disallowed name'); ?></label>
                                                <div class="control-wrapper">
                                                    <input id="rdbadmin_UserRegisterDisallowedName" type="text" name="rdbadmin_UserRegisterDisallowedName" value="" maxlength="500">
                                                    <div class="form-description">
                                                        <?php
                                                        echo __('Disallowed name to use in username, email, display name.');
                                                        echo ' ';
                                                        echo __('Use comma %s to separate multiple names.', '(<code>,</code>)');
                                                        echo ' ';
                                                        echo __('Use asterisk %s as wild card in each name.', '(<code>*</code>)');
                                                        ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserRegisterDefaultRoles"><?php echo __('Default role'); ?></label>
                                                <div class="control-wrapper">
                                                    <select id="rdbadmin_UserRegisterDefaultRoles" name="rdbadmin_UserRegisterDefaultRoles[]" multiple="multiple">
                                                        <?php
                                                        if (isset($listRoles['items']) && is_array($listRoles['items'])) {
                                                            foreach ($listRoles['items'] as $role) {
                                                                echo '<option value="' . $role->userrole_id . '">' . $role->userrole_name . '</option>' . PHP_EOL;
                                                            }// endforeach;
                                                            unset($role);
                                                        }
                                                        unset($listRoles);
                                                        ?> 
                                                    </select>
                                                    <div class="form-description">
                                                        <?php echo __('Default role to use when user register their account or add new user from administrator page.'); ?> 
                                                    </div>
                                                </div>
                                            </div>

                                            <h2><?php echo __('Login & Security'); ?></h2>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserLoginNotRememberLength"><?php echo __('Login length without remember'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserLoginNotRememberLength" class="rd-input-control" type="number" name="rdbadmin_UserLoginNotRememberLength" value="" min="0">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo __('days'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description">
                                                        <?php 
                                                        echo __('Number of days for cookie to be expire when user login without remember option checked. Use zero to keep session until browser close.');
                                                        ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserLoginRememberLength"><?php echo __('Login length with remember'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserLoginRememberLength" class="rd-input-control" type="number" name="rdbadmin_UserLoginRememberLength" value="" min="1">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo __('days'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description">
                                                        <?php 
                                                        echo __('Number of days for cookie to be expire when user login with remember option checked.');
                                                        ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserLoginCaptcha"><?php echo __('Login captcha'); ?></label>
                                                <div class="control-wrapper">
                                                    <select id="rdbadmin_UserLoginCaptcha" name="rdbadmin_UserLoginCaptcha">
                                                        <option value="0"><?php echo esc__('Do not use'); ?></option>
                                                        <option value="1"><?php echo esc__('Use until login success'); ?></option>
                                                        <option value="2"><?php echo esc__('Always use'); ?></option>
                                                    </select>
                                                    <div class="form-description">
                                                        <?php echo __('To use login captcha until login success, the captcha will be skipped until login cookies expired with these conditions.'); ?><br>
                                                        <?php echo __('If user login without remember then it will be expired in one day.'); ?><br>
                                                        <?php echo __('If user login with remember then it will be expired within remember length multiply with 6 days.'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="control-wrapper">
                                                    <label>
                                                        <input id="rdbadmin_UserLoginBruteforcePreventByIp" type="checkbox" name="rdbadmin_UserLoginBruteforcePreventByIp" value="1">
                                                        <?php echo __('Prevent brute-force attack by IP address'); ?> 
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="control-wrapper">
                                                    <label>
                                                        <input id="rdbadmin_UserLoginBruteforcePreventByDc" type="checkbox" name="rdbadmin_UserLoginBruteforcePreventByDc" value="1">
                                                        <?php echo __('Prevent brute-force attack by device cookie'); ?> 
                                                    </label>
                                                    <div class="form-description">
                                                        <?php echo __('Read more about device cookie at %1$s.', '<a href="https://owasp.org/www-community/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies" target="owasp">OWASP</a>'); ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserLoginMaxFail"><?php echo __('Maximum login failed'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserLoginMaxFail" class="rd-input-control" type="number" name="rdbadmin_UserLoginMaxFail" value="" min="2" max="100">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo p__('Number of times such as 3 times.', 'times'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description">
                                                        <?php 
                                                        echo __('Maximum of failed login allowed.'); 
                                                        echo ' ';
                                                        echo __('This will work only when one of brute-force option were checked.');
                                                        ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserLoginMaxFailWait"><?php echo __('Login failed wait time'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserLoginMaxFailWait" class="rd-input-control" type="number" name="rdbadmin_UserLoginMaxFailWait" value="" min="1" max="500">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo __('minutes'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description">
                                                        <?php 
                                                        echo __('Wait time in minutes if user reach maximum login failed.'); 
                                                        echo ' ';
                                                        echo __('This will work only when one of brute-force option were checked.');
                                                        ?> 
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!--.col-md-6-->

                                        <div class="column col-md-6">
                                            <h2><?php echo __('Other user settings'); ?></h2>
                                            <div class="form-group">
                                                <label>
                                                    <input id="rdbadmin_UserConfirmEmailChange" type="checkbox" name="rdbadmin_UserConfirmEmailChange" value="1">
                                                    <?php echo __('Confirmation is required when users change their email'); ?> 
                                                    <span class="form-description"><?php echo __('A confirmation message with link will be sent to new user\'s email.'); ?></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserConfirmWait"><?php echo __('Confirmation wait time'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserConfirmWait" class="rd-input-control" type="number" name="rdbadmin_UserConfirmWait" value="" min="1" max="200">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo __('minutes'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description">
                                                        <?php 
                                                        echo __('Number of minutes that a user must take action when waiting for confirmation.'); 
                                                        echo ' ';
                                                        echo __('Example: reset password, change email, prevent simultaneuos login link, etc.');
                                                        ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserLoginLogsKeep"><?php echo __('Logins log expiration'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserLoginLogsKeep" class="rd-input-control" type="number" name="rdbadmin_UserLoginLogsKeep" value="" min="90">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo __('days'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description">
                                                        <?php echo __('How many days that user\'s failed logins data to keep in database?') ?> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>
                                                    <input id="rdbadmin_UserDeleteSelfGrant" type="checkbox" name="rdbadmin_UserDeleteSelfGrant" value="1">
                                                    <?php echo __('Allow user to delete their account'); ?> 
                                                    <span class="form-description"><?php echo __('If allowed, once account was deleted it will be stay in the database for days before actual deleted.'); ?></span>
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="rdbadmin_UserDeleteSelfKeep"><?php echo __('Actual delete user'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <input id="rdbadmin_UserDeleteSelfKeep" class="rd-input-control" type="number" name="rdbadmin_UserDeleteSelfKeep" value="" min="10">
                                                        <div class="rd-input-group-block append">
                                                            <span class="rd-input-group-block-text"><?php echo __('days'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-description">
                                                        <?php echo __('On delete user wether delete themself or by admin, How many days before it gets actual delete from database?') ?> 
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!--.col-md-6-->
                                    </div><!--.rd-columns-flex-container-->
                                </div><!--#tab-2-->

                                <div id="tab-3" class="rd-tabs-content">
                                    <div class="form-group">
                                        <label class="control-label" for="rdbadmin_MailProtocol"><?php echo __('Email protocol'); ?></label>
                                        <div class="control-wrapper">
                                            <select id="rdbadmin_MailProtocol" name="rdbadmin_MailProtocol">
                                                <option value="mail"><?php echo esc__('Mail function'); ?></option>
                                                <option value="sendmail"><?php echo esc__('Sendmail function'); ?></option>
                                                <option value="smtp"><?php echo esc__('SMTP'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <fieldset>
                                        <legend><?php echo __('Sendmail'); ?></legend>
                                        <div class="form-group">
                                            <label class="control-label" for="rdbadmin_MailPath"><?php echo __('Sendmail path'); ?></label>
                                            <div class="control-wrapper">
                                                <input id="rdbadmin_MailPath" type="text" name="rdbadmin_MailPath" value="" maxlength="255">
                                            </div>
                                        </div>
                                    </fieldset>
                                    <fieldset>
                                        <legend><?php echo __('SMTP'); ?></legend>
                                        <div class="form-group">
                                            <label class="control-label" for="rdbadmin_MailSmtpHost"><?php echo __('SMTP host'); ?></label>
                                            <div class="control-wrapper">
                                                <input id="rdbadmin_MailSmtpHost" type="text" name="rdbadmin_MailSmtpHost" value="" maxlength="255">
                                            </div>
                                        </div>
                                        <div class="form-group rd-columns-flex-container">
                                            <label class="control-label" for="rdbadmin_MailSmtpPort"><?php echo __('SMTP port'); ?></label>
                                            <div class="control-wrapper col-md-3">
                                                <input id="rdbadmin_MailSmtpPort" type="number" name="rdbadmin_MailSmtpPort" value="" min="1">
                                            </div>
                                        </div>
                                        <div class="form-group rd-columns-flex-container">
                                            <label class="control-label" for="rdbadmin_MailSmtpSecure"><?php echo __('SMTP encryption'); ?></label>
                                            <div class="control-wrapper col-md-3">
                                                <select id="rdbadmin_MailSmtpSecure" name="rdbadmin_MailSmtpSecure">
                                                    <option value=""><?php echo esc__('No encryption'); ?></option>
                                                    <option value="ssl"><?php echo esc__('SSL'); ?></option>
                                                    <option value="tls"><?php echo esc__('TLS'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label" for="rdbadmin_MailSmtpUser"><?php echo __('SMTP username'); ?></label>
                                            <div class="control-wrapper">
                                                <input id="rdbadmin_MailSmtpUser" type="text" name="rdbadmin_MailSmtpUser" value="" maxlength="255" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label" for="rdbadmin_MailSmtpPass"><?php echo __('SMTP password'); ?></label>
                                            <div class="control-wrapper">
                                                <input id="rdbadmin_MailSmtpPass" type="password" name="rdbadmin_MailSmtpPass" value="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label"></label>
                                            <div class="control-wrapper">
                                                <button id="rdbadmin_MailSmtpTestConnectionButton" class="rd-button" type="button"><?php echo __('Test connection'); ?></button>
                                                <span id="rdbadmin_MailSmtpTestConnectionResultPlaceholder"></span>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <div class="form-group">
                                        <label class="control-label" for="rdbadmin_MailSenderEmail"><?php echo __('Sender email'); ?></label>
                                        <div class="control-wrapper">
                                            <input id="rdbadmin_MailSenderEmail" type="email" name="rdbadmin_MailSenderEmail" value="" maxlength="255">
                                            <div class="form-description">
                                                <?php echo __('An email that will be use as sender such as registration verification email.'); ?> 
                                            </div>
                                        </div>
                                    </div>
                                </div><!--#tab-3-->
                            </div><!--.rd-tabs-->

                            <div class="rd-columns-flex-container submit-button-row">
                                <div class="column">
                                    <div class="column submit-button-wrapper"><!--this .column is just to align the space on left/right to match padding in tab's content-->
                                        <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </form>
