<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;


use DPRMC\RemitSpiderDeutscheBank\Objects\Deal;
use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use GuzzleHttp\Client;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 * 0 => "https://tss.sfs.db.com/deal/2475/overview"
 *
 * API REQUESTS
 * 1 => "https://tss.sfs.db.com/api/v1/dealapi/deal/2475"
 * 2 => "https://tss.sfs.db.com/api/v1/dealapi/deal/2475/userentitlements"
 * 3 => "https://tss.sfs.db.com/api/v1/dealapi/deal/2475/reportTypes"
 * 4 => "https://tss.sfs.db.com/api/v1/dealapi/factors/2475/BondSummary"
 * 5 => "https://tss.sfs.db.com/api/v1/dealapi/factors/2475/factorsmostrecent"
 * 6 => "https://tss.sfs.db.com/api/v1/dealapi/deal/2475/latestreportspertype"
 * 7 => "https://tss.sfs.db.com/api/v1/dealapi/factors/2475/factordates"
 * 8 => "https://tss.sfs.db.com/api/v1/dealapi/deal/2475/reports"
 * 9 => "https://tss.sfs.db.com/api/v1/dealapi/factors/2475/factorsperrange/?fromDate=2025-01-01&toDate=2025-01-31"
 */
class DealHelper {

    use RequestTrait;

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

        //try {
        //    $deal->mostRecentFactors = $this->_getMostRecentFactors( $dealId );
        //} catch ( \Exception $e ) {
        //    $deal->mostRecentFactors = [];
        //    $this->Debug->_debug( $e->getMessage() );
        //    $Deal->_addException( $e );
        //}
        //
        //try {
        //    $deal->latestReportsPerType = $this->_getLatestReportsPerType( $dealId );
        //} catch ( \Exception $e ) {
        //    $deal->latestReportsPerType = [];
        //    $this->Debug->_debug( $e->getMessage() );
        //    $Deal->_addException( $e );
        //}


        $this->Debug->_debug( 'Returning the completed deal object.' );
        return $deal;
    }




    /**
     * @param int    $dealId
     * @param string $bearerToken
     *
     * @return array
     */
    public function apiGetMostRecentFactors( int $dealId, string $bearerToken ): array {
        $url = 'https://tss.sfs.db.com/api/v1/dealapi/factors/' . $dealId . '/factorsmostrecent';
        return $this->_guzzleRequestJson( $url, $bearerToken, 'GET' );
    }


    // https://tss.sfs.db.com/api/v1/dealapi/factors/2475/BondSummary
    public function apiGetBondSummary( int $dealId, string $bearerToken ): array {
        $url = 'https://tss.sfs.db.com/api/v1/dealapi/factors/' . $dealId . '/BondSummary';
        return $this->_guzzleRequestJson( $url, $bearerToken, 'GET' );
    }


    /**
     * @param int    $dealId
     * @param string $bearerToken
     *
     * @return array
     */
    public function apiGetLatestReportsPerType( int $dealId, string $bearerToken ): array {
        $url = 'https://tss.sfs.db.com/api/v1/dealapi/deal/' . $dealId . '/latestreportspertype';

        // {"limit":30,"reportCategories":[2],"excludeReportCategories":true,"orderBy":"reportDate DESC, description ASC"}
        $payload = [
            'limit' => 30,
            'reportCategories' => [2],
            'excludeReportCategories' => true,
            'orderBy' => 'reportDate DESC, description ASC',
        ];
        $body    = json_encode( $payload );
        return $this->_guzzlePostJson( $url,
                                       $bearerToken,
                                       $body );
    }






    //protected function _getRequestByUrl( string $url ): array {
    //    /**
    //     * @var array $request
    //     */
    //    foreach ( $this->NetworkListener->requests as $requestCounter => $request ):
    //        $requestUrl = $request[ 'params' ][ 'response' ][ 'url' ];
    //        if ( $url === $requestUrl ):
    //            return $request;
    //        endif;
    //    endforeach;
    //
    //    throw new \Exception( "Unable to find the request with this URL: " . $url );
    //}

    //protected function _getMostRecentFactors( int $dealId ): array {
    //    $url = 'https://tss.sfs.db.com/api/v1/dealapi/factors/' . $dealId . '/factorsmostrecent';
    //    $this->Debug->_debug( "Searching through all (" . count( $this->NetworkListener->requests ) . ") network requests for the URL: " . $url );
    //    $request = $this->_getRequestByUrl( $url );
    //
    //    dump( $request );
    //
    //    $body    = @$request[ 'data' ][ 'result' ][ 'body' ];
    //    $factors = @json_decode( $body, TRUE );
    //    return $factors ?? [];
    //}
    //
    //
    //
    //protected function _getLatestReportsPerType( int $dealId ): array {
    //    $url     = 'https://tss.sfs.db.com/api/v1/dealapi/deal/' . $dealId . '/latestreportspertype';
    //    $request = $this->_getRequestByUrl( $url );
    //
    //    dump( $request );
    //
    //    $body                 = @$request[ 'data' ][ 'result' ][ 'body' ];
    //    $latestReportsPerType = @json_decode( $body, TRUE );
    //    return $latestReportsPerType ?? [];
    //}


}