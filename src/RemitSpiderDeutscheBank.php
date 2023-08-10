<?php

namespace DPRMC\RemitSpiderDeutscheBank;


use DPRMC\RemitSpiderDeutscheBank\Helpers\Debug;
use DPRMC\RemitSpiderDeutscheBank\Helpers\DeutscheBankBrowser;
use DPRMC\RemitSpiderDeutscheBank\Helpers\Login;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 *
 */
class RemitSpiderDeutscheBank {


    public DeutscheBankBrowser $DeutscheBankBrowser;
    public Debug               $Debug;
    public Login               $Login;


    protected bool   $debug;
    protected string $pathToScreenshots;

    protected string $pathToPortfolioIds;
    protected string $pathToDealLinkSuffixes;
    protected string $pathToHistoryLinks;
    protected string $pathToFileIndex;
    protected string $timezone;

    protected array $portfolioIds;
    protected array $dealIds;

    protected Page $page;


    const  BASE_URL                    = 'https://tss.sfs.db.com';
//    const  PORTFOLIO_IDS_FILENAME      = '_portfolio_ids.json';
//    const  DEAL_LINK_SUFFIXES_FILENAME = '_deal_link_suffixes.json';
//    const  HISTORY_LINKS_FILENAME      = '_history_links.json';
//    const  FILE_INDEX_FILENAME         = '_file_index.json';

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
                                 string $user,
                                 string $pass,
                                 bool   $debug = FALSE,
                                 string $pathToScreenshots = '',
                                 string $pathToFileDownloads = '',
                                 string $timezone = self::DEFAULT_TIMEZONE
    ) {

        $this->debug                  = $debug;
        $this->pathToScreenshots      = $pathToScreenshots;
//        $this->pathToPortfolioIds     = $pathToPortfolioIds . self::PORTFOLIO_IDS_FILENAME;
//        $this->pathToDealLinkSuffixes = $pathToDealLinkSuffixes . self::DEAL_LINK_SUFFIXES_FILENAME;
//        $this->pathToHistoryLinks     = $pathToHistoryLinks . self::HISTORY_LINKS_FILENAME;
//        $this->pathToFileIndex        = $pathToFileIndex . self::FILE_INDEX_FILENAME;

        $this->timezone = $timezone;

        $this->DeutscheBankBrowser = new DeutscheBankBrowser( $chromePath );
        $this->DeutscheBankBrowser->page->setDownloadPath( $pathToFileDownloads );

        $this->Debug = new Debug( $this->DeutscheBankBrowser->page,
                                  $pathToScreenshots,
                                  $debug,
                                  $this->timezone );

        $this->Login = new Login( $this->DeutscheBankBrowser->page,
                                  $this->Debug,
                                  $user,
                                  $pass,
                                  $this->timezone );

//        $this->Portfolios = new Portfolios( $this->DeutscheBankBrowser->page,
//                                            $this->Debug,
//                                            $this->pathToPortfolioIds,
//                                            $this->timezone );
//
//        $this->Deals = new Deals( $this->DeutscheBankBrowser->page,
//                                  $this->Debug,
//                                  $this->pathToDealLinkSuffixes,
//                                  $this->timezone );
//
//        $this->HistoryLinks = new HistoryLinks( $this->DeutscheBankBrowser->page,
//                                                $this->Debug,
//                                                $this->pathToHistoryLinks,
//                                                $this->timezone );
//
//        $this->FileIndex = new FileIndex( $this->DeutscheBankBrowser->page,
//                                          $this->Debug,
//                                          $this->pathToFileIndex,
//                                          $this->timezone );
//
//        $this->PrincipalAndInterestFactors = new PrincipalAndInterestFactors( $this->DeutscheBankBrowser->page,
//                                                                              $this->Debug,
//                                                                              $this->timezone );
//        $this->PeriodicReportsSecured      = new PeriodicReportsSecured( $this->DeutscheBankBrowser->page,
//                                                                         $this->Debug,
//                                                                         $this->timezone );
    }


    /**
     *
     */
//    private function _loadIds() {
//        if ( file_exists( $this->pathToPortfolioIds ) ):
//            $this->portfolioIds = file( $this->pathToPortfolioIds );
//        else:
//            file_put_contents( $this->pathToPortfolioIds, NULL );
//        endif;
//
//        if ( file_exists( $this->pathToDealLinkSuffixes ) ):
//            $this->dealIds = file( $this->pathToDealLinkSuffixes );
//        else:
//            file_put_contents( $this->pathToDealLinkSuffixes, NULL );
//        endif;
//    }


    /**
     * A little helper function to turn on debugging from the top level object.
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