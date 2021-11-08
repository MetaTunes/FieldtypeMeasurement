<?php

namespace MeasurementCurrency {
/*
 * Real time currency rates - replace the function below with the API of your choice
 * This one uses Alpha Vantage (https://www.alphavantage.co/) which has a free service rate limited to 5 per minute and 500 per day
 */

	use ProcessWire\MeasurementException;
	use ProcessWire\ProcessWire;

	if(!function_exists(__NAMESPACE__ . '\currencyConverter')) {
		function currencyConverter($currency_from,$currency_to,$currency_input){
			// Use recent details to avoid 5 per minute lockout
			$sessionVar = \ProcessWire\wire()->session->get('MeasurementCurrency');
			//bd($sessionVar, 'sessionvar');
			if($sessionVar and isset($sessionVar[$currency_from][$currency_to])) {
				$savedDetails = $sessionVar[$currency_from][$currency_to];
				if($savedDetails['time'] > time() - 300) {      // 5 minute timeout
					//bd($savedDetails, 'USING SAVED DATA');
					$rate = (is_array($savedDetails['rate'])) ? $savedDetails['rate'][count($savedDetails['rate']) - 1] : $savedDetails['rate']; // use the latest rate
					return $currency_input * $rate;
				}
			}
			$apiKey = \ProcessWire\wire()->config->alphaVantageApiKey; // Get your API key from https://www.alphavantage.co/ and put it in your config file
			$json = file_get_contents("https://www.alphavantage.co/query?function=CURRENCY_EXCHANGE_RATE&from_currency=$currency_from&to_currency=$currency_to&apikey=$apiKey");

			$data = json_decode($json,true);
			//bd($data, 'REAL TIME DATA');
			if(isset($data['Realtime Currency Exchange Rate']['5. Exchange Rate'])) {
				$rate = (float) $data['Realtime Currency Exchange Rate']['5. Exchange Rate'];
			} else {
				throw new MeasurementException(\ProcessWire\__("Bad API return (maybe excess calls?)"));
			}
			$newVar = array_merge_recursive($sessionVar, [$currency_from => [$currency_to => ['time' => time(), 'rate' => $rate]]]);
			//bd($newVar, 'new var');
			\ProcessWire\wire()->session->set('MeasurementCurrency', $newVar);
			return $currency_input * $rate;
		}
	}
	} // End of MeasurementCurrency namespace

	namespace ProcessWire {
return array(
///////Units Of Currency///////
/// This uses the (almost) real-time converter function above
/// For real-time conversion to work use correct currency codes - see http://en.wikipedia.org/wiki/ISO_4217",
/// If you want long names which are not currency codes, add "alias" => "United States dollar" for example to the array and change the plural
/// Note that rates may have small timing or other differences which may build with repeated to and from conversions

	"notes" => "The base currency is US dollars (USD). ISO codes are used for all unit names. Currencies are converted using an (almost) real-time feed from Alpha Vantage. Note that rates may have small timing or other differences which may build with repeated to and from conversions.",
	"base" => "USD",
	"units" => array(
		"USD" => array("shortLabel" => "$", "position" => "prepend", "plural" => "USD", "conversion" => 1),
		"GBP" => array("shortLabel" => "£", "position" => "prepend", "plural" => "GBP", "conversion" => function($val, $toFrom) {
			return ($toFrom) ? \MeasurementCurrency\currencyConverter('USD', 'GBP', $val) :
				\MeasurementCurrency\currencyConverter('GBP', 'USD', $val);
		},),
		"EUR" => array("shortLabel" => "€", "position" => "prepend", "plural" => "EUR", "conversion" => function($val, $toFrom) {
			return ($toFrom) ? \MeasurementCurrency\currencyConverter('USD', 'EUR', $val) :
				\MeasurementCurrency\currencyConverter('EUR', 'USD', $val);
		},),
	)
);
}