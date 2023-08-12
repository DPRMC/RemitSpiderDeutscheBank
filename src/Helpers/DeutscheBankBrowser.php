<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;

use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;

/**
 *
 */
class DeutscheBankBrowser {

    protected CookiesCollection   $cookies;
    protected string              $chromePath;
    protected ProcessAwareBrowser $browser;

    public Page $page;
    const NETWORK_IDLE_MS_TO_WAIT    = 4000; // This was too short. So I removed it from usage.
    const BROWSER_WINDOW_SIZE_WIDTH  = 1250;
    const BROWSER_WINDOW_SIZE_HEIGHT = 768;
    const BROWSER_ENABLE_IMAGES      = TRUE;
    const USER_AGENT_STRING          = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.83 Safari/537.36';

    const DEFAULT_TIMEZONE = 'America/New_York';
    /**
     * Add some delay between each instruction sent to Chrome. More human-like?
     */
    const BROWSER_CONNECTION_DELAY = 1;

    public function __construct( string $chromePath ) {
        $this->cookies    = new CookiesCollection();
        $this->chromePath = $chromePath;

        $browserFactory = new BrowserFactory( $this->chromePath );
        // starts headless chrome
        $this->browser = $browserFactory->createBrowser( [
                                                             'headless'        => TRUE,         // disable headless mode
                                                             'connectionDelay' => self::BROWSER_CONNECTION_DELAY,
                                                             //'debugLogger'     => 'php://stdout', // will enable verbose mode
                                                             'windowSize'      => [ self::BROWSER_WINDOW_SIZE_WIDTH,
                                                                                    self::BROWSER_WINDOW_SIZE_HEIGHT ],
                                                             'enableImages'    => self::BROWSER_ENABLE_IMAGES,
                                                             'customFlags'     => [ '--disable-web-security' ],
                                                         ] );

        $this->createPage();
    }

    /**
     * @return void
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    private function createPage() {
        $this->page = $this->browser->createPage();
        $this->page->setUserAgent( self::USER_AGENT_STRING );
        $this->page->setCookies( $this->cookies );
    }


    public function reloadCookies() {
        $this->page->setCookies( $this->cookies );
    }


    public static function isForbidden( string $html ): bool {
        $pattern = '/403 - Forbidden: Access is denied/';
        $matches = [];
        $success = preg_match( $pattern, $html, $matches );
        if ( 1 !== $success ):
            return FALSE;
        endif;
        return TRUE;
    }
}