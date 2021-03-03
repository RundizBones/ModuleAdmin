<?php


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Tools;


/**
 * Tools > email tester page controller.
 * 
 * @since 1.16
 */
class EmailTesterController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    /**
     * Do send email message.
     * 
     * @return string
     */
    public function doSendEmailAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminTools', ['emailTester']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            // prepare data
            $data = [];
            $data['toemail'] = trim($this->Input->post('toemail', '', FILTER_SANITIZE_EMAIL));

            // form validate. ----------------------------------------------------------------------
            $formValidated = true;
            if (stripos($data['toemail'], '@') === false) {
                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Please enter target email.');
                $formValidated = false;
            }
            // end form validate. -----------------------------------------------------------------

            if ($formValidated === true) {
                $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
                try {
                    // get mailer object.
                    $Mail = $Email->getMailer();
                    $Mail->addAddress($data['toemail']);
                    $Mail->isHTML(true);

                    $Mail->Subject = __('Email tester');
                    $emailMessage = '<p>This is email tester, send from RundizBones admin module.</p>';
                    $Mail->msgHtml($emailMessage);
                    $Mail->AltBody = $Mail->html2text($emailMessage);
                    unset($emailMessage);

                    $sendResult = $Mail->send();
                    $output['emailSent'] = $sendResult;

                    if ($sendResult === true) {
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('The email was sent successfully.');
                    }
                } catch (\Exception $e) {
                    if ($this->Container->has('Logger')) {
                        /* @var $Logger \Rdb\System\Libraries\Logger */
                        $Logger = $this->Container->get('Logger');
                        $Logger->write('modules/rdbadmin/controllers/admin/tools/emailtestercontroller', 4, 'An email could not be sent. ' . $e->getMessage());
                        unset($Logger);
                    }

                    $output['emailSent'] = false;

                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('The email could not be sent.');
                    if (!empty($e->getMessage())) {
                        $output['formResultMessage'] .= '<br>' . PHP_EOL
                            . $e->getMessage();
                    }
                    if (!empty($Mail->ErrorInfo)) {
                        $output['formResultMessage'] .= '<br>' . PHP_EOL
                            . $Mail->ErrorInfo;
                    }
                }// endtry
                unset($Email, $Mail);
            }// endif;
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doSendEmailAction


    /**
     * Email tester tool page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminTools', ['emailTester']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // set urls and methods.
        $urlAppBased = $Url->getAppBasedPath(true);
        $output['urls'] = [];
        $output['urls']['emailTestUrl'] = $urlAppBased . '/admin/tools/email-tester';// display email tester tool page.
        $output['urls']['emailTestMethod'] = 'GET';
        $output['urls']['emailTestSubmitUrl'] = $urlAppBased . '/admin/tools/email-tester';// submit email tester via rest api.
        $output['urls']['emailTestSubmitMethod'] = 'POST';
        unset($urlAppBased);

        $output['pageTitle'] = __('Email tester');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content or ajax request.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            //$Assets->addMultipleAssets('css', [], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaToolsEmailTester'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaToolsEmailTester',
                'RdbaToolsEmailTesterObject',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'emailTestUrl' => $output['urls']['emailTestUrl'],
                    'emailTestMethod' => $output['urls']['emailTestMethod'],
                    'emailTestSubmitUrl' => $output['urls']['emailTestSubmitUrl'],
                    'emailTestSubmitMethod' => $output['urls']['emailTestSubmitMethod'],
                ]
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Tools/emailTester_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
