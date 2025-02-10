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

    // https://identity.db.com/auth/realms/global/protocol/openid-connect/auth?client_id=account&redirect_uri=https%3A%2F%2Fidentity.db.com%2Fauth%2Frealms%2Fglobal%2Faccount%2Flogin-redirect&state=0%2F7a54a783-53bd-4ee2-8aa7-1f37f2490c9d&response_type=code&scope=openid


    const URL_LOGIN  = 'https://tss.sfs.db.com';
    const URL_LOGOUT = 'https://identity.db.com/auth/realms/global/protocol/openid-connect/logout';

    const LOGIN_LINK_BUTTON_Y = 140;
    const LOGIN_LINK_BUTTON_X = 1200;

    const LOGIN_BUTTON_X = 460;
    const LOGIN_BUTTON_Y = 390;


//    const URL_INTERFACE = RemitSpiderDeutscheBank::BASE_URL . '/TIR/public/deals';

    protected Page         $Page;
    public NetworkListener $NetworkListener;
    protected Debug        $Debug;
    protected string       $timezone;

    public ?string           $csrf = NULL;
    public CookiesCollection $cookies;

    public readonly array  $config;
    public readonly string $bearerToken;
    public readonly array  $token;
    public readonly array  $claims;


    /**
     * @param \HeadlessChromium\Page                                 $Page
     * @param \DPRMC\RemitSpiderDeutscheBank\Helpers\NetworkListener $NetworkListener
     * @param \DPRMC\RemitSpiderDeutscheBank\Helpers\Debug           $Debug
     * @param string                                                 $timezone
     */
    public function __construct( Page            &$Page,
                                 NetworkListener &$NetworkListener,
                                 Debug           &$Debug,

                                 string          $timezone = RemitSpiderDeutscheBank::DEFAULT_TIMEZONE ) {
        $this->Page            = $Page;
        $this->NetworkListener = $NetworkListener;
        $this->Debug           = $Debug;

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

        //$this->Page->getSession()->on( 'method:Network.responseReceived', function ( array $params ): void {
        //    $request_id = @$params[ "requestId" ];
        //    $data       = @$this->Page->getSession()->sendMessageSync( new \HeadlessChromium\Communication\Message( 'Network.getResponseBody', [ 'requestId' => $request_id ] ) )->getData();
        //
        //    // START DEBUG
        //    $filepath     = '/Users/michaeldrennen/PhpstormProjects/DPRMC/RemitSpiderDeutscheBank/tests/temp_files/login_params_' . md5( json_encode( $data ) ) . '.txt';
        //    $prettyParams = print_r( $params, TRUE );
        //    $written      = file_put_contents( $filepath, $prettyParams );
        //
        //    $filepath   = '/Users/michaeldrennen/PhpstormProjects/DPRMC/RemitSpiderDeutscheBank/tests/temp_files/login_data_' . md5( json_encode( $data ) ) . '.txt';
        //    $prettyData = print_r( $data, TRUE );
        //    $written    = file_put_contents( $filepath, $prettyData );
        //    // END DEBUG
        //
        //
        //    if ( $this->_isRequestThatHasTheAccessToken( $params, $data ) ):
        //        $anonymousAccessToken = @$data[ "result" ][ "body" ];
        //
        //        $this->accessToken = $anonymousAccessToken;
        //    endif;
        //} );


        $this->Page->navigate( self::URL_LOGIN )->waitForNavigation( Page::NETWORK_IDLE );
        //sleep( 5 );

        $this->Debug->_screenshot( "1_home_page" );
        $this->Debug->_html( "1_home_page" );

        $this->Page->mouse()->find( 'button.jss98' )->click();

        $this->Page->waitForReload();

        $this->Debug->_screenshot( "2_login_page" );
        $this->Debug->_html( "2_login_page" );

        $this->Page->evaluate( "document.querySelector('#username').value = '" . $user . "';" );
        $this->Page->evaluate( "document.querySelector('#password').value = '" . $pass . "';" );

        $this->Debug->_screenshot( "3_filled_in_login_page" );
        $this->Debug->_html( "3_filled_in_login_page" );

        $this->Page->mouse()->find( '#kc-login' )->click();
        $this->Page->waitForReload();

        sleep( 5 );

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

        if ( empty( $cookieArray ) ):
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


    // https://identity.db.com/auth/realms/global/protocol/openid-connect/token
    protected function _isRequestThatHasTheAccessToken( array $params = [], string $data = '' ): bool {
        $jsonData = json_decode( $data, TRUE );
        if ( is_null( $jsonData ) ):
            return FALSE;
        endif;

        if ( isset( $jsonData[ 'access_token' ] ) ):
            return TRUE;
        endif;

        return FALSE;
    }

}