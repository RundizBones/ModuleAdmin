<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Email class.
 * 
 * For make it easier to load mailer class with send mail configuration.
 * 
 * @since 0.1
 */
class Email
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var string The base folder (full path) to prepend to relative paths to images.
     */
    protected $baseFolder;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class. Only required for some method.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        }
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }// __get


    /**
     * Initialize mailer class and configured it.
     * 
     * This method initialize the mailer class, setup configuration for it (mail, sendmail, smtp, smtp username & password, etc..).<br>
     * It also setup sender address.<br>
     * This method trigger exception for mailer. So, please use try..catch to catch the error message.
     * 
     * @throws \Exception Throw exception if contain errors.
     * @return \PHPMailer\PHPMailer\PHPMailer Return mailer class to ready for set receipt address, subject, body message and send.
     */
    public function getMailer(): \PHPMailer\PHPMailer\PHPMailer
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configNames = [
            'rdbadmin_MailProtocol',
            'rdbadmin_MailPath',
            'rdbadmin_MailSmtpHost',
            'rdbadmin_MailSmtpPort',
            'rdbadmin_MailSmtpSecure',
            'rdbadmin_MailSmtpUser',
            'rdbadmin_MailSmtpPass',
            'rdbadmin_MailSenderEmail',
        ];
        $configDefaults = [
            'mail',
            '/usr/sbin/sendmail',
            '',
            25,
            '',
            '',
            '',
            '',
        ];

        $configResult = $ConfigDb->get($configNames, $configDefaults);
        unset($ConfigDb, $configDefaults, $configNames);

        $Mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $Mail->CharSet = 'UTF-8';

        if (defined('APP_ENV') && APP_ENV === 'development') {
            $Mail->SMTPDebug = 3;
            $Mail->Debugoutput = function($str, $level) {
                // the log file name should be unique by date/time (with seconds).
                if (isset($this->Container) && $this->Container instanceof \Rdb\System\Container && $this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/libraries/email', 0, date('Y-m-d H:i:s') . ' ' . trim($str));
                    unset($Logger);
                }
            };
        }

        if ($configResult['rdbadmin_MailProtocol'] === 'sendmail') {
            //$Mail->isSendmail();// reference
            $ini_sendmail_path = ini_get('sendmail_path');
            $Mail->Mailer = 'sendmail';
            if (false === stripos($ini_sendmail_path, 'sendmail')) {
                $Mail->Sendmail = $configResult['rdbadmin_MailPath'];
            } else {
                $Mail->Sendmail = $ini_sendmail_path;
            }
            unset($ini_sendmail_path);
        } elseif ($configResult['rdbadmin_MailProtocol'] === 'smtp') {
            $Mail->isSMTP();
            $Mail->Host = $configResult['rdbadmin_MailSmtpHost'];
            $Mail->Port = $configResult['rdbadmin_MailSmtpPort'];

            if (!empty($configResult['rdbadmin_MailSmtpUser']) && !empty($configResult['rdbadmin_MailSmtpPass'])) {
                $Mail->SMTPAuth = true;
            }

            $Mail->Username = $configResult['rdbadmin_MailSmtpUser'];
            $Mail->Password = $configResult['rdbadmin_MailSmtpPass'];
            $Mail->SMTPSecure = $configResult['rdbadmin_MailSmtpSecure'];

            if (empty($configResult['rdbadmin_MailSmtpSecure']) || $configResult['rdbadmin_MailSmtpSecure'] === 'ssl') {
                $Mail->SMTPAutoTLS = false;
            }
        } else {
            $Mail->isMail();
        }

        try {
            $Mail->setFrom($configResult['rdbadmin_MailSenderEmail'], $configResult['rdbadmin_MailSenderEmail']);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        unset($configResult);

        return $Mail;
    }// getMailer


    /**
     * Get email message content and maybe replace the placeholder.
     * 
     * It will be looking for "Module/[moduleName]/languages/email-messages/[messageFile]-[language].html" file.<br>
     * This method can replace placeholder in the `$replaces` argument.
     * 
     * After called to this method, you can access the base folder (full path) that can be use for prepend to relative paths to images by call to `$Email->baseFolder`.
     * 
     * You can design email message by add any image related from the `baseFolder`.<br>
     * Example: `baseFolder` is "Modules/MyModule", the image tag `&lt;img src=&quot;assets/img/logo.png&quot;&gt;` will be call to "Modules/MyModule/assets/img/logo.pn".
     * 
     * Full example:
     * <pre>
     * $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
     * $Mail = $Email->getMailer();
     * $Mail->addAddress('someone@address.tld', 'someone name');
     * $Mail->Subject = 'Forgot my password.';
     * $Mail->isHTML(true);
     * $replaces['%tokenvalue%'] = $tokenValue;
     * $emailMessage = $Email->getMessage('RdbAdmin', 'ForgotLoginPass', $replaces);
     * $Mail->msgHtml($emailMessage, $Email->baseFolder);
     * $Mail->AltBody = $Mail->html2text($emailMessage);
     * $Mail->send();
     * </pre>
     * 
     * @param string $moduleName The module name (case sensitive) that this email message file is in.
     * @param string $messageFile The email message file without extension. Example: 'ForgotLoginPass' will be looking for 'ForgotLoginPass-[language].html'.
     * @param array $replaces The associative array for replace placeholder in message.
     * @return string Return email message content.
     * @throws \RuntimeException Throw exception if the email message file was not found.
     */
    public function getMessage(string $moduleName, string $messageFile, array $replaces = []): string
    {
        $emailMessagesPath = 'languages/email-messages';
        if ($moduleName === 'SystemCore') {
            $messageFolder = ROOT_PATH . '/System/Core/' . $emailMessagesPath;
            $this->baseFolder = realpath(ROOT_PATH . '/System/Core');
        } else {
            $messageFolder = MODULE_PATH . '/' . $moduleName . '/' . $emailMessagesPath;
            $this->baseFolder = realpath(MODULE_PATH . '/' . $moduleName);
        }
        unset($emailMessagesPath);

        if (is_file($messageFolder . '/' . $messageFile . '-' . $_SERVER['RUNDIZBONES_LANGUAGE'] . '.html')) {
            $fullPathMessageFile = $messageFolder . '/' . $messageFile . '-' . $_SERVER['RUNDIZBONES_LANGUAGE'] . '.html';
        } elseif (is_file($messageFolder . '/' . $messageFile . '.html')) {
            $fullPathMessageFile = $messageFolder . '/' . $messageFile . '.html';
        } else {
            throw new \RuntimeException(
                sprintf(
                    'The email message file was not found for %s.', 
                    $messageFolder . '/' . $messageFile . '-' . $_SERVER['RUNDIZBONES_LANGUAGE'] . '.html'
                )
            );
        }
        unset($messageFolder);

        $messageContent = file_get_contents($fullPathMessageFile);
        unset($fullPathMessageFile);

        if (!empty($replaces)) {
            foreach ($replaces as $find => $replace) {
                $messageContent = str_replace($find, $replace, $messageContent);
            }// endforeach;
            unset($find, $replace);
        }

        return $messageContent;
    }// getMessage


}
