<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;

use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use HeadlessChromium\Page;

/**
 *
 */
class NetworkListener {

    protected Page   $Page;
    protected string $pathToScreenshots;
    protected bool   $debug;
    protected string $timezone;

    const CLIP_DEFAULT_HEIGHT = DeutscheBankBrowser::BROWSER_WINDOW_SIZE_HEIGHT;
    const CLIP_DEFAULT_WIDTH  = DeutscheBankBrowser::BROWSER_WINDOW_SIZE_WIDTH;

    public string $accessToken;
    public string $idToken;
    public string $refreshToken;
    public int    $expiresIn;
    public int    $refreshExpiresIn;

    public string $tokenType;
    public bool   $notBeforePolicy;
    public string $sessionState;
    public string $scope;


    public int $requestCount = 0;

    public array $requests = [];


    public function __construct( Page   &$page,
                                 string $pathToScreenshots = '',
                                 bool   $debug = FALSE,
                                 string $timezone = RemitSpiderDeutscheBank::DEFAULT_TIMEZONE ) {
        $this->Page              = $page;
        $this->pathToScreenshots = $pathToScreenshots;
        $this->debug             = $debug;
        $this->timezone          = $timezone;
    }


    public function enableListener() {
        $this->Page->getSession()->on( 'method:Network.responseReceived', function ( array $params ): void {


            $request_id = @$params[ "requestId" ];
            $data       = @$this->Page->getSession()->sendMessageSync( new \HeadlessChromium\Communication\Message( 'Network.getResponseBody', [ 'requestId' => $request_id ] ) )->getData();

            $this->_setAccessTokenIfPresent( $params, $data );

            $this->requests[ $this->requestCount ] = [
                'params' => $params,
                'data'   => $data
            ];

            $this->requestCount++;
            //if ( $this->_isRequestThatContainsAccessToken( $params ) ):
            //    $stringJson = @$data[ "result" ][ "body" ];
            //    $json       = json_decode( $stringJson, TRUE );
            //
            //
            //endif;
        } );
    }


    /**
     * @param array $params
     * @param array $data
     *
     * @return void
     */
    protected function _setAccessTokenIfPresent( array $params = [], array $data = [] ) {
        $stringJson = @$data[ "result" ][ "body" ];

        if ( empty( $stringJson ) ) :
            return;
        endif;

        $json = json_decode( $stringJson, TRUE );
        if ( NULL === $json ):
            return;
        endif;

        if ( isset( $json[ "access_token" ] ) ):
            $this->accessToken = $json[ 'access_token' ];
        endif;

        if ( isset( $json[ "id_token" ] ) ):
            $this->idToken = $json[ 'id_token' ];
        endif;

        if ( isset( $json[ "refresh_token" ] ) ):
            $this->refreshToken = $json[ 'refresh_token' ];
        endif;

        if ( isset( $json[ "expires_in" ] ) ):
            $this->expiresIn = $json[ 'expires_in' ];
        endif;

        if ( isset( $json[ "refresh_expires_in" ] ) ):
            $this->refreshExpiresIn = $json[ 'refresh_expires_in' ];
        endif;

        if ( isset( $json[ "token_type" ] ) ):
            $this->tokenType = $json[ 'token_type' ];
        endif;

        if ( isset( $json[ "not-before-policy" ] ) ):
            $this->notBeforePolicy = $json[ 'not-before-policy' ];
        endif;

        if ( isset( $json[ "session_state" ] ) ):
            $this->sessionState = $json[ 'session_state' ];
        endif;

        if ( isset( $json[ "scope" ] ) ):
            $this->scope = $json[ 'scope' ];
        endif;
    }

}