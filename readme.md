# Fieldtype Measurement
This fieldtype allows a measurement unit to be stored with a corresponding measurement value ('magnitude'). 
The relevant details of the type of measurement are set on the Details tab of the field setup. 
The API permits various conversions and formatting.

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
(see later notes for the details on this).

On the details tab of the field setup page, you define the quantity to be measured and select which of the units defined in the config file are to be allowed as input choices. In addition you can choose:
* to hide the quantity text in the input field (quite likely as you will probably use a name for the field which provides similar information and will be the label for the fieldset);
* to show an 'update box' - if this is shown and checked before saving the page then, if the units have been changed, the magnitude of the measurement will be converted into the new units (see below).

## Usage on a page
Having added the field to a template, related pages then display a fieldset with:
* quantity (not editable here) unless it was set as hidden;
* magnitude;
* unit (dropdown);
* 'update' checkbox (if set to be shown).

If the update box is not checked, just set the magnitude and units as appropriate and save the page.

If the update box is checked and the units have been changed then the magnitude will automatically be converted on saving the page (unless the new or old units is blank), so in this case set the magnitude first in the old units, then change the units, then save the page. After saving, the update box will be unchecked.

When rendering the page, the normal formatted value will be the measurement value followed by the abbreviation ('shortLabel' in the config file). This format can be changed (see API section).

### Combination units
Some units are "combinations" - in other words, two or more units combined - e.g. feet and inches. These are defined using the pipe "|" join. When entering a value, it is necessary to enter the required number of values joined with a "|". Thus, for 2 feet 3 inches, enter "2|3" where the selected unit is "foot|inch". If the format of the magnitude is inconsistent with the chosen units an error will be thrown. If "update" is selected then the magnitude needs to be consistent with the previous unit chosen - conversion to the correct format for the new unit is automatic.

When rendering the page, the normal formatted value will be the first measurement value followed by the first abbreviation ('shortLabel' in the config file), then the second (after a space) etc. This format can be changed (see API section).

## Use in selectors
The field can be used in selectors in the usual way, e.g. if the field is 'temperature':
````
$pages->find("template=my_template, temperature.unit=Celsius, temperature.magnitude>20");
````
Note that no on-the-fly conversions are carried out here, so if you are using a mix of units, you may need to include them all (with 'or' conjunction).

## API
To use the API you need the **unformatted** field - i.e. either directly from 
````
$page->getUnformatted('my_measurement_field');
````
or by setting $page->of(false).
This is an object of class "Measurement" (which extends WireData).
The following methods are available for Measurement objects:
* *format(?array $options = [])*: Change the formatting used in subsequent rendering.
The default options are:
    ````
    $defaultOptions = [
        'label' => 'short',
        'decimals' => 2,
        'round' => true,
        'join' => [' '],
        'skipNil' => true
    ];
    ````
   'label =>'short' provides the abbreviations; for the long names (pluralised where appropriate), use 'long'. Use 'label' => 'none' to omit labels. If 'round' is false then the value will be truncated.
   'join' and 'skipNil' are only relevant for combination units - see below (note that 'join' is an array).

    For plurals which are not achieved by adding an 's', the plural is given in the config file.
 
    *Combination units*: The options above operate on each magnitude successively. The 'join' elements are used to append each magnitude/label group. E.g.
    ````
  $page->length->format(['label' => 'long', 'decimals' => 1, 'join' => [' and ']]);
  ````
  results in something like: '1 foot and 3.4 inches'. Note that the number of elements in the join array is one less than the number of elements in the combination unit - i.e. there is no 'join' string after the last element (any excess elements will be ignored and  a shortfall will just result in concatenation). The 'skipNil' option, if true, will cause any leading elements to be suppressed - so '1 inch' not '0 feet 1 inch'. The last element will always be displayed. 
    
* *render(?array $options = [])*: Render the measurement object. $options are as for format() above and will temporarily over-ride any previous setting by format().
* *valueAs(string $unit, ?int $decimals = null, ?bool $round = true)*: Returns the magnitude converted to the specified unit (or an error if the specified unit does not exist or is not compatible).
Rounds (or truncates) the value to the specified number of decimal places (if given).
* *valueAsAll(?int $decimals = null, ?bool $round = true)*: Returns an array of all conversion values for compatible units.
* *valueAsSelectable(?int $decimals = null, ?bool $round = true)*: Returns an array of all conversion values for selectable units.
* *valueAsMany(array $units, ?int $decimals = null, ?bool $round = true)*: Returns an array of all conversion values for units in the specified array.
* *convertFrom($value, ?string $unit = null])*: Sets the magnitude to the value, converting from the specified compatible unit (if given) to the current unit of the measurement object. This method updates the current object.
* *convertTo(string $unit, ?int $decimals = null, ?bool $round = true)*: Converts the object to one with the specified unit, carrying out the relevant conversion of the magnitude.
Note that if the specified unit is not in the selectable options list, then blank will be displayed as an option; changing the field setup details to include the relevant option will cause it to display. This method updates the current object.
* *add(Measurement $measurement2, ?string $unit = null)*: Add measurement2 to this measurement. The result is a new measurement object with the magnitude equal to the sum; the unit will be as specified by $unit, if present, otherwise it will be the unit of this measurement.
* *subtract(Measurement $measurement2, ?string $unit = null)*: Subtract measurement2 from this measurement. The result is a new measurement object with the magnitude equal to the difference; the unit will be as specified by $unit, if present, otherwise it will be the unit of this measurement.
* *getUnits(?string $unit = null)*: Get all the compatible units for $unit. If $unit is null, this is all the compatible units for the current unit of the measurement object.
Returns an array ```['unit name1' => 'unit name1', 'unit name2' => 'unit name2', etc...]```.
* *addUnit(string $unit, string $base, string $shortLabel, $conversion, ?string $plural)*: Add a new unit and conversion in memory. $base should be the compatible base unit. $shortLabel is the abbreviation.
The arguments match the format of the config files (see below for more details) but the new unit is only in memory - it is not added to the related file.
Returns true/false.
* *removeUnit(string $unit)*: Remove a unit (which had been added using addUnit) from memory.

This API can be used outside of the fieldtype context - just create a new Measurement object:
````
$measurement = new Measurement(?string $quantity = null, ?string $unit = null, $magnitude = null);
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
If you a defining a complex conversion for use in addUnit() then define the callback as a variable first then include it as the conversion argument.
E.g.
````
$fahrenheit = function($val, $tofrom){..etc..};
$measurement->addUnit("Fahrenheit", "Kelvin", "degF", $fahrenheit, "Fahrenheit");
````
### Combination units
Combination units (see definition above) need to be defined with a pipe join for each element, other than the conversion. The conversion will always be a callable - a simple multiplier is not appropriate. For example:
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
 