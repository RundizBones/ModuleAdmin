<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
/* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
$Plugins = $this->Container->get('Plugins');
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
                                    <?php
                                    /*
                                     * PluginHook: Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsNav.last
                                     * PluginHookDescription: Hook after last tabs navigation (inside `<ul>` element) in settings views.
                                     * PluginHookParam: None.
                                     * PluginHookReturn: None.
                                     * PluginHookSince: 1.1.7
                                     */
                                    $Plugins->doHook(
                                        'Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsNav.last'
                                    );
                                    ?> 
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
                                            <div class="form-description">
                                                <?php echo __('Number of items per page in administrator pages.'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label" for="rdbadmin_SiteFavicon"><?php echo __('Favicon'); ?></label>
                                        <div class="control-wrapper">
                                            <div id="current-favicon-preview"></div>
                                            <button id="prog-delete-favicon-button" class="prog-delete-favicon-button rd-hidden rd-button danger" type="button">
                                                <i class="fa-solid fa-trash"></i>
                                                <?php echo __('Delete this favicon'); ?> 
                                            </button>
                                            <div id="rdbadmin-favicon-dropzone" class="rdbadmin-favicon-dropzone rdbadmin-file-dropzone" title="<?php echo esc__('Drop a file into this area to start upload.'); ?>">
                                                <span id="rdbadmin-favicon-choose-files-button" class="rd-button info rd-inputfile rdbadmin-button-upload-file" tabindex="0">
                                                    <span class="label"><i class="fa-solid fa-file-arrow-up"></i> <?php echo __('Choose a file'); ?></span>
                                                    <input id="rdbadmin_SiteFavicon" type="file" name="rdbadmin_SiteFavicon" tabindex="-1" accept="<?php echo ($favicon['allowedFileExtensions'] ?? '.ico,.gif,.png'); ?>">
                                                </span>
                                                <span id="rdbadmin-favicon-upload-status-placeholder"></span>
                                                <div id="rdbadmin-favicon-dropzone-description" class="form-description">
                                                    <?php 
                                                    echo __('Click on choose a file or drop a file here to start upload.'); 
                                                    echo ' ';
                                                    printf(__('Max file size %s.'), ini_get('upload_max_filesize')); 
                                                    echo ' ';
                                                    printf(__('Recommended image size is %1$s pixels.'), ($favicon['recommendedSize'] ?? '512x512'));
                                                    ?> 
                                                </div>
                                            </div><!--#rdbadmin-favicon-dropzone-->
                                        </div>
                                    </div>

                                    <fieldset>
                                        <legend><?php echo __('API access'); ?></legend>
                                        <div class="form-group">
                                            <label class="control-label" for="rdbadmin_SiteAllowOrigins"><?php echo __('Allow origins'); ?></label>
                                            <div class="control-wrapper">
                                                <input id="rdbadmin_SiteAllowOrigins" type="text" name="rdbadmin_SiteAllowOrigins" value="" maxlength="500">
                                                <div class="form-description">
                                                    <?php 
                                                    printf(
                                                        __('The %1$s header to be sent to client. To set multiple origins, separate them with comma (%2$s) and the system will be use one per header.'),
                                                        '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin" target="cors-allow-origin"><code>Access-Control-Allow-Origin</code></a>' .
                                                        ' <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin" target="cors-allow-origin"><i class="fa-solid fa-up-right-from-square"></i></a>',
                                                        '<code>,</code>'
                                                    );
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label" for="rdbadmin_SiteAPILimitAccess"><?php echo __('Limited access to API'); ?></label>
                                            <div class="control-wrapper">
                                                <label>
                                                    <input id="rdbadmin_SiteAPILimitAccess" type="checkbox" name="rdbadmin_SiteAPILimitAccess" value="1">
                                                    <?php echo __('Require API key to access via REST API.'); ?> 
                                                </label>
                                                <div class="form-description">
                                                    <?php
                                                    echo __('If you limited access via REST API, the API key is required.');
                                                    ?> 
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label" for="rdbadmin_SiteAPIKey"><?php echo __('API key'); ?></label>
                                            <div class="control-wrapper">
                                                <input id="rdbadmin_SiteAPIKey" type="text" name="rdbadmin_SiteAPIKey" value="" maxlength="200">
                                                <div class="form-description">
                                                    <?php
                                                    echo __('The API key to access via REST API.');
                                                    echo ' ';
                                                    printf(
                                                        __('Recommended %1$d to %2$d characters long, contain letters, numbers, and/or special characters without space.'),
                                                        30,
                                                        80
                                                    );
                                                    echo '<br>' . PHP_EOL;
                                                    printf(
                                                        __('To access REST API with key, use one of these headers: %1$s or use method %2$s body, or use query string parameter %3$s.'),
                                                        '<code>Authorization</code>, <code>X-Authorization</code>',
                                                        '<code>POST</code>',
                                                        '<code>rdba-api-key</code>'
                                                    );
                                                    ?> 
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label"></label>
                                            <div class="control-wrapper">
                                                <button id="rdbadmin_SiteRegenerateAPIKey" class="rd-button" type="button"><?php echo __('Regenerate API key'); ?></button>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <?php
                                    /*
                                     * PluginHook: Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsContent.tab-1
                                     * PluginHookDescription: Hook at the bottom of tabs content in tab 1.
                                     * PluginHookParam: None.
                                     * PluginHookReturn: None.
                                     * PluginHookSince: 1.1.7
                                     */
                                    $Plugins->doHook(
                                        'Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsContent.tab-1'
                                    );
                                    ?> 
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
                                                        echo sprintf(__('Use comma %s to separate multiple emails.'), '(<code>,</code>)');
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
                                                        echo sprintf(__('Use comma %s to separate multiple names.'), '(<code>,</code>)');
                                                        echo ' ';
                                                        echo sprintf(__('Use asterisk %s as wild card in each name.'), '(<code>*</code>)');
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
                                                <label class="control-label" for="rdbadmin_UserLoginNotRememberLength"><?php echo __('Login time period without remember'); ?></label>
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
                                                <label class="control-label" for="rdbadmin_UserLoginRememberLength"><?php echo __('Login time period with remember'); ?></label>
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
                                                        <?php echo sprintf(__('Read more about device cookie at %1$s.'), '<a href="https://owasp.org/www-community/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies" target="owasp">OWASP</a>'); ?> 
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

                                <?php
                                /*
                                 * PluginHook: Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsContent.last
                                 * PluginHookDescription: Hook after last tabs content (inside `<div class="rd-tabs">`) in settings views.
                                 * PluginHookParam: None.
                                 * PluginHookReturn: None.
                                 * PluginHookSince: 1.1.7
                                 */
                                $Plugins->doHook(
                                    'Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsContent.last'
                                );
                                unset($Plugins);
                                ?> 
                            </div><!--.rd-tabs-->

                            <div class="rd-columns-flex-container submit-button-row">
                                <div class="column">
                                    <div class="column submit-button-wrapper"><!--this .column is just to align the space on left/right to match padding in tab's content-->
                                        <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </form>
