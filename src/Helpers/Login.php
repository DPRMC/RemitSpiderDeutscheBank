<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;


use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;

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
    protected string $user;
    protected string $pass;
    protected string $timezone;

    public ?string           $csrf = NULL;
    public CookiesCollection $cookies;


    /**
     * @param Page $Page
     * @param Debug $Debug
     * @param string $user
     * @param string $pass
     * @param string $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $user,
                                 string $pass,
                                 string $timezone = RemitSpiderDeutscheBank::DEFAULT_TIMEZONE ) {
        $this->Page     = $Page;
        $this->Debug    = $Debug;
        $this->user     = $user;
        $this->pass     = $pass;
        $this->timezone = $timezone;
    }



    public function login(): string {
        $this->Debug->_debug( "Navigating to login screen at: " . self::URL_LOGIN );
        $this->Page->navigate( self::URL_LOGIN )->waitForNavigation(Page::NETWORK_IDLE);
        sleep(5);

        $this->Debug->_screenshot( 'start_page' );
        $this->Debug->_html( 'start_page' );

        $this->Debug->_debug( "Deleting the overlay" );
        $this->Page->evaluate("document.querySelector('.jss338').remove();");

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
        $this->Page->evaluate( "document.querySelector('#username').value = '" . $this->user . "';" );
        $this->Page->evaluate( "document.querySelector('#password').value = '" . $this->pass . "';" );

        $this->Debug->_screenshot( 'filled_in_user_pass' );
        $this->Debug->_html( 'filled_in_user_pass' );
        $this->Debug->_screenshot( 'the_login_button', new Clip( 0, 0, self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y ) );

        $this->Debug->_debug( "Clicking the login button." );
        $this->Page->mouse()
                   ->move( self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y )
                   ->click();
        sleep(5);
        $this->Page->waitForReload();

        $this->Debug->_screenshot( 'am_i_logged_in' );
        $this->Debug->_html( 'am_i_logged_in' );

        $currentUrl = $this->Page->getCurrentUrl();
        $this->Debug->_debug("Currently at: " . $currentUrl);

        $postLoginHTML = $this->Page->getHtml();

        var_dump($postLoginHTML);

        file_put_contents('deleteme.html',$postLoginHTML);

        // TODO Add code to determine if the login was successful.

        return $postLoginHTML;
    }


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