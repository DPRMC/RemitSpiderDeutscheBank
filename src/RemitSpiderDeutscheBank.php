<?php

namespace DPRMC\RemitSpiderDeutscheBank;


use DPRMC\RemitSpiderDeutscheBank\Helpers\DealHelper;
use DPRMC\RemitSpiderDeutscheBank\Helpers\Deals;
use DPRMC\RemitSpiderDeutscheBank\Helpers\Debug;
use DPRMC\RemitSpiderDeutscheBank\Helpers\DeutscheBankBrowser;
use DPRMC\RemitSpiderDeutscheBank\Helpers\Login;
use DPRMC\RemitSpiderDeutscheBank\Helpers\NetworkListener;
use GuzzleHttp\Client;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 *
 */
class RemitSpiderDeutscheBank {


    public DeutscheBankBrowser $DeutscheBankBrowser;

    protected Page $page;

    public Debug $Debug;

    public NetworkListener $NetworkListener;

    public Login $Login;

    public Deals $Deals;

    public DealHelper $DealHelper;


    public Client $guzzle;


    protected bool   $debug;
    protected string $pathToScreenshots;

    protected string $pathToPortfolioIds;
    protected string $pathToDealLinkSuffixes;
    protected string $pathToHistoryLinks;
    protected string $pathToFileIndex;
    protected string $timezone;

    protected array $portfolioIds;
    protected array $dealIds;


    const  BASE_URL = 'https://tss.sfs.db.com';


    const DEFAULT_TIMEZONE = 'America/New_York';

    /**
     * TESTING, not sure if this will work.
     *
     * @var CookiesCollection Saving the cookies post login. When the connection dies for no reason, I can restart the
     *      session.
     */
    public CookiesCollection $cookies;


    // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1710/abn-amro-2003-4
    protected array $linksToDealsBySecurityId = [];


    public function __construct( string $chromePath,
                                 bool   $debug = FALSE,
                                 string $pathToScreenshots = '',
                                 string $pathToFileDownloads = '',
                                 string $timezone = self::DEFAULT_TIMEZONE
    ) {

        $this->debug             = $debug;
        $this->pathToScreenshots = $pathToScreenshots;
        $this->timezone          = $timezone;

        $this->DeutscheBankBrowser = new DeutscheBankBrowser( $chromePath );
        $this->DeutscheBankBrowser->page->setDownloadPath( $pathToFileDownloads );

        $this->guzzle = new Client();

        $this->Debug = new Debug( $this->DeutscheBankBrowser->page,
                                  $pathToScreenshots,
                                  $debug,
                                  $this->timezone );

        $this->NetworkListener = new NetworkListener( $this->DeutscheBankBrowser->page,
                                                      $pathToScreenshots,
                                                      $this->debug,
                                                      $this->timezone );

        $this->Login = new Login( $this->DeutscheBankBrowser->page,
                                  $this->NetworkListener,
                                  $this->Debug,
                                  $this->timezone );

        $this->Deals = new Deals( $this->DeutscheBankBrowser->page,
                                  $this->NetworkListener,
                                  $this->Debug,
                                  $this->timezone );

        $this->DealHelper = new DealHelper( $this->DeutscheBankBrowser->page,
                                            $this->NetworkListener,
                                            $this->Debug,
                                            $this->timezone );


        $this->NetworkListener->enableListener();


    }


    /**
     * A little helper function to turn on debugging from the top level object.
     *
     * @return void
     */
    public function enableDebug(): void {
        $this->debug = TRUE;
        $this->Debug->enableDebug();
        $this->Debug->_debug( "Debug has been enabled." );
    }


    /**
     * @return void
     */
    public function disableDebug(): void {
        $this->debug = FALSE;
        $this->Debug->disableDebug();
        $this->Debug->_debug( "Debug has been disabled." );
    }



}