<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;


use DPRMC\RemitSpiderDeutscheBank\Objects\Deal;
use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use GuzzleHttp\Client;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 *
 */
class DealHelper {


    protected Page            $Page;
    protected NetworkListener $NetworkListener;

    protected Debug  $Debug;
    protected string $timezone;


    public CookiesCollection $cookies;


    public array $mostRecentFactors    = [];
    public array $latestReportsPerType = [];


    public array $exceptions = [];

    /**
     * @param \HeadlessChromium\Page                                 $Page
     * @param \DPRMC\RemitSpiderDeutscheBank\Helpers\NetworkListener $NetworkListener
     * @param \DPRMC\RemitSpiderDeutscheBank\Helpers\Debug           $Debug
     * @param string                                                 $timezone
     */
    public function __construct( Page            &$Page,
                                 NetworkListener $NetworkListener,
                                 Debug           &$Debug,
                                 string          $timezone = RemitSpiderDeutscheBank::DEFAULT_TIMEZONE ) {
        $this->Page            = $Page;
        $this->Debug           = $Debug;
        $this->NetworkListener = $NetworkListener;

        $this->timezone = $timezone;
    }


    /**
     * @return array
     */
    public function getNetworkRequestUrls(): array {
        $urls = [];

        $requests = $this->NetworkListener->requests;
        foreach ( $requests as $requestCounter => $request ):
            $url                     = $request[ 'params' ][ 'response' ][ 'url' ];
            $urls[ $requestCounter ] = $url;
        endforeach;

        return $urls;
    }


    /**
     * There are a bunch of data requests, for images and such.
     * Right now I just need the http(s) requests
     *
     * @return array
     */
    public function getHttpNetworkRequestUrls(): array {
        $urls     = $this->getNetworkRequestUrls();
        $httpUrls = [];
        foreach ( $urls as $url ):
            if ( str_starts_with( $url, 'http' ) ):
                $httpUrls[] = $url;
            endif;
        endforeach;
        return $httpUrls;
    }


    /**
     * @param int $dealId
     *
     * @return array
     */
    public function getHttpNetworkRequestUrlsByDealId( int $dealId ): array {
        $dealIdUrls = [];
        $httpUrls   = $this->getHttpNetworkRequestUrls();
        foreach ( $httpUrls as $url ):
            if ( str_contains( $url, '/' . $dealId ) ):
                $dealIdUrls[] = $url;
            endif;
        endforeach;
        return $dealIdUrls;
    }


    /**
     * @param int $dealId
     *
     * @return \DPRMC\RemitSpiderDeutscheBank\Objects\Deal
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
    public function getDealOverview( int $dealId ): Deal {

        $this->Debug->_debug( 'Navigating to the search page.' );
        $this->Page->navigate( 'https://tss.sfs.db.com/search' )->waitForNavigation( Page::NETWORK_IDLE );

        $this->Debug->_screenshot( '5_search_page' );
        $this->Debug->_html( '5_search_page' );

        if ( empty( $this->NetworkListener->accessToken ) ):
            throw new \Exception( "Can't get the Deal Overview, because the access token is empty." );
        endif;


        $extraHeaders = [
            'Authorization' => 'Bearer ' . $this->NetworkListener->accessToken,
        ];
        $this->Page->setExtraHTTPHeaders( $extraHeaders );


        // https://tss.sfs.db.com/deal/2475/overview
        $url = 'https://tss.sfs.db.com/deal/' . $dealId . '/overview';

        $this->Debug->_debug( 'Navigating to the deal overview page for: ' . $dealId );
        $this->Page->navigate( $url )->waitForNavigation( Page::NETWORK_IDLE );
        $this->Debug->_screenshot( '6_deal_' . $dealId . '_page' );
        $this->Debug->_html( '6_deal_' . $dealId . '_page' );

        $this->Debug->_debug( 'Parsing out the most recent factors and latest reports per type.' );

        $deal = new Deal();

        try {
            $deal->mostRecentFactors = $this->_getMostRecentFactors( $dealId );
        } catch ( \Exception $e ) {
            $deal->mostRecentFactors = [];
            $this->Debug->_debug( $e->getMessage() );
            $Deal->_addException( $e );
        }

        try {
            $deal->latestReportsPerType = $this->_getLatestReportsPerType( $dealId );
        } catch ( \Exception $e ) {
            $deal->latestReportsPerType = [];
            $this->Debug->_debug( $e->getMessage() );
            $Deal->_addException( $e );
        }


        $this->Debug->_debug( 'Returning the completed deal object.' );
        return $deal;
    }


    /**
     * @param string $url
     *
     * @return array
     * @throws \Exception
     */
    protected function _getRequestByUrl( string $url ): array {
        /**
         * @var array $request
         */
        foreach ( $this->NetworkListener->requests as $requestCounter => $request ):
            $requestUrl = $request[ 'params' ][ 'response' ][ 'url' ];
            if ( $url === $requestUrl ):
                return $request;
            endif;
        endforeach;

        throw new \Exception( "Unable to find the request with this URL: " . $url );
    }


    /**
     * @param int $dealId
     *
     * @return array
     * @throws \Exception
     */
    protected function _getMostRecentFactors( int $dealId ): array {
        $url = 'https://tss.sfs.db.com/api/v1/dealapi/factors/' . $dealId . '/factorsmostrecent';
        $this->Debug->_debug( "Searching through all (" . count( $this->NetworkListener->requests ) . ") network requests for the URL: " . $url );
        $request = $this->_getRequestByUrl( $url );

        dump($request);

        $body    = @$request[ 'data' ][ 'result' ][ 'body' ];
        $factors = @json_decode( $body, TRUE );
        return $factors ?? [];
    }


    /**
     * @param int $dealId
     *
     * @return array
     * @throws \Exception
     */
    protected function _getLatestReportsPerType( int $dealId ): array {
        $url                  = 'https://tss.sfs.db.com/api/v1/dealapi/deal/' . $dealId . '/latestreportspertype';
        $request              = $this->_getRequestByUrl( $url );

        dump($request);

        $body                 = @$request[ 'data' ][ 'result' ][ 'body' ];
        $latestReportsPerType = @json_decode( $body, TRUE );
        return $latestReportsPerType ?? [];
    }


}