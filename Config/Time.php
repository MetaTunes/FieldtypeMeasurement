<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;

return array(
///////Units Of Time///////
	"dimension" => new Dimension([Dimension::TIME => 1]),
	'notes' => __("The base unit for time is the second. Units which are shown as [Date] are measured with the base being seconds since 1/1/1970 (ie.Unix timestamp)."),
	'base' => 'second',
	'units' => array(
		"second" => array("shortLabel" => "s", "conversion" => 1),
		"year" => array("shortLabel" => "yr", "conversion" => 31536000),
		"month" => array("shortLabel" => "mon", "conversion" => 2628000),
		"week" => array("shortLabel" => "wk", "conversion" =>  604800),
		"fortnight" => array("shortLabel" => "Fortn", "conversion" =>  1209600),
		"day" => array("shortLabel" => "d", "conversion" => 86400),
		"hour" => array("shortLabel" => "h", "conversion" => 3600),
		"minute" => array("shortLabel" => "m", "conversion" => 60),
		"milliscond" => array("shortLabel" => "ms", "conversion" =>  0.001),
		"microsecond" => array("shortLabel" => "μs", "conversion" =>  0.000001),
		"nanosecond" => array("shortLabel" => "ns", "conversion" => 0.000000001),
		"[Date] yyyy|mm|dd" => array(
			"shortLabel" => "",
			"join" => ['/', '/'],
			"conversion" => function($val, $tofrom) {
				if($tofrom) {
					$date = date("Y-m-d", $val);
					$date = explode('-', $date);
					return $date;
				} else {
					$date = implode('-', $val);
					return strtotime($date);
				}
			}
		),
		"[Date] dd|mm|yyyy" => array(
			"shortLabel" => "",
			"join" => ['/', '/'],
			"conversion" => function($val, $tofrom) {
				if($tofrom) {
					$date = date("Y-m-d", $val);
					$date = explode('-', $date);
					return array_reverse($date);
				} else {
					$date = implode('-', array_reverse($val));
					return strtotime($date);
				}
			}
		),
		"[Date] mm|dd|yyyy" => array(
			"shortLabel" => "",
			"join" => ['/', '/'],
			"conversion" => function($val, $tofrom) {
				if($tofrom) {
					$date = date("Y-m-d", $val);
					$date = explode('-', $date);
					return [$date[1], $date[2], $date[0]];
				} else {
					$date = implode('-', [$val[2], $val[0], $val[1]]);
					return strtotime($date);
				}
			}
		),
		"[Date] yyyy|mm|dd|hh|mm|ss" => array(
			"shortLabel" => "",
			"join" => ['/', '/', ' ', ':', ':'],
			"conversion" => function($val, $tofrom) {
				if($tofrom) {
					$time = date("Y-m-d H:i:s", $val);
					$time = str_replace(' ', '-', $time);
					$time = str_replace(':', '-', $time);
					$time = explode('-', $time);
					return $time;
				} else {
					$date = implode('-', [$val[0], $val[1], $val[2]]);
					$time = implode(':', [$val[3], $val[4], $val[5]]);
					return strtotime($date . ' ' . $time);
				}
			}
		),

	)
);
