<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Sessions;


/**
 * User login sessions - ping controller.
 * 
 * @since 0.1
 */
class PingController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use Traits\SessionsTrait;


    /**
     * Ping check logged in.
     * 
     * @param int $user_id The user ID.
     * @return string
     */
    public function indexAction($user_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $output = [];

        // get the session key via request headers.
        $headers = apache_request_headers();
        $output['loggedIn'] = false;
        if (is_array($headers)) {
            $headers = array_change_key_case($headers);
            if (array_key_exists('sessionkey', $headers)) {
                $output['loggedIn'] = $this->isUserLoggedIn((int) $user_id, $headers['sessionkey']);
            }
        }
        unset($headers);

        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();
        
        $output['loggedInAsString'] = ($output['loggedIn'] === true ? 'true' : 'false');
        if ($output['loggedIn'] !== true) {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Your session has been expired, please go to %1$slogin page%2$s.', '<a class="rdba-login-page-link" href="' . $Url->getAppBasedPath(true) . '/admin/login">', '</a>');
            http_response_code(401);
            $output['loginUrlBaseDomain'] = $Url->getDomainProtocol();
            $output['loginUrl'] = $Url->getAppBasedPath(true) . '/admin/login';
        } else {
            $output['totalLoggedInSessions'] = $this->totalLoggedInSessions;
        }

        unset($Url);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// indexAction


}
