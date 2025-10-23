<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;


use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use GuzzleHttp\Client;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 *
 */
class Deals {

    public array $deals = [];

    const ANONYMOUS_TOKEN_URL = 'https://tss.sfs.db.com/api/v1/authapi/account/anonymoustoken';


    protected Page            $Page;
    protected NetworkListener $NetworkListener;
    protected Debug           $Debug;
    protected string          $timezone;


    public CookiesCollection $cookies;

    public readonly array $config;


    /**
     * [access_token] =>
     * eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJrUVVReE9RS3NHUjJmdTQ4OU91RjBxQVcyemlYRHEzTFE0a3h5UmJyNDlFIn0.eyJleHAiOjE3Mzg4NzczMDQsImlhdCI6MTczODg3NzAwNCwiYXV0aF90aW1lIjoxNzM4ODc2OTk3LCJqdGkiOiJhYzYyNmZmZC01MDEyLTQ4YWYtYjViZC1lZGNiNGEzMDI4ODUiLCJpc3MiOiJodHRwczovL2lkZW50aXR5LmRiLmNvbS9hdXRoL3JlYWxtcy9nbG9iYWwiLCJhdWQiOiIxNDYxMzQtMV9UQVNfSW52ZXN0b3JfUmVwb3J0aW5nX3Byb2QiLCJzdWIiOiJkODY3NTYyNS0xYTI3LTQ4YTEtOTUwNC1mNTc0OTY0ZWNiNzEiLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiIxNDYxMzQtMV9UQVNfSW52ZXN0b3JfUmVwb3J0aW5nX3Byb2QiLCJub25jZSI6IjY2YTExODkxLTFlZTktNDc3Ni04NzY3LWI1OTY3NTIxODFkMSIsInNlc3Npb25fc3RhdGUiOiJiNWM1YWI1Ny01ZmNiLTRjOWMtOGU5Zi02M2UyZmU5ZmVmMTQiLCJhY3IiOiIxIiwic2lkIjoiYjVjNWFiNTctNWZjYi00YzljLThlOWYtNjNlMmZlOWZlZjE0IiwiZWlkcF9hY3IiOiIxIiwiZW1haWxfdmVyaWZpZWQiOnRydWUsImFtciI6WyJwd2QiXSwibGFzdF9hdXRoIjoxNzM4ODc2Mjk1LCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJiYWNrb2ZmaWNlQGRlZXJwYXJrcmQuY29tIiwiZW1haWwiOiJiYWNrb2ZmaWNlQGRlZXJwYXJrcmQuY29tIn0.mnZWjiHyUZoSc5O8dI0oyWzbwLX64L8PhjX3DCz4XpjZwijzK3uRAGfDVZ7VtvInCuI5T36mEvo430DMdf2Urym7RDeeVrtAdXDT1qGA2bahXObjIQ-mcIfatNtFkYRgJRMngrLV4fu9H4NC23WcqRoJlviMiuIZc2Qou5GE_zKZJf40jrpH7cSvNDIwe6v3IJpNuMGDlMJTg_xXHvt52OsAABPeJDpZAkYPE7rr05AZb5BOgiBRFeaNGS2tO571bZmV4b76vaFfmmAb6rfPpGLG8sVfdgzLzlodd-h8rnm3TMxqwC9_6-fKVKFIiRcK6WGQsqb5wWEEV6OjKTvMOQ
     * [expires_in] => 300
     * [refresh_expires_in] => 900
     * [refresh_token] =>
     * eyJhbGciOiJIUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICIwOGJjMTQyYS0xYzk2LTQ1MjUtYTllNC1iOWEwZjNmYTI4ZDUifQ.eyJleHAiOjE3Mzg4Nzc5MDQsImlhdCI6MTczODg3NzAwNCwianRpIjoiN2E2NmY4YjItYTVlZC00YWI1LWEzMDgtYzA2ODE2NjFlYTIwIiwiaXNzIjoiaHR0cHM6Ly9pZGVudGl0eS5kYi5jb20vYXV0aC9yZWFsbXMvZ2xvYmFsIiwiYXVkIjoiaHR0cHM6Ly9pZGVudGl0eS5kYi5jb20vYXV0aC9yZWFsbXMvZ2xvYmFsIiwic3ViIjoiZDg2NzU2MjUtMWEyNy00OGExLTk1MDQtZjU3NDk2NGVjYjcxIiwidHlwIjoiUmVmcmVzaCIsImF6cCI6IjE0NjEzNC0xX1RBU19JbnZlc3Rvcl9SZXBvcnRpbmdfcHJvZCIsIm5vbmNlIjoiNjZhMTE4OTEtMWVlOS00Nzc2LTg3NjctYjU5Njc1MjE4MWQxIiwic2Vzc2lvbl9zdGF0ZSI6ImI1YzVhYjU3LTVmY2ItNGM5Yy04ZTlmLTYzZTJmZTlmZWYxNCIsInNpZCI6ImI1YzVhYjU3LTVmY2ItNGM5Yy04ZTlmLTYzZTJmZTlmZWYxNCJ9.igT1CuqwA7CsRJU4SlER_vOwMIeTGlfGiRWAzi_Qpvc
     * [token_type] => Bearer
     * [id_token] =>
     * eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJrUVVReE9RS3NHUjJmdTQ4OU91RjBxQVcyemlYRHEzTFE0a3h5UmJyNDlFIn0.eyJleHAiOjE3Mzg4NzczMDQsImlhdCI6MTczODg3NzAwNCwiYXV0aF90aW1lIjoxNzM4ODc2OTk3LCJqdGkiOiIyNmIyMDc0Ni02YmViLTQ1YWUtYTRlZi05ZWIxZGFjNTRlNDMiLCJpc3MiOiJodHRwczovL2lkZW50aXR5LmRiLmNvbS9hdXRoL3JlYWxtcy9nbG9iYWwiLCJhdWQiOiIxNDYxMzQtMV9UQVNfSW52ZXN0b3JfUmVwb3J0aW5nX3Byb2QiLCJzdWIiOiJkODY3NTYyNS0xYTI3LTQ4YTEtOTUwNC1mNTc0OTY0ZWNiNzEiLCJ0eXAiOiJJRCIsImF6cCI6IjE0NjEzNC0xX1RBU19JbnZlc3Rvcl9SZXBvcnRpbmdfcHJvZCIsIm5vbmNlIjoiNjZhMTE4OTEtMWVlOS00Nzc2LTg3NjctYjU5Njc1MjE4MWQxIiwic2Vzc2lvbl9zdGF0ZSI6ImI1YzVhYjU3LTVmY2ItNGM5Yy04ZTlmLTYzZTJmZTlmZWYxNCIsImF0X2hhc2giOiJ1UFVJdllpeDl3QTIwcGFRT1V4VmVnIiwiYWNyIjoiMSIsInNpZCI6ImI1YzVhYjU3LTVmY2ItNGM5Yy04ZTlmLTYzZTJmZTlmZWYxNCIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJsYXN0X2F1dGgiOjE3Mzg4NzYyOTUsImVtYWlsIjoiYmFja29mZmljZUBkZWVycGFya3JkLmNvbSJ9.QNn-x3nU7uScQsKO2_jQru3Lkq0PT5BWWFlhJ-VhdePBN-SwzcBR7RoL3ccmeJs2RZ2SnonXvznMDyCCkQL-26aQmZ2bWqj6Rt5TSK-H-NaL_x2wFgP62jK-ThoP0iLd_GQ-WUS770TRy4dXXbRz8wk7yiVdxRNQLV-sx4Ov06gzx1I7LAU8ctdWAVp4x1jdATyMuD89C8B-Cpwlrb1tXSsEsr8dyJIe2-OomqQ03ubW9p3NxWa2Z22fMBA9gUe_jgK6bUHOixhdyO7KL_3zESQQJ_nwuKqcnVeJ47JjOJuAnjxwIjvAStkc5AQrePClXi4ekHT0hgyqlT8q3gbk_A
     * [not-before-policy] => 0
     * [session_state] => b5c5ab57-5fcb-4c9c-8e9f-63e2fe9fef14
     * [scope] => openid email roles
     */

