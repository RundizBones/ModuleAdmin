/**
 * Installer SQL.
 * 
 * Please follow these instruction strictly.
 * The table name in this file must wrap with %$...% and have no prefix. Example: `%$users%` will be converted to `prefix_users`.
 * No ENGINE=xxx in the SQL.
 * No COLLATE xxx in each table or column (except it is special such as `utf8_bin` for work with case sensitive).
 * Use only CHARSET=utf8 in the CREATE TABLE, nothing else, no utf8mb4 or anything. Just utf8.
 *
 * DO NOT just paste the SQL data that exported from MySQL. Please modify by read the instruction above first.
 */


-- Begins the SQL string below this line. ------------------------------------------------------------------


SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


CREATE TABLE IF NOT EXISTS `%$config%` (
  `config_name` varchar(191) DEFAULT NULL COMMENT 'config name',
  `config_value` longtext DEFAULT NULL COMMENT 'config value',
  `config_description` text DEFAULT NULL COMMENT 'description for this config',
  UNIQUE KEY `config_name` (`config_name`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='contain site configurations.';

INSERT INTO `%$config%` (`config_name`, `config_value`, `config_description`) VALUES
('rdbadmin_SiteName', 'RundizBones', 'Website name.'),
('rdbadmin_SiteTimezone', 'Asia/Bangkok', 'Website timezone.'),
('rdbadmin_UserRegister', '0', '0 to not allowed register (add by admin only), 1 to allowed.'),
('rdbadmin_UserRegisterNotifyAdmin', '0', 'Send email to notify admin when new member registered? 0=no, 1=yes.'),
('rdbadmin_UserRegisterNotifyAdminEmails', '', 'The emails of administrator to notify when new member registered. Use comma (,) to add more than one.'),
('rdbadmin_UserRegisterVerification', '0', 'User registration verification method.\n0=never verify (always activated)\n1=by user''s email\n2=by admin.'),
('rdbadmin_UserRegisterWaitVerification', '2', 'How many days that user needs to take action to verify their email on register or added by admin?'),
('rdbadmin_UserRegisterDisallowedName', 'admin, root', 'Disallowed user_login, user_email, user_display_name. Use comma (,) to add multiple values, use double quote to escape and enclosure ("name contain, comma").'),
('rdbadmin_UserRegisterDefaultRoles', '3', 'Default roles for newly register user. Use comma (,) to add multiple values.'),
('rdbadmin_UserLoginCaptcha', '1', 'Use captcha for login?\n0=do not use\n1=use until login success and next time do not use it\n2=always use.'),
('rdbadmin_UserLoginBruteforcePreventByIp', '1', 'Use brute-force prevention by IP address?\n0=do not use\n1=use it.'),
('rdbadmin_UserLoginBruteforcePreventByDc', '1', 'Use brute-force prevention by Device cookie?\n0=do not use\n1=use it.'),
('rdbadmin_UserLoginMaxFail', '10', 'Maximum times that client can login failed continuously. (For brute-force prevent by IP).\n\nMaximum times that client can login failed continuously during time period. (For brute-force prevent by Device Cookie).'),
('rdbadmin_UserLoginMaxFailWait', '60', 'How many minutes that client have to wait until they are able to try login again? (For brute-force prevent by IP).\n\nHow many minutes in time period that client can try login until maximum attempts? (For brute-force prevent by Device Cookie).'),
('rdbadmin_UserLoginNotRememberLength', '0', 'How many days to keep cookie when user login without remember ticked? 0 = until browser close'),
('rdbadmin_UserLoginRememberLength', '20', 'How many days that user can remember their logins?'),
('rdbadmin_UserLoginLogsKeep', '90', 'How many days that user logins data to keep in database?'),
('rdbadmin_UserConfirmEmailChange', '1', 'When user change their email, do they need to confirm? 1=yes, 0=no.'),
('rdbadmin_UserConfirmWait', '10', 'How many minutes that the user needs to take action such as confirm reset password, change email?'),
('rdbadmin_UserDeleteSelfGrant', '0', 'Allow user to delete themself?\n0=do not allowed\n1=allowed.'),
('rdbadmin_UserDeleteSelfKeep', '30', 'On delete user wether delete themself or by admin, How many days before it gets actual delete?'),
('rdbadmin_MailProtocol', 'mail', 'The mail sending protocol.\nmail, sendmail, smtp'),
('rdbadmin_MailPath', '/usr/sbin/sendmail', 'The sendmail path.'),
('rdbadmin_MailSmtpHost', '', 'SMTP host'),
('rdbadmin_MailSmtpPort', '', 'SMTP port'),
('rdbadmin_MailSmtpSecure', '', 'SMTP encryption\n'''' (empty), ssl, tls'),
('rdbadmin_MailSmtpUser', '', 'SMTP username'),
('rdbadmin_MailSmtpPass', '', 'SMTP password'),
('rdbadmin_MailSenderEmail', 'noreply@localhost.localhost', 'The sender email (send from this email).'),
('rdbadmin_AdminItemsPerPage', '20', 'Number of items will be display per page for admin pages.');


-- users and related tables. ---------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `%$users%` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_login` varchar(191) DEFAULT NULL COMMENT 'username (must be unique for use as login)',
  `user_email` varchar(191) DEFAULT NULL COMMENT 'email (must be unique for use as login)',
  `user_password` tinytext DEFAULT NULL COMMENT 'password',
  `user_display_name` varchar(191) DEFAULT NULL COMMENT 'display name for cloak the username',
  `user_create` datetime DEFAULT NULL COMMENT 'user created date/time',
  `user_create_gmt` datetime DEFAULT NULL COMMENT 'user created date/time in gmt 0',
  `user_lastupdate` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'user last update',
  `user_lastupdate_gmt` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'user last update in gmt 0',
  `user_lastlogin` datetime DEFAULT NULL COMMENT 'user last login',
  `user_lastlogin_gmt` datetime DEFAULT NULL COMMENT 'user last login in gmt 0',
  `user_status` int(1) NOT NULL DEFAULT 0 COMMENT 'user status. 0=disabled, 1=enabled',
  `user_statustext` varchar(191) DEFAULT NULL COMMENT 'user status text for describe what happens. (untranslated.)',
  `user_deleted` int(1) NOT NULL DEFAULT 0 COMMENT 'user soft deleted. 0=not deleted, 1=deleted',
  `user_deleted_since` datetime DEFAULT NULL COMMENT 'user deleted since date/time',
  `user_deleted_since_gmt` datetime DEFAULT NULL COMMENT 'user deleted since date/time in gmt 0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_login` (`user_login`),
  UNIQUE KEY `user_email` (`user_email`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='contain users account' AUTO_INCREMENT=2 ;

INSERT INTO `%$users%` (`user_id`, `user_login`, `user_email`, `user_password`, `user_display_name`, `user_create`, `user_create_gmt`, `user_lastupdate`, `user_lastupdate_gmt`, `user_lastlogin`, `user_lastlogin_gmt`, `user_status`, `user_statustext`) VALUES
(0, 'guest', 'guest@localhost.localhost', NULL, 'Guest', '2019-06-01 07:00:00', '2019-06-01 00:00:00', '2019-06-01 07:00:00', '2019-06-01 00:00:00', NULL, NULL, 0, 'This account is for guest actions.'),
(1, 'admin', 'admin@localhost.localhost', '$2y$11$9yegQbS1A9MntZsOm.dunuD1H5fMokXUihX3zvv5fDwEdKrEomAlG', 'Administrator', '2019-06-01 07:00:00', '2019-06-01 00:00:00', '2019-06-01 07:00:00', '2019-06-01 00:00:00', NULL, NULL, 1, NULL);


CREATE TABLE IF NOT EXISTS `%$users_roles%` (
  `usersroles_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'refer to users.user_id',
  `userrole_id` bigint(20) NOT NULL COMMENT 'refer to user_roles.userrole_id',
  PRIMARY KEY (`usersroles_id`),
  KEY `user_id` (`user_id`),
  KEY `userrole_id` (`userrole_id`)
) DEFAULT CHARSET=utf8 COMMENT='contain user''s roles relation' AUTO_INCREMENT=3 ;

INSERT INTO `%$users_roles%` (`usersroles_id`, `user_id`, `userrole_id`) VALUES
(1, 0, 4),
(2, 1, 1);


CREATE TABLE IF NOT EXISTS `%$user_fields%` (
  `userfield_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'refer to users.user_id',
  `field_name` varchar(191) DEFAULT NULL COMMENT 'field name',
  `field_value` longtext DEFAULT NULL COMMENT 'field value',
  `field_description` varchar(100) DEFAULT NULL COMMENT 'for describe what is this field for',
  PRIMARY KEY (`userfield_id`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8 COMMENT='contain user fields or additional data from users table' AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `%$user_roles%` (
  `userrole_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userrole_name` varchar(191) DEFAULT NULL COMMENT 'role name',
  `userrole_description` text DEFAULT NULL COMMENT 'description of this role',
  `userrole_priority` int(5) NOT NULL COMMENT 'role priority, lower number is higher priority',
  `userrole_create` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'role created date/time',
  `userrole_create_gmt` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'role created date/time in gmt',
  `userrole_lastupdate` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'role last update',
  `userrole_lastupdate_gmt` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'role last update in gmt',
  PRIMARY KEY (`userrole_id`),
  KEY `userrole_priority` (`userrole_priority`)
) DEFAULT CHARSET=utf8 COMMENT='contain user roles name and description' AUTO_INCREMENT=5 ;

INSERT INTO `%$user_roles%` (`userrole_id`, `userrole_name`, `userrole_description`, `userrole_priority`, `userrole_create`, `userrole_create_gmt`, `userrole_lastupdate`, `userrole_lastupdate_gmt`) VALUES
(1, 'Super administrator', 'Have full controls.', 1, '2019-06-01 07:00:00', '2019-06-01 00:00:00', '2019-06-01 07:00:00', '2019-06-01 00:00:00'),
(2, 'Administrator', 'For users who may have a lot of controls but less than super administrator.', 2, '2019-06-01 07:00:00', '2019-06-01 00:00:00', '2019-06-01 07:00:00', '2019-06-01 00:00:00'),
(3, 'Member', 'For normal users.', 9999, '2019-06-01 07:00:00', '2019-06-01 00:00:00', '2019-06-01 07:00:00', '2019-06-01 00:00:00'),
(4, 'Guest', 'For guest account or non-registered users.', 10000, '2019-06-01 07:00:00', '2019-06-01 00:00:00', '2019-06-01 07:00:00', '2019-06-01 00:00:00');


CREATE TABLE IF NOT EXISTS `%$user_logins%` (
  `userlogin_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'refer to users.user_id',
  `userlogin_session_key` varchar(1000) DEFAULT NULL COMMENT 'user login session key (must be unique for successfull login, if present)',
  `userlogin_ua` varchar(191) DEFAULT NULL COMMENT 'login user agent',
  `userlogin_ip` varchar(100) DEFAULT NULL COMMENT 'user login ip address',
  `userlogin_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'user login date/time',
  `userlogin_date_gmt` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'user login date/time in gmt 0',
  `userlogin_expire_date` datetime DEFAULT NULL COMMENT 'user login expiration date/time (cookie, session timeout)',
  `userlogin_expire_date_gmt` datetime DEFAULT NULL COMMENT 'user login expiration date/time in gmt 0',
  `userlogin_dc_sign` varchar(1000) DEFAULT NULL COMMENT 'device cookie signature (if present)',
  `userlogin_dc_lockout` int(1) NOT NULL DEFAULT 0 COMMENT '0=dont lock, 1=lock untrusted clients, 2=lock selected device cookie',
  `userlogin_dc_lockout_until` datetime DEFAULT NULL COMMENT 'lockout selected user until date/time',
  `userlogin_dc_lockout_until_gmt` datetime DEFAULT NULL COMMENT 'lockout selected user until date/time in gmt 0',
  `userlogin_result` int(1) NOT NULL DEFAULT 0 COMMENT 'user login result. 0=failed, 1=success',
  `userlogin_result_text` varchar(191) DEFAULT NULL COMMENT 'user login result text for describe what happen. (untranslated.)',
  `userlogin_result_text_data` varchar(191) DEFAULT NULL COMMENT 'contain data for replace placeholder using PHP sprintf($row->userlogin_result_text, ...$row->userlogin_result_text_data). store data in serialize of array, example: serialize([''hello'', ''world'']) for string ''say %s to the %s'' will be ''say hello to the world. (untranslated.)''',
  PRIMARY KEY (`userlogin_id`),
  KEY `user_id` (`user_id`),
  KEY `userlogin_session_key` (`userlogin_session_key`),
  KEY `userlogin_dc_sign` (`userlogin_dc_sign`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='contain user login sessions' AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `%$user_permissions%` (
  `permission_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userrole_id` bigint(20) DEFAULT NULL COMMENT 'refer to user_roles.userrole_id. this is for store the role''s permission.',
  `user_id` bigint(20) DEFAULT NULL COMMENT 'refer to users.user_id. this is for store the user''s permission.',
  `module_system_name` varchar(191) NOT NULL COMMENT 'module folder name',
  `permission_page` varchar(191) NOT NULL COMMENT 'the permission page or permission main key. example: productStock',
  `permission_action` varchar(191) DEFAULT NULL COMMENT 'the permission action or permission sub key. example: addStock, countStock, subtract',
  PRIMARY KEY (`permission_id`),
  KEY `userrole_id` (`userrole_id`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8 COMMENT='contain role''s permission or user''s permission for admin page and action.' AUTO_INCREMENT=1 ;