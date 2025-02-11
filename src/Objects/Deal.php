<?php

namespace DPRMC\RemitSpiderDeutscheBank\Objects;

class Deal {

    protected array $exceptions = [];

    public array $mostRecentFactors = [];

    public array $latestReportsPerType = [];

    public function __construct() {

    }


    /**
     * @return void
     */
    protected function _resetExceptions() {
        $this->exceptions = [];
    }


    protected function _addException( \Exception $e ) {
        $this->exceptions[] = $e;
    }

    public function hasExceptions(): bool {
        return count( $this->exceptions ) > 0;
    }

    public function getExceptions(): array {
        return $this->exceptions;
    }
}