<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;

use GuzzleHttp\Client;

trait RequestTrait {


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


    protected function _guzzleRequest( string $url, string $bearerToken, string $method = 'GET' ): string {
        $client = new Client();
        $options = [];

        $response = $client->request( $method, $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $bearerToken,
                'Content-Type'  => 'application/json;charset=utf-8',
                'Accept'        => 'application/json',
            ],
        ] );
        return $response->getBody()->getContents();
    }


    protected function _guzzleRequestJson( string $url, string $bearerToken, string $method = 'GET' ): array {
        $body = $this->_guzzleRequest( $url, $bearerToken, $method );
        return json_decode( $body, TRUE );
    }


    protected function _guzzlePostJson( string $url, string $bearerToken, string $body ): array {
        $client = new Client();
        $options = [];

        $response = $client->request( 'POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $bearerToken,
                'Content-Type'  => 'application/json;charset=utf-8',
                'Accept'        => 'application/json',
            ],
            'body'    => $body,
        ] );
        $contents = $response->getBody()->getContents();
        return json_decode( $contents, TRUE );
    }





}