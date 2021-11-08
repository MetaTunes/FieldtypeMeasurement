<?php namespace ProcessWire;

use \Throwable;

class MeasurementException extends WireException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}