<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;


use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use GuzzleHttp\Client;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 *
 */
class Deals {


    protected Page   $Page;
    protected Debug  $Debug;
    protected string $timezone;

    public ?string           $csrf = NULL;
    public CookiesCollection $cookies;

    public readonly array  $config;
    public readonly string $bearerToken;


    /**
     * @param Page   $Page
     * @param Debug  $Debug
     * @param string $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $timezone = RemitSpiderDeutscheBank::DEFAULT_TIMEZONE ) {
        $this->Page  = $Page;
        $this->Debug = $Debug;

        $this->timezone = $timezone;
    }


    public function getDeals() {
        $this->Page->getSession()->on( 'method:Network.responseReceived', function ( array $params ): void {

            $request_id = @$params[ "requestId" ];
            $data       = @$this->Page->getSession()->sendMessageSync( new \HeadlessChromium\Communication\Message( 'Network.getResponseBody', [ 'requestId' => $request_id ] ) )->getData();


            if ( $this->_isRequestThatContainsAccessToken( $data ) ):
                $stringJson = @$data[ "result" ][ "body" ];
                $json       = json_decode( $stringJson, TRUE );

                $prettyJson = print_r($json, true);

                $filepath = '/Users/michaeldrennen/PhpstormProjects/DPRMC/RemitSpiderDeutscheBank/prettyjson.txt';
                file_put_contents( $filepath, $prettyJson );

                //flush();
                //die();

                //$this->bearerToken =
            endif;



        } );
    }


    protected function _isRequestThatContainsAccessToken( array $data ): bool {
        if ( 'https://identity.db.com/auth/realms/global/protocol/openid-connect/token' == $data[ 'url' ] ):
            return TRUE;
        endif;
        return FALSE;
    }


}