<?php

use HeadlessChromium\Page;
use PHPUnit\Framework\TestCase;


class RemitSpiderDeutscheBankTest extends TestCase {

    protected static \DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank $spider;

    protected static bool $debug = TRUE;

    const TIMEZONE = 'America/New_York';


    protected $handler;


    private static function _getSpider(): \DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank {


        return new \DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank( $_ENV[ 'CHROME_PATH' ],
                                                                           self::$debug,
                                                                           $_ENV[ 'PATH_TO_SCREENSHOTS' ],
                                                                           $_ENV[ 'PATH_TO_DOWNLOADS' ],
                                                                           self::TIMEZONE );
    }

    public static function setUpBeforeClass(): void {
        self::$spider = self::_getSpider();
    }


    public static function tearDownAfterClass(): void {
        self::$spider->DeutscheBankBrowser->page->close();
    }


    /**
     * @test
     */
    public function testConstructor() {
        $spider = $this->_getSpider();
        $this->assertInstanceOf( \DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank::class,
                                 $spider );
    }


    /**
     * @test
     * @group badlogin
     */
    public function testBadLoginShouldThrowException() {
        $this->expectException( \DPRMC\RemitSpiderDeutscheBank\Exceptions\ExceptionLoginIncorrect::class );
        $user   = 'poop';
        $pass   = 'fart';
        $spider = $this->_getSpider();
        $spider->Login->login( $user, $pass );
    }


    /**
     * @test
     * @group login
     */
    public function testLoginAndLogout() {
        $user          = $_ENV[ 'CUSTODIAN_USER' ];
        $pass          = $_ENV[ 'CUSTODIAN_PASS' ];
        self::$spider  = $this->_getSpider();
        $postLoginHtml = self::$spider->Login->login( $user, $pass );
        $this->assertIsString( $postLoginHtml );
        $this->assertNotEmpty( self::$spider->NetworkListener->accessToken );
    }


    /**
     * @test
     * @group do
     */
    public function testGetDealOverview(){
        $user          = $_ENV[ 'CUSTODIAN_USER' ];
        $pass          = $_ENV[ 'CUSTODIAN_PASS' ];
        self::$spider  = $this->_getSpider();
        $postLoginHtml = self::$spider->Login->login( $user, $pass );

        $deal = self::$spider->DealHelper->getDealOverview( 2475);

        print_r(self::$spider->NetworkListener->requests);

        print_r($deal->mostRecentFactors);
        print_r($deal->latestReportsPerType);

        $this->assertIsArray($deal->mostRecentFactors);
        $this->assertIsArray($deal->latestReportsPerType);

        $this->assertNotEmpty($deal->mostRecentFactors);
        $this->assertNotEmpty($deal->latestReportsPerType);
    }




    /**
     * @test
     * @group async
     */
    public function testGetSomeDeals() {


        //$html = self::$spider->Login->login( $_ENV[ 'CUSTODIAN_USER' ], $_ENV[ 'CUSTODIAN_PASS' ] );
        //
        //$this->assertIsString( $html );
        //
        //$deals = self::$spider->Deals->getDeals();





        $deals = self::$spider->Deals->getDealsWhenLoggedOut();


        $this->assertIsArray( $deals );
        $this->assertGreaterThan( 0, count( $deals ) );


        file_put_contents( '/Users/michaeldrennen/PhpstormProjects/DPRMC/RemitSpiderDeutscheBank/tests/temp_files/deals.json', json_encode( $deals ) );

        //$this->handler = function (array $params): void {
        //    $url = @$params["response"]["url"];
        //
        //    if ( str_contains( $url, "PATH_TO_FILE" ) ):
        //
        //
        //        self::$spider->DeutscheBankBrowser->page->getSession()->removeListener('method:Network.responseReceived', $this->handler);
        //
        //        $request_id         = @$params["requestId"];
        //        $data               = @self::$spider->DeutscheBankBrowser->page->getSession()->sendMessageSync(new HeadlessChromium\Communication\Message('Network.getResponseBody', ['requestId' => $request_id]))->getData();
        //
        //        //CONTENT OF FILE
        //        $content            = @$data["result"]["body"];
        //
        //        echo $content;
        //    endif;
        //};
        //
        //
        //
        //
        //
        //$postLoginHtml = self::$spider->Login->login( $_ENV[ 'CUSTODIAN_USER' ], $_ENV[ 'CUSTODIAN_PASS' ] );
        //
        //
        //self::$spider->DeutscheBankBrowser->page->getSession()->on('method:Network.responseReceived', function (array $params): void {
        //
        //    $request_id         = @$params["requestId"];
        //    $data               = @self::$spider->DeutscheBankBrowser->page->getSession()->sendMessageSync(new HeadlessChromium\Communication\Message('Network.getResponseBody', ['requestId' => $request_id]))->getData();
        //
        //    //CONTENT OF FILE
        //    $content            = @$data["result"]["body"];
        //    print_r($params);
        //    echo "\n**********************************************\n";
        //    echo $content;
        //    echo "\n**********************************************\n";
        //
        //    flush();
        //});
        //
        //$searchUrl = 'https://tss.sfs.db.com/search';
        //self::$spider->DeutscheBankBrowser->page->navigate($searchUrl)->waitForNavigation(Page::NETWORK_IDLE);
        //
        //self::$spider->Debug->_html( '99_searchUrl' );
        //self::$spider->Debug->_screenshot( '99_searchUrl' );
        //
        //
        //
        //
        //
        //
        //
        //return;
        //
        //$start = 0;
        //$end   = 30;
        //$url   = 'https://tss.sfs.db.com/api/v1/dealapi/deal?start=' . $start . '&end=' . $end . '&orderby=name';
        //
        //
        //$allCookies = self::$spider->DeutscheBankBrowser->page->getAllCookies();
        //
        //
        //print_r($allCookies);
        //flush();
        //return;
        //
        //
        ////$response = $spider->guzzle->get( $url );
        ////
        ////$json = $response->getBody()->getContents();
        ////
        ////$result = json_decode( $json, TRUE );
        ////
        ////$start = $result[ 'start' ];
        ////$end   = $result[ 'end' ];
        ////$total = $result[ 'total' ];
        ////$data  = $result[ 'data' ];
        //
        //
        //self::$spider->DeutscheBankBrowser->page->navigate($url)->waitForNavigation(Page::NETWORK_IDLE);
        //
        //self::$spider->Debug->_debug( '99_url: ' . $url );
        //self::$spider->Debug->_screenshot( '99_url: ' . $url );
        //self::$spider->Debug->_html( '99_url: ' . $url );
        //
        //
        //
        //file_put_contents( '/Users/michaeldrennen/PhpstormProjects/DPRMC/RemitSpiderDeutscheBank/tests/temp_files/deals.json', $json );




    }





}