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

        $this->Page->navigate( $url )->waitForNavigation( Page::NETWORK_IDLE );
        $this->Debug->_screenshot( '6_deal_' . $dealId . '_page' );
        $this->Debug->_html( '6_deal_' . $dealId . '_page' );


        $deal = new Deal();

        $deal->mostRecentFactors = $this->_getMostRecentFactors( $dealId );
        $deal->latestReportsPerType = $this->_getLatestReportsPerType( $dealId );

        return $deal;


        //$pageSize = 30;
        //
        //$start = 0;
        //$end   = $pageSize;
        //$total = 999;
        //
        //$debugRequestCounter = 6;
        //
        //$deals = [];
        //
        //while ( $end <= $total ):
        //
        //    $url = 'https://tss.sfs.db.com/api/v1/dealapi/deal?start=' . $start . '&end=' . $end . '&orderby=name';
        //
        //    $this->Page->navigate( $url )->waitForNavigation( Page::NETWORK_IDLE );
        //    $this->Debug->_screenshot( $debugRequestCounter . '_deal_1_page' );
        //    $this->Debug->_html( $debugRequestCounter . '_deal_1_page' );
        //
        //    $html  = $this->Page->getHtml();
        //    $json  = strip_tags( $html );
        //    $array = json_decode( $json, TRUE );
        //
        //
        //    $total = $array[ 'total' ];
        //    $data  = $array[ 'data' ];
        //
        //    $start = $end + 1;
        //    $end   = $end + $pageSize;
        //
        //    foreach ( $data as $deal ):
        //        $deals[] = $deal;
        //    endforeach;
        //
        //    $debugRequestCounter++;
        //
        //    sleep( 1 );
        //endwhile;

        return [];
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
        $url     = 'https://tss.sfs.db.com/api/v1/dealapi/factors/' . $dealId . '/factorsmostrecent';
        $request = $this->_getRequestByUrl( $url );
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
        $body                 = @$request[ 'data' ][ 'result' ][ 'body' ];
        $latestReportsPerType = @json_decode( $body, TRUE );
        return $latestReportsPerType ?? [];
    }


}