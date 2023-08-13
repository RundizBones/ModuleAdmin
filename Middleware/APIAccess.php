<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Middleware;


/**
 * Check settings for limited access REST API or not, if yes then validate the API key.
 * 
 * On failed, return 403 status code with message.
 * 
 * @since 1.2.1
 */
class APIAccess
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * The class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Bind text domain.
     */
    protected function bindTextDomain()
    {
        $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
        $Languages->bindTextDomain(
            'rdbadmin', 
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
        unset($Languages);
    }// bindTextDomain


    /**
     * Get request header and POST body API key.
     * 
     * This will be trim empty space on the edge of the key.
     * 
     * @return string Return API key from requester.
     */
    protected function getRequestAPIKey(): string
    {
        $headers = apache_request_headers();
        $requestAPIKey = '';
        if ((!isset($requestAPIKey) || empty($requestAPIKey)) && isset($headers['Authorization'])) {
            $requestAPIKey = $headers['Authorization'];
        }
        if ((!isset($requestAPIKey) || empty($requestAPIKey)) && isset($headers['X-Authorization'])) {
            $requestAPIKey = $headers['X-Authorization'];
        }
        unset($headers);

        if ((!isset($requestAPIKey) || empty($requestAPIKey)) && isset($_POST['rdba-api-key'])) {
            $requestAPIKey = $_POST['rdba-api-key'];
        }

        return trim($requestAPIKey);
    }// getRequestAPIKey


    /**
     * Initialize to detect if root URL is not in exception or front pages URLs then redirect to admin.
     * 
     * @param string|null $response
     * @return string|null
     */
    public function init($response = '')
    {
        if (strtolower(PHP_SAPI) === 'cli') {
            // if running from CLI.
            // don't run this middleware here.
            return $response;
        }

        // check for settings for limited access from REST API.
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configVals = $ConfigDb->get(['rdbadmin_SiteAPILimitAccess', 'rdbadmin_SiteAPIKey'], ['0', '']);
        unset($ConfigDb);
        if (isset($configVals['rdbadmin_SiteAPILimitAccess']) && $configVals['rdbadmin_SiteAPILimitAccess'] !== '1') {
            // if config is not limited access from REST API.
            unset($configVals);
            // don't run this middleware here.
            return $response;
        }
        $apiKey = $configVals['rdbadmin_SiteAPIKey'];
        unset($configVals);

        // check for non HTML accept request.
        $Input = new \Rdb\Modules\RdbAdmin\Libraries\Input($this->Container);
        if (!$Input->isNonHtmlAccept()) {
            // if HTML request.
            // don't run this middleware.
            unset($apiKey, $Input);
            return $response;
        }
        unset($Input);

        // check for same site request.
        if ($this->isSamsSite()) {
            unset($apiKey);
            return $response;
        }

        // if come to this means it is REST API or from other site.
        $requestAPIKey = $this->getRequestAPIKey();

        if ($requestAPIKey !== $apiKey) {
            // if api key does not matched.
            $this->bindTextDomain();

            $output = [];
            $output['message'] = __('Access denied!') . PHP_EOL . __('The API key does not matched.');

            unset($apiKey, $requestAPIKey);
            $this->response($output);
        }// endif; api key does not matched.
        unset($apiKey, $requestAPIKey);

        return $response;
    }// init


    /**
     * Check if same site request.
     * 
     * @return bool Return `true` if same site request, `false` for otherwise.
     */
    protected function isSamsSite(): bool
    {
        $origin = ($_SERVER['HTTP_ORIGIN'] ?? null);// http[s]://domain.tld (currently work on Chrome only)
        $domainProtocol = (strtolower(($_SERVER['HTTPS'] ?? '')) === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
        $referrerDomain = parse_url(($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST);
        if (!is_string($referrerDomain)) {
            $referrerDomain = '';
        }
        $currentDomain = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        if (!is_string($currentDomain)) {
            $currentDomain = '';
        }

        if (
            (
                $currentDomain !== '' &&
                strtolower($referrerDomain) === strtolower($currentDomain) 
            ) ||
            (
                isset($origin) &&
                $origin === $domainProtocol
            )
        ) {
            // if from same site.
            unset($currentDomain, $domainProtocol, $origin, $referrerDomain);
            return true;
        }
        unset($currentDomain, $domainProtocol, $origin, $referrerDomain);

        return false;
    }// isSamsSite


    /**
     * Response header and body.
     * 
     * This method will be send headers and body then exit execution.
     * 
     * @param array $output The array for response body.
     */
    protected function response(array $output)
    {
        $Input = new \Rdb\System\Libraries\Input($this->Container);
        $httpAccept = $Input->determineAcceptContentType();
        unset($Input);

        http_response_code(403);

        switch ($httpAccept) {
            case 'application/json':
                header('Content-Type: application/json');
                echo json_encode($output);
                break;
            case 'application/xml':
            case 'text/xml':
                header('Content-Type: application/xml');
                $SimpleXml = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
                $Xml = new \Rdb\System\Libraries\Xml();
                $Xml->fromArray($output, $SimpleXml);
                echo $SimpleXml->asXML();
                unset($SimpleXml, $Xml);
                break;
            default:
                header('Content-Type: ' . $httpAccept);
                echo json_encode($output);
                break;
        }
        unset($httpAccept);
        exit();// must stopped.
    }// response


}
