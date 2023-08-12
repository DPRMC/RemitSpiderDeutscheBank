<?php

namespace DPRMC\RemitSpiderDeutscheBank\Helpers;

use Carbon\Carbon;
use DPRMC\RemitSpiderDeutscheBank\RemitSpiderDeutscheBank;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;

/**
 *
 */
class Debug {

    protected Page   $page;
    protected string $pathToScreenshots;
    protected bool   $debug;
    protected string $timezone;

    const CLIP_DEFAULT_HEIGHT = DeutscheBankBrowser::BROWSER_WINDOW_SIZE_HEIGHT;
    const CLIP_DEFAULT_WIDTH  = DeutscheBankBrowser::BROWSER_WINDOW_SIZE_WIDTH;

    public function __construct( Page   &$page,
                                 string $pathToScreenshots = '',
                                 bool   $debug = FALSE,
                                 string $timezone = RemitSpiderDeutscheBank::DEFAULT_TIMEZONE ) {
        $this->page              = $page;
        $this->pathToScreenshots = $pathToScreenshots;
        $this->debug             = $debug;
        $this->timezone          = $timezone;
    }

    public function enableDebug(): void {
        $this->_debug( "Debug now ENABLED. Screenshots are being saved to: " . $this->pathToScreenshots );
        $this->debug = TRUE;
    }

    public function disableDebug(): void {
        $this->_debug( "Debug now DISABLED. Remember to delete screenshots from: " . $this->pathToScreenshots );
        $this->debug = FALSE;
    }


    /**
     * This is just a little helper function to clean up some debug code.
     *
     * @param string $suffix
     * @param \HeadlessChromium\Clip|NULL $clip
     *
     * @return void
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function _screenshot( string $suffix, Clip $clip = NULL ) {
        if ( ! $this->debug ):
            return;
        endif;
        $now   = Carbon::now( $this->timezone );
        $time  = $now->timestamp;
        $micro = $now->microsecond;

        if ( is_null( $clip ) ):
            $clip = new Clip( 0, 0, self::CLIP_DEFAULT_WIDTH, self::CLIP_DEFAULT_HEIGHT, 1 );
        endif;

        $this->page->screenshot( [ 'clip' => $clip ] )
                   ->saveToFile( $this->pathToScreenshots . $time . '_' . $micro . '_' . $suffix . '.jpg' );

    }


    public function _html( string $filename ) {
        $now   = Carbon::now( $this->timezone );
        $time  = $now->timestamp;
        $micro = $now->microsecond;
        if ( $this->debug ):
            $html = @$this->page->getHtml();
            file_put_contents( $this->pathToScreenshots . $time . '_' . $micro . '_' . $filename . '.html', $html );
        endif;
    }

    public function _debug( string $message, bool $die = FALSE ) {
        if ( $this->debug ):
            echo "\n" . $message;
            flush();
            if ( $die ):
                die();
            endif;
        endif;
    }
}