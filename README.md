# PVOutput
Implementation of PVOutput.org API. For more information, see http://www.pvoutput.org/help.html#api-spec

Methods currently implemented are:

+ [Add Output](http://www.pvoutput.org/help.html#api-addoutput)
+ [Get Output](http://www.pvoutput.org/help.html#api-getoutput)
+ [Add Status](http://www.pvoutput.org/help.html#api-addstatus)

This is a simple PHP class that can be used in a larger project to delegate communication with PVOutput. It is not a monitoring application.

Please note that not all API parameters are handled by this class methods. Parameters coverage is:
+ Add Output
 + `d`
 + `g`
 + `pp`
 + `pt`
 + `c`
+ Get Output
 + `df`
 + `dt`
 + `a`
 + `limit`
 + `tid`
 + `sid1`
+ Add Status
 + `d`
 + `t`
 + `v2`
 + `v4`

Missing parameters will be implemented in the future.

## Installation

### Composer

 From the Command Line:

```
composer require ebaschiera/pvoutput:1.1.*
```

In your `composer.json`:

``` json
{
    "require": {
        "ebaschiera/pvoutput": "1.1.*"
    }
}
```

### Manually
Just download the release package, put `src/PVOutput.php` inside your project tree and you are set.

## Basic usage
Please note that not all API method parameters are handled by this class.
```
//during the day
$pvoutput = new \PVOutput\PVOutput($system_id, $api_key);
$pvoutput->addStatus(2500, 1500); //sends instant generation and consumption


//at the end of the day
$pvoutput = new \PVOutput\PVOutput($system_id, $api_key);
$pvoutput->addOutput(NULL, 9000, 3100, $peak_output_datetime, 8000);
//sends generated energy, peak generation power, peak generation datetime, consumed energy


//get a full month aggregated output
$start_date = \DateTime::createFromFormat('Ymd', '20150401');
$end_date = \DateTime::createFromFormat('Ymd', '20150430');
$pvoutput = new \PVOutput\PVOutput($system_id, $api_key);
$pvoutput->getOutput($start_date, $end_date, 'm');
```

## License
This software is provided as is, according to GPL-2. See the `LICENSE` file for more information.