    public readonly string $accessToken;
    public readonly string $idToken;
    public readonly string $refreshToken;
    public readonly int    $expiresIn;
    public readonly int    $refreshExpiresIn;
    public readonly string $tokenType;
    public readonly bool   $notBeforePolicy;
    public readonly string $sessionState;
    public readonly string $scope;


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
     * @param string|NULL $pathToCacheJson
     * @param int|NULL    $limit
     *
     * @return array
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\JavascriptException
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function getDealsWhenLoggedOut( string $pathToCacheJson = NULL, int $limit = NULL ) {

        $this->Page->getSession()->on( 'method:Network.responseReceived', function ( array $params ): void {

            $request_id = @$params[ "requestId" ];
            $data       = @$this->Page->getSession()->sendMessageSync( new \HeadlessChromium\Communication\Message( 'Network.getResponseBody', [ 'requestId' => $request_id ] ) )->getData();

            // START DEBUG
            //$filepath     = '/Users/michaeldrennen/PhpstormProjects/DPRMC/RemitSpiderDeutscheBank/tests/temp_files/params_' . md5( json_encode( $data ) ) . '.txt';
            //$prettyParams = print_r( $params, TRUE );
            //$written      = file_put_contents( $filepath, $prettyParams );
            //
            //$filepath   = '/Users/michaeldrennen/PhpstormProjects/DPRMC/RemitSpiderDeutscheBank/tests/temp_files/data_' . md5( json_encode( $data ) ) . '.txt';
            //$prettyData = print_r( $data, TRUE );
            //$written    = file_put_contents( $filepath, $prettyData );
            // END DEBUG


            if ( $this->_isRequestThatContainsAnonymousAccessToken( $params ) ):
                $anonymousAccessToken = @$data[ "result" ][ "body" ];

                $this->accessToken = $anonymousAccessToken;
            endif;
        } );

        $this->Page->navigate( 'https://tss.sfs.db.com/search' )->waitForNavigation( Page::NETWORK_IDLE );
        $this->Debug->_screenshot( '1_search_page' );
        $this->Debug->_html( '1_search_page' );

        // This query selector just would not work.
        //$this->Page->mouse()->find( 'button[data-qa-target="termsofuse_decline"] span' )->click();

        $agreeAndContinueX = 950;
        $agreeAndContinueY = 630;
        $this->Page->mouse()->move( $agreeAndContinueX, $agreeAndContinueY )->click();

        // DEBUG SCREENSHOT to see where the mouse will click.
        //$clip = new Clip(0,0,$agreeAndContinueX,$agreeAndContinueY);
        //$this->Debug->_screenshot( '2_search_page_clip', $clip );

        $this->Debug->_screenshot( '2_search_page' );
        $this->Debug->_html( '2_search_page' );

        $extraHeaders = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];
        $this->Page->setExtraHTTPHeaders( $extraHeaders );


        $pageSize = 30;
        $start    = 0;
        $end      = $pageSize;
        $total    = 999;

        $debugRequestCounter = 6;


        while ( $end <= $total ):
            $url = 'https://tss.sfs.db.com/api/v1/dealapi/deal?start=' . $start . '&end=' . $end . '&orderby=name';

            $this->Page->navigate( $url )->waitForNavigation( Page::NETWORK_IDLE );
            $this->Debug->_screenshot( $debugRequestCounter . '_deal_1_page' );
            $this->Debug->_html( $debugRequestCounter . '_deal_1_page' );

            $html  = $this->Page->getHtml();
            $json  = strip_tags( $html );
            $array = json_decode( $json, TRUE );


            $total = $array[ 'total' ];
            $data  = $array[ 'data' ];

            $start = $end + 1;
            $end   = $end + $pageSize;

            foreach ( $data as $deal ):
                $this->deals[] = $deal;
            endforeach;

            $debugRequestCounter++;

            // Let's not beat their servers to death.
            sleep( 1 );

            if ( $limit && count( $this->deals ) >= $limit ):
                break;
            endif;
        endwhile;

        if ( $pathToCacheJson ):
            $bytesWritten = file_put_contents( $pathToCacheJson, json_encode( $this->deals ) );
            if ( FALSE === $bytesWritten ):
                throw new \Exception( "Unable to write deals to file: " . $pathToCacheJson . "  , but the deals were saved to the public array \$this->deals." );
            endif;
        endif;

        return $this->deals;
    }


    /**
     * This works when logged in.
     * However, the access token times out before it can get all the deals.
     * I can put time in figuring out the request to use the refresh token
     * to get a new access token, OR
     * I can just create a method to grab the deals while logged out using
     * an anonymous token. Which I did, and it works fine.
     *
     * @return array
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\JavascriptException
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function getDeals() {
        $prettyJson = NULL;


        $this->Page->getSession()->on( 'method:Network.responseReceived', function ( array $params ): void {
            $request_id = @$params[ "requestId" ];
            $data       = @$this->Page->getSession()->sendMessageSync( new \HeadlessChromium\Communication\Message( 'Network.getResponseBody', [ 'requestId' => $request_id ] ) )->getData();

            if ( $this->_isRequestThatContainsAccessToken( $params ) ):
                $stringJson = @$data[ "result" ][ "body" ];
                $json       = json_decode( $stringJson, TRUE );

                $this->accessToken      = $json[ 'access_token' ];
                $this->idToken          = $json[ 'id_token' ];
                $this->refreshToken     = $json[ 'refresh_token' ];
                $this->expiresIn        = $json[ 'expires_in' ];
                $this->refreshExpiresIn = $json[ 'refresh_expires_in' ];
                $this->tokenType        = $json[ 'token_type' ];
                $this->notBeforePolicy  = $json[ 'not-before-policy' ];
                $this->sessionState     = $json[ 'session_state' ];
                $this->scope            = $json[ 'scope' ];
            endif;
        } );


        $this->Page->navigate( 'https://tss.sfs.db.com/search' )->waitForNavigation( Page::NETWORK_IDLE );

        $this->Debug->_screenshot( '5_search_page' );
        $this->Debug->_html( '5_search_page' );


        $extraHeaders = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];
        $this->Page->setExtraHTTPHeaders( $extraHeaders );


        $pageSize = 30;

        $start = 0;
        $end   = $pageSize;
        $total = 999;

        $debugRequestCounter = 6;

        $deals = [];

        while ( $end <= $total ):

            $url = 'https://tss.sfs.db.com/api/v1/dealapi/deal?start=' . $start . '&end=' . $end . '&orderby=name';

            $this->Page->navigate( $url )->waitForNavigation( Page::NETWORK_IDLE );
            $this->Debug->_screenshot( $debugRequestCounter . '_deal_1_page' );
            $this->Debug->_html( $debugRequestCounter . '_deal_1_page' );

            $html  = $this->Page->getHtml();
            $json  = strip_tags( $html );
            $array = json_decode( $json, TRUE );


            $total = $array[ 'total' ];
            $data  = $array[ 'data' ];

            $start = $end + 1;
            $end   = $end + $pageSize;

            foreach ( $data as $deal ):
                $deals[] = $deal;
            endforeach;

            $debugRequestCounter++;

            sleep( 1 );
        endwhile;

        return $deals;
    }


    /**
     * @param array $params
     *
     * @return bool
     */
    protected function _isRequestThatContainsAccessToken( array $params ): bool {
        if ( !isset( $params[ 'response' ][ 'url' ] ) ):
            return FALSE;
        endif;

        if ( 'https://identity.db.com/auth/realms/global/protocol/openid-connect/token' == $params[ 'response' ][ 'url' ] ):
            return TRUE;
        endif;
        return FALSE;
    }


    protected function _isRequestThatContainsAnonymousAccessToken( array $params ): bool {
        if ( !isset( $params[ 'response' ][ 'url' ] ) ):
            return FALSE;
        endif;

        if ( self::ANONYMOUS_TOKEN_URL == $params[ 'response' ][ 'url' ] ):
            return TRUE;
        endif;
        return FALSE;
    }


    public function refreshAccessToken() {
        $this->Debug->_debug( 'Refreshing access token' );

        // https://identity.db.com/auth/realms/global/protocol/openid-connect/token


    }

}