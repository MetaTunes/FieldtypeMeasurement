# Fieldtype Measurement
This fieldtype allows a measurement unit to be stored along with a corresponding measurement value ('magnitude'). 
The relevant details for the type of measurement are set in the Details tab of the field setup page. 
The API permits various conversions and formatting.

**Please note that this module is 'alpha' at the moment - it has all the planned functionality and has been tested in PW3.0.148 - 203. However, different PW versions, modules and your own code may affect it differently. It is therefore not recommended for use in production sites unless you have fully tested it in context first.**

## Installation

FieldtypeMeasurement can be installed like every other module in ProcessWire. Check the following guide for detailed information: [How-To Install or Uninstall Modules](http://modules.processwire.com/install-uninstall/)

It requires **ProcessWire version >=3.0.148**. This is checked during the installation of the module. You may also wish to install [RockCalculator](https://processwire.com/modules/rock-calculator/). This is not required but, if installed, will automatically apply to measurement fields without further set-up (for use in other fields refer to the documentation with that module).

## Set up
The various units of measurement are defined in the module FieldtypeMeasurement/Config directory. 
There is a separate file for each type of quantity - e.g. "Length", "Area". 
Each file contains an array defining the applicable units of measure for this quantity, in the following format (for example in "Length.php"):

 ````
["base" => "metre",
	"units" => [
	    "metre" => ["shortLabel" => "m", "conversion" => 1],
		"foot" => ["shortLabel" => "ft", "conversion" => 0.3048, "plural" => "feet"],
		**etc**,
	]
]
 ````
(This is a simplified example - see later notes for the details).

On the details tab of the field setup page, you define the quantity to be measured (i.e. the name of one of the config files) and select which of the units defined in the config file are to be allowed as input choices. In addition you can choose:
* to hide the quantity and notes text in the input field;
* to enable the magnitude value to be automatically updated when you change the unit selection in a field - the options are to 'Always' convert, to 'Never' convert or to 'Ask' whether or not to convert each time the unit selection is changed (note that, if conversion is chosen and the unit is changed to no selection then the magnitude value will be blanked);
* to hide the magnitude box - this might be, for example, if you want to use the field just to choose a default unit (obviously if this is chosen then the conversion option is irrelevant);
* to show a 'remark' box - if selected, the contents of any box will be rendered, by default, as a tooltip, indicated by a dotted line under the rendered value in the front end.

For some demo videos, please see [this post](https://processwire.com/talk/topic/26241-fieldtypemeasurement/?do=findComment&comment=223287).

## Usage on a page
Having added the field to a template, related pages then display a fieldset with:
* quantity and notes (not editable here) unless this was set as hidden;
* magnitude (unless you chose to hide it);
* unit (dropdown);
* 'remark' box (if set to be shown).

When rendering the page, the normal formatted value will be the measurement value followed by the abbreviation ('shortLabel' in the config file). This format can be changed (see API section). If there is a 'remark' it will be shown as a tooltip unless a different formatting option is chosen. 

If no unit is selected, the measurement will not be rendered (but any remark will still be shown).

### Combination units
Some units are "combinations" - in other words, two or more units combined - e.g. feet and inches. These are defined using the pipe "|" join. When entering a value, it is necessary to enter the required number of values joined with a "|". Thus, for 2 feet 3 inches, enter "2|3" where the selected unit is "foot|inch". If the format of the magnitude is inconsistent with the chosen units an error will be thrown. If the unit is changed, conversion to the correct format for the new unit is automatic.

When rendering the page, the normal formatted value will be the first measurement value followed by the first abbreviation ('shortLabel' in the config file), then the second (after a space) etc. This format can be changed (see API section).

### Use with RockCalculator

If you have installed the RockCalculator module, then that will be available in all magnitude input fields except for those for combination units.

## Use in selectors
The field can be used in selectors in the usual way, e.g. if the field is 'temperature':
````
$pages->find("template=my_template, temperature.unit=Celsius, temperature.magnitude>20");
````
Note that temperature.magnitude is the value in whatever unit is currently selected for the field on each page - which is why temperature.unit has been specified, in the above example, to ensure a consistent search. You can select regardless of the units by using .baseMagnitude because the database stores the magnitude in base unit amounts as well as in current unit amounts, so: 

````$pages->find("template=my_template, temperature.baseMagnitude>293");````

will select all pages where the temperature exceeds 293 Kelvin, regardless of what units are used to record it on the page.

**Important**: If magnitude is composite (e.g. 1|10 representing, say, 1 ft 10 inches) then the comparison will treat the pipe join as if it was a decimal point, so it will think that 1|10 < 1|2, which is wrong. Therefore, if using > or < in the comparison and there is a risk of combination units existing where the second (or subsequent) portion may be greater than 9, use baseMagnitude, not magnitude.

## API
To use the API you need the **unformatted** field - i.e. either directly from 
````
$page->getUnformatted('my_measurement_field');
````
or by setting $page->of(false). Note that, if using getUnformatted(), the argument needs to be the field name (string), not the field object.

The unformatted field is an object of class "Measurement" (which extends WireData). The main methods of this class are described below.

### Measurement methods
The following methods are available for Measurement objects. (Note that, if you have the ProcessWireAPI module, you can access these very readily there).
* *format(?array $options = [])*: Change the formatting used in subsequent rendering.
  The default options are:
  
    ````
        $defaultOptions = [
            'label' => 'short', // 'short', 'shortPadded' (with space to separate from magnitude), 'long', 'none'
            'position' => 'append', // 'append' - after the magnitude, 'prepend' - before the magnitude (only applies to shortLabels.)
            'decimals' => null, // positive integer number of places
            'round' => true, // otherwise value will be truncated
            'join' => [' '], // an array for joining characters for combi units (one less element than the number of units in the combi) - e.g. [' and ']
            'skipNil' => true, // for combi units, do not render nil amounts, other than the last
            'alias' => $unit,
            'notes' => null,
            'plural' => null,
				'remarks' => 'tooltip' // how to display any contents of the 'remark' box: as 'tooltip', 'before' the value, 'after' the value, or 'none' (do not display)
        ];
    ````
   'label =>'short' provides the abbreviations; for the long names (pluralised where appropriate), use 'long'. 'position' determines the location of the shortLabel (before or after the magnitude). Long names will always be after the magnitude and preceded by a space. Use 'label' => 'none' to omit labels. If 'round' is false then the value will be truncated.
   'join' and 'skipNil' are only relevant for combination units - see below (note that 'join' is an array).
  
    For plurals which are not achieved by adding an 's', the plural is given in the config file. Other options may be specified in the config file, in which case they will override the general defaults (but will in turn be overridden by any format($options)).
  
    *Combination units*: The options above operate on each magnitude component successively. The 'join' elements are used to append each magnitude/label group. E.g.
    ````
  $page->length->format(['label' => 'long', 'decimals' => 1, 'join' => [' and ']]);
    ````
  results in something like: '1 foot and 3.4 inches'. Note that the number of elements in the join array is one less than the number of elements in the combination unit - i.e. there is no 'join' string after the last element (any excess elements will be ignored and  a shortfall will just result in concatenation). The 'skipNil' option, if true, will cause any leading elements to be suppressed - so '1 inch' not '0 feet 1 inch'. The last element will always be displayed. 
  
* *render(?array $options = [])*: Render the measurement using default or specified options. $options are as for format() above and will temporarily over-ride any previous setting by format(). This method is hookable, so you can replace it completely with your own method, if required. 

* *valueAs(string $unit, ?int $decimals = null, ?bool $round = true)*: Returns the magnitude converted to the specified unit (or an error if the specified unit does not exist or is not compatible).
  Rounds (or truncates) the value to the specified number of decimal places (if given).

* *valueAsBase(?int $decimals = null, ?bool $round = true)*: As for valueAs() with $unit = the base unit.

* *valueAsAll(?int $decimals = null, ?bool $round = true)*: Returns an array of all conversion values for compatible units.

* *valueAsMany(array $units, ?int $decimals = null, ?bool $round = true)*: Returns an array of all conversion values for units in the specified array.

* *convertFrom($value, ?string $unit = null])*: Set the measurement from the given value & unit. If $value is a number: sets the magnitude to the value, converting from the specified compatible unit (if given) to the current unit of the measurement object. If $value is a Measurement object: converts the $value measurement to the units of the current object. This method updates the current object.

* *convertFromBase($value)*: As for convertFrom() with $unit = the base unit.

* *valueFromBase($value, $unit)*: Given a base unit magnitude $value, return the magnitude in the given $unit. If $unit is a combination unit, the result will be an array

* *convertTo(string $unit, ?int $decimals = null, ?bool $round = true)*: Converts the object to one with the specified unit, carrying out the relevant conversion of the magnitude.
  Note that if the specified unit is not in the selectable options list, then blank will be displayed as an option; changing the field setup details to include the relevant option will cause it to display. This method updates the current object.

* *convertToBase(?int $decimals = null, ?bool $round = true)*: As for convertTo() with $unit = the base unit.

* *add(Measurement $measurement, ?string $unit = null)*: Add $measurement to this measurement. The result is in the units of this measurement unless $unit is specified ($measurement will be converted as appropriate). Returns a new Measurement object.

* *sumOf(...$measurements)*: Adds the measurements. Updates the current object which must be of the same quantity as the measurements to be summed. Typically set ````$m = new Measurement($quantity);```` and then ````$m->sumOf(...);````

* *subtract(Measurement $measurement, ?string $unit = null)*: Subtract $measurement from this measurement. The result is in the units of this measurement unless $unit is specified ($measurement will be converted as appropriate). Returns a new Measurement object.

* *multiplyBy($multiplier, ?string $quantity = null, ?string $unit = null)*: If $multiplier is a number then the measurement will simply be scaled. If $multiplier is a Measurement object then the result will be computed using dimensional analysis (both the current object and $measurement must be of quantities that have dimensions defined). If $quantity and $unit are not defined then they will be inferred as far as possible, otherwise they will be checked for consistency and the result will be returned as specified.

* *baseMultiplyBy($multiplier, ?string $quantity = null, ?string $unit = null)*: Method to use instead of multiplyBy(), where it is expected that the result of multiplyBy() would be an unknown quantity. This method anticipates that the result will be a BaseMeasurement and avoids unnecessary warnings

* *productOf(...$measurements)*: Multiplies the measurements using dimensional analysis (see *multiplyBy()* ) and updates the current object, which must have a quantity, dimension and units consistent with the intended product.

* *negate()*: Multiply by -1.

* *power(int $exp, ?string $quantity = null, ?string $unit = null)*: Raise the measurement to the given power (any real number).  If the result is has a dimension not matching any quantity, it returns a BaseMeasurement (dimensionless of magnitude 1 if $exp = 0).

* *divideBy($divisor, ?string $quantity = null, ?string $unit = null)*: Analogous to multiplyBy() above.

* *baseDivideBy($divisor, ?string $quantity = null, ?string $unit = null)*: Method to use instead of divideBy(), where it is expected that the result of divideBy() would be an unknown quantity. This method anticipates that the result will be a BaseMeasurement and avoids unnecessary warnings

* *invert(?string $quantity = null, ?string $unit = null)*: Raise to the power -1. See *power()*.

* *getConversions(?string $unit = null)*: Get all the compatible units for $unit - i.e. those which it can be converted to/from. If $unit is null, this is all the compatible units for the current unit of the measurement object.
  Returns an array ```['unit name1' => 'unit name1', 'unit name2' => 'unit name2', etc...]```.

* *addUnit(string $unit, array $params, ?string $selectableIn = null, ?string $template = null)*: Add a new unit (compatible with the current one - i.e. measuring the same quantity) and conversion in memory. $params should be an array in the same format as the definition of a unit in the config file (see below - but do NOT use anonymous functions). If you supply a field name (of FieldtypeMeasurement) as $selectableIn, then the new unit will be a selectable unit in that field (and if a template name is supplied in $template then the selectable unit will only be included in that template context).
  If the unit already exists, then the existing parameters will be removed and replaced by the specified conversion and options. To amend a unit without specifying all parameters, use amendUnit().
  Returns true/false.
  
* *amendUnit(string $unit, $conversion, array $options = [])* Amend a unit definition in memory (which was added using addUnit() ). $conversion can be just a multiplier number or a callable function - see config files below for more details $options is the same format as in format(). Note that the amendment creates a temporary new unit which overrides the existing one - it is not added to the related file. To revert this amendment, you need to use removeUnit(). Returns true/false.

* *removeUnit(string $unit)*: Remove a temporary unit - which had been added using addUnit() or amended using amendUnit() - from the session.

* *getUnits()*: Returns the units which are compatible (from the config file) as an array.

### Other functions
* *FieldtypeMeasurement::addSelectableUnit(string $field, string $unit, ?string $template = null)* Adds the specified unit (if compatible) to the selectable units for the field (in the context of $template, if given).
* *FieldtypeMeasurement::removeSelectableUnit(string $field, string $unit, ?string $template = null)* Removes the specified unit (if it exists) from the selectable units for the field (in the context of $template, if given).

### Dimensions

If the relevant config file defines a dimension (see below) then a Measurement object will have a dimension attribute. This is an object (of class Dimension) with one data item : dimensionArray.

Each SI base unit (of which there are seven) is associated with a text key as follows:

```
	const TIME = 'time';
	const LENGTH = 'length';
	const MASS = 'mass';
	const CURRENT = 'current';
	const TEMPERATURE = 'temperature';
	const SUBSTANCE_AMOUNT = 'substance_amount';
	const LUMINOSITY = 'luminosity';
```

The dimension of any SI derived unit is an array where the values for each key is the exponent of the relevant base dimension. So, for example, acceleration has a dimensionArray ``['length' => 1, 'time' => -2]``. Each quantity that has an SI base unit or SI derived unit  as its base unit can therefore be associated with such an object. This enables dimensional analysis to be carried out on such quantities when (for example) multiplying and dividing them. However, quantities which do not have a base or derived SI unit as their base unit cannot be given a dimension. The config files include some SI base quantities and some SI derived quantities, but not all of them. It is therefore quite possible, for example, to construct a *Measurement::combineMeasurements()* which results in a SI derived quantity for which there is no config file (in which case a 'BaseMeasurement' object is returned).

It is (technically) possible for users to extend the dimensions by artificially adding new ones, providing they are represented by unique text keys, but the user is then responsible for the meaningfulness and consistency of the result.

### Use of Measurement API outside page context

This API can be used outside of the pagecontext - just create a new Measurement object:

````
$m = $modules->get('FieldtypeMeasurement');
$measurement = $m->measurement(?string $quantity = null, ?string $unit = null, $magnitude = null);
````

The arguments may be null and set later, but errors may occur if using methods for objects without all properties set. Use set and get thus:

````
$measurement->set('quantity', 'Area');
$measurement->get('quantity');
````

For combination units, the magnitude must be an array or a string of numbers with pipe joins, e.g. '2|3.5'

## Config files

There is a file for each quantity - e.g. "Area.php" - in the module 'Config' directory. These can be modified but may be overwritten at the next module update.
Therefore, if you wish to modify a file (or indeed create a new one for a new quantity), it is better to make a copy and place it in "your_site/templates/" in a directory named "Measurement", then modify that.
E.g. ````your_site/templates/Measurement/Area.php````. The module will then use that in preference to any similarly-named file in the module Config directory.

As described above, the basic format is:
 ````
["base" => "metre",
	"units" => [
	    "metre" => ["shortLabel" => "m", "conversion" => 1],
		"foot" => ["shortLabel" => "ft", "conversion" => 0.3048, "plural" => "feet"],
		**etc**,
	]
]
 ````
The plural element is optional. If omitted, the plural format will be the unit name followed by an 's'.

If the conversion is a simple multiplier/divisor then a single number can be used to express the unit in terms of base units. 
E.g. foot = 0.3048 metre.

If it is more complex then a callback can be used. E.g. add Fahrenheit to the Temperature file:
````
"Fahrenheit" => [
    "shortLabel" => "degF", 
    "conversion" => function($val, $tofrom){
    //$val - value to convert
    //$tofrom - whether it is being converted to or from this unit 
    //  (true - $val is the magnitude in base units; false - $val is the magnitude in this unit)
    return $tofrom ? ($val * 9/5 - 459.67) : (($val + 459.67) * 5/9);
    }, 
    "plural" => "Fahrenheit"]
````
If you are defining a complex conversion for use in addUnit() then define the callback as a variable first then include it as the conversion argument. (This is because you cannot store anonymous functions in session variables.)
E.g.

````
$fahrenheit = function($val, $tofrom){..etc..};
$measurement->addUnit("Fahrenheit", "Kelvin", "degF", $fahrenheit, "Fahrenheit");
````

As mentioned in the API section for format(), specific format options may be included in the config file which will override the normal default options. For example:
````
"mach" => array("shortLabel" => "mach", "conversion" => 340.29, "plural" => "mach", "position" => "prepend", "label" => "shortPadded"),
````
is used to achieve a formatting of 'mach 1.5'.

### Combination units
Combination units (see definition above) need to be defined with a pipe join for each label-type element. The conversion will always be a callable - a simple multiplier is not appropriate. For example:
````
"foot|inch" => array(            // pipe join is required
    "shortLabel" => "ft|in",
    "conversion" => function($val, $tofrom) {
        // value is an array for combi-type units
        if($tofrom) {
            // $val is the base unit magnitude - so return an array
            $convert = $val / .3048;
            $ft = intval($convert);
            $in = ($convert - $ft) * 12;
            return [$ft, $in];
        } else {
            // $val is an array [ft, in] - so return a single value for the base unit
            $ft = $val[0] + ($val[1] / 12);
            return $ft * 0.3048;
        }
    },
    "plural" => "feet|inches"
)
````
### Dimensions

Config files for quantities which have SI base or SI derived units as their base units may have dimensions assigned (see discussion above). For example, for the Density quantity:

```
"dimension" => new Dimension([Dimension::MASS => 1, Dimension::LENGTH => -3]),
```

(Note that using class constants rather than text keys allows your IDE to hint/check).

### Other features of config files

An element "alias" can be included to provide an alias to the key name for the unit. If present, it will be rendered in the front and back end instead of the key name. This alias can be made multi-language capable by enclosing the text thus: ````"alias" => __("an alias")````. Any text element can be made multi-language ready in this manner, but it is unwise to make the keys translatable as this can cause errors.

Additional elements "notes" can be added to describe aspects of the units and conversions in the file. If present, they will appear as markup on the 'details' tab of the field. Notes can be defined at the top level (i.e. the same level as "base" and "units") and/or within each unit array.

Functions may be defined outside the array. These can then be used more than once in the conversion definitions. It is a good idea to put them in a different namespace which references the quantity name. (Note that all the measurement classes and config files are outside the ProcessWire namespace anyway, to avoid potential clashes - use this same namespace for all new config files).

See Config/SpecificGravity.php and Config/Currency.php for examples of all these features.

### Currencies

An (almost) real time currency converter is included as Config/Currency.php. Please not that this is proof of concept at present - do not use for real financial transactions. It is intended as an example of how to add such a feature. The example uses Alpha Vantage (https://www.alphavantage.co/) which provides free API keys with usage constraints - you will need to get a key to use it. Once you have your key, put it in your config.php file thus: ````$config->alphaVantageApiKey = 'yourkey';````.

## Hooks

Various methods within the FieldtypeMeasurement class are hookable (inspect code to see what exactly). In particular, an optional argument has been introduced to ___wakeupValue:  argument[3] - $warnNull, if set to true will suppress the warning provided if the measurement has a null unit (default is false). Measurements may deliberately make use of null units to designate themselves as calculated fields (i.e. completed by API at run time, not by entered and stored data).

 # Changelog
 * 0.0.21 bug fix Measurement.php
 * 0.0.20 bug fix Mass.php
 * 0.0.19 new Measurement methods baseMultiplyBy() & baseDivideBy() - see API details above
 * 0.0.18 allow optional suppression of null units warning on wakeup
 * 0.0.17 allow use of RockCalculator, if installed
 * 0.0.16 bug fixes
 * 0.0.15 improved operation inside repeater matrix items
 * 0.0.14 bug fixes
 * 0.0.13 minor enhancements & bug fixes
 * 0.0.12 interactive dependent select in config, bug fixes
 * 0.0.11 bug fixes to in-field conversion
 * 0.0.10 bug fixes and enhancements to in-field conversion
 * 0.0.9 changed in-field conversion method to use htmx rather than forcing save, plus numerous bug fixes
 * 0.0.8 added 'remark' box to be rendered as tooltip, if present
 * 0.0.7 new namespaces, refactoring and extended dimensions
 * 0.0.6 minor fixes and new Measurement methods
 * 0.0.5 allowed specific formats in config file, additional units, dimensional analysis supported
 * 0.0.4 altered add() and subtract() methods and added related static functions
 * 0.0.3 revised db schema to hold base unit value
 * 0.0.2 additional formatting options