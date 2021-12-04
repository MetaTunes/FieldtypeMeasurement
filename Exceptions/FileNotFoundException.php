<?php namespace MetaTunes\MeasurementClasses;
use ProcessWire\WireException;
use \Throwable;

class FileNotFoundException extends WireException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}