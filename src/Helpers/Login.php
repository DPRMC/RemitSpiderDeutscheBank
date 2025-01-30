<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;


use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use GuzzleHttp\Client;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 *
 */
class Login {

    const URL_LOGIN  = 'https://tss.sfs.db.com';
    const URL_LOGOUT = 'https://identity.db.com/auth/realms/global/protocol/openid-connect/logout';

    const LOGIN_LINK_BUTTON_Y = 140;
    const LOGIN_LINK_BUTTON_X = 1200;

    const LOGIN_BUTTON_X = 460;
    const LOGIN_BUTTON_Y = 390;


//    const URL_INTERFACE = RemitSpiderDeutscheBank::BASE_URL . '/TIR/public/deals';

    protected Page   $Page;
    protected Debug  $Debug;
    protected string $timezone;

    public ?string           $csrf = NULL;
    public CookiesCollection $cookies;

    public readonly array  $config;
    public readonly string $bearerToken;
    public readonly array  $token;
    public readonly array $claims;


    /**
     * @param Page $Page
     * @param Debug $Debug
     * @param string $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,

                                 string $timezone = RemitSpiderDeutscheBank::DEFAULT_TIMEZONE ) {
        $this->Page  = $Page;
        $this->Debug = $Debug;

        $this->timezone = $timezone;
    }


    /**
     * @param string $user
     * @param string $pass
     *
     * @return string
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\ElementNotFoundException
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\JavascriptException
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function login( string $user, string $pass ): string {
        $this->Debug->_debug( "Navigating to login screen at: " . self::URL_LOGIN );
        $this->Page->navigate( self::URL_LOGIN )->waitForNavigation( Page::NETWORK_IDLE );
        //sleep( 5 );

        $this->Debug->_screenshot( "1_home_page" );
        $this->Debug->_html( "1_home_page" );

        $this->Page->mouse()->find('button.jss98')->click();

        $this->Page->waitForReload();

        $this->Debug->_screenshot( "2_login_page" );
        $this->Debug->_html( "2_login_page" );

        $this->Page->evaluate( "document.querySelector('#username').value = '" . $user . "';" );
        $this->Page->evaluate( "document.querySelector('#password').value = '" . $pass . "';" );

        $this->Debug->_screenshot( "3_filled_in_login_page" );
        $this->Debug->_html( "3_filled_in_login_page" );

        $this->Page->mouse()->find('#kc-login')->click();
        $this->Page->waitForReload();

        sleep(5);

        $this->Debug->_screenshot( "4_am_i_logged_in" );
        $this->Debug->_html( "4_am_i_logged_in" );

        return $this->Page->getHtml();
    }


    /**
     * @return \GuzzleHttp\Cookie\CookieJar
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    private function _getCookieJar() {
        $cookies = $this->Page->getAllCookies();

        // User probably forgot to login first...
        if ( empty( $cookies ) ):
            throw new \Exception( "Cookies were empty for the browser. You probably forgot to login." );
        endif;

        $cookieArray = [];
        /**
         * @var \HeadlessChromium\Cookies\Cookie $cookie
         */
        foreach ( $cookies as $cookie ):
            $cookieArray[ $cookie->getName() ] = $cookie->getValue();
        endforeach;

        if(empty($cookieArray)):
            $domain = '';
        else:
            $domain = $cookie->getDomain();
        endif;

        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
            $cookieArray,
            $domain
        );

        return $jar;
    }


    protected function _requestConfig() {
        $client   = new Client();
        $jar      = $this->_getCookieJar();
        $options  = [
            'cookies'         => $jar,
            'allow_redirects' => TRUE,
        ];
        $response = $client->get( 'https://tss.sfs.db.com/api/v1/authapi/config', $options );

        $json         = $response->getBody();
        $this->config = json_decode( $json, TRUE );
    }


    /**
     * After the initial page load (before being logged in) I need to request an initial "anonymous" token.
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function _requestAnonymousToken() {
        $client   = new Client();
        $options  = [
            'allow_redirects' => TRUE,
        ];
        $response = $client->post( 'https://tss.sfs.db.com/api/v1/authapi/account/anonymoustoken', $options );

        $this->bearerToken = $response->getBody();
    }


    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function _requestClaims() {
        $client   = new Client();
        $jar      = $this->_getCookieJar();
        $options  = [
            'cookies'         => $jar,
            'allow_redirects' => TRUE,
            'headers'         => [
                'Authorization' => 'Bearer ' . $this->bearerToken
            ],
        ];
        $response = $client->get( 'https://tss.sfs.db.com/api/v1/authapi/account/claims', $options );

        $json        = $response->getBody();
        $this->claims = json_decode( $json, TRUE );
    }





    protected function _requestToken() {
        $client   = new Client();
        $jar      = $this->_getCookieJar();
        $options  = [
            'form_params'     => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->bearerToken,
            ],
            'query'           => [

            ],
            'cookies'         => $jar,
            'allow_redirects' => TRUE,
            'headers'         => [
                'Authorization' => 'Bearer ' . $this->bearerToken
            ],
        ];
        $response = $client->post( 'https://identity.db.com/auth/realms/global/protocol/openid-connect/token', $options );

        $json        = $response->getBody();
        $this->token = json_decode( $json, TRUE );
    }

    //


    /**
     * @return bool
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function logout(): bool {
        $this->Page->navigate( self::URL_LOGOUT )->waitForNavigation();
        $this->Debug->_screenshot( 'loggedout' );
        return TRUE;
    }


    /**
     * @param string $html
     *
     * @return string
     * @throws \Exception
     */
    protected function getCSRF( string $html ): string {
        $dom = new \DOMDocument();
        @$dom->loadHTML( $html );
        $inputs = $dom->getElementsByTagName( 'input' );
        foreach ( $inputs as $input ):
            $id = $input->getAttribute( 'id' );

            // This is the one we want!
            if ( 'OWASP_CSRFTOKEN' == $id ):
                return $input->getAttribute( 'value' );
            endif;
        endforeach;

        // Secondary Search if first was unfruitful. I have been getting some errors.
        // This regex search is looing for:
        // xhr.setRequestHeader('OWASP_CSRFTOKEN', 'AAAA-BBBB-CCCC-DDDD-EEEE-FFFF-GGGG-HHHH');
        //$pattern = "/'OWASP_CSRFTOKEN', '(.*)'\);/";
        $pattern = '/([A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4})/';
        $matches = [];
        $success = preg_match( $pattern, $html, $matches );
        if ( 1 === $success ):
            return $matches[ 1 ];
        endif;

        throw new \Exception( "Unable to find the CSRF value in the HTML." );
    }

}