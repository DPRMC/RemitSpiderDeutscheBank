<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;


use DPRMC\RemitSpiderDeutscheBank\Exceptions\ExceptionLoginIncorrect;
use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use GuzzleHttp\Client;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class Login {

    const URL_LOGIN  = 'https://tss.sfs.db.com/search';
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
    public readonly string $refreshToken;
    public readonly array  $token;


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
     * @return string
     * @throws ExceptionLoginIncorrect
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
    public function login( string $user, string $pass ): string {
        $this->Debug->_debug( "Navigating to login screen at: " . self::URL_LOGIN );
        $this->Page->navigate( self::URL_LOGIN )->waitForNavigation( Page::NETWORK_IDLE );
        sleep( 5 );

        $this->_requestAnonymousToken();
        var_dump( $this->refreshToken );

        $this->Debug->_screenshot( 'start_page' );
        $this->Debug->_html( 'start_page' );

        $this->Debug->_debug( "Deleting the overlay" );
        $this->Page->evaluate( "document.querySelector('.jss338').remove();" );

        $this->Debug->_screenshot( 'page_without_overlay' );
        $this->Debug->_html( 'page_without_overlay' );

        $this->Debug->_debug( "Clicking Login button." );

        $this->Debug->_screenshot( 'where_i_clicked_to_login', new Clip( 0, 0, self::LOGIN_LINK_BUTTON_X, self::LOGIN_LINK_BUTTON_Y ) );

        $this->Page->mouse()
                   ->move( self::LOGIN_LINK_BUTTON_X, self::LOGIN_LINK_BUTTON_Y )
                   ->click();

        $this->Page->waitForReload();

        $this->Debug->_screenshot( 'login_page' );
        $this->Debug->_html( 'login_page' );

        $this->Debug->_debug( "Should be on login button." );

        $this->Debug->_debug( "Filling out user and pass." );
        $this->Page->evaluate( "document.querySelector('#username').value = '" . $user . "';" );
        $this->Page->evaluate( "document.querySelector('#password').value = '" . $pass . "';" );

        $this->Debug->_screenshot( 'filled_in_user_pass' );
        $this->Debug->_html( 'filled_in_user_pass' );
        $this->Debug->_screenshot( 'the_login_button', new Clip( 0, 0, self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y ) );

        $this->Debug->_debug( "Clicking the login button." );
        $this->Page->mouse()
                   ->move( self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y )
                   ->click();
        sleep( 5 );
        $this->Page->waitForReload();

        $this->Debug->_screenshot( 'am_i_logged_in' );
        $this->Debug->_html( 'am_i_logged_in' );

        $currentUrl = $this->Page->getCurrentUrl();
        $this->Debug->_debug( "Currently at: " . $currentUrl );

        $postLoginHTML = $this->Page->getHtml();

        if ( str_contains( $postLoginHTML, 'Please check your entries' ) ):
            throw new  ExceptionLoginIncorrect( "Login appears to be incorrect. Check for a changed password." );
        endif;

        $this->_requestConfig();

        print_r( $this->config );

        $this->_requestToken();

        print_r( $this->token );

        return $postLoginHTML;
    }


    private function _getCookieJar() {
        $cookies = $this->Page->getCookies();

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

        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
            $cookieArray,
            $cookie->getDomain()
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


    protected function _requestAnonymousToken() {
        $client   = new Client();
        $options  = [
            'allow_redirects' => TRUE,
        ];
        $response = $client->post( 'https://tss.sfs.db.com/api/v1/authapi/account/anonymoustoken', $options );

        $this->refreshToken = $response->getBody();
    }

    protected function _requestToken() {
        $client   = new Client();
        $jar      = $this->_getCookieJar();
        $options  = [
            'form_params'     => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ],
            'query'           => [

            ],
            'cookies'         => $jar,
            'allow_redirects' => TRUE,
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