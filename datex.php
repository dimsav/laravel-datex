<?php

/*
|--------------------------------------------------------------------------
| Usage
|--------------------------------------------------------------------------
| // Todo: write examples
|
*/

class Datex
{
    // Todo: check this site for info: http://www.brightcherry.co.uk/scribbles/php-adding-and-subtracting-dates/

    /*
     * This is needed when the php format is default.
     */
    private static $formats = array(
        '.'   => 'eur', // DD.MM.YY
        '/'   => 'usa', // MM/DD/YY
        '-'   => 'iso', // YY-MM-DD
        'php' => 'php'
    );

    private static $default_format = 'php';

    public static $options = array();

    public static function date($output_format, $input, $input_format = null)
    {
        return date($output_format, strtotime(self::datetime($input, $input_format)));
    }

    public static function set_format($format)
    {
        if (in_array($format, self::$formats))
        {
            self::$default_format = $format;
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function get_format()
    {
        return self::$default_format;
    }

    /*
	|--------------------------------------------------------------------------
	| Converts the string to the MySQL datetime format (2001-03-10 17:16:18)
	|--------------------------------------------------------------------------
	|
	|
	*/
    public static function datetime($string = '1', $format = null)
    {
        if ( isset($format) )
        {
            self::set_format($format);
        }

        // If $string is timestamp, use it. (Hopefully you won't need to use
        // a timestamp before Mon, 12 Jan 1970 13:46:40)
        if ((string)(int)$string === (string)$string && (int)$string > 1000000)
        {
            return date('Y-m-d H:i:s', $string);
        }

        if (self::string_splitter($string) !== false)
        {
            list($date_string, $time_string) = self::string_splitter($string);

            $date_stamp = self::string_to_date_stamp($date_string);
            list($time_stamp, $adjustments) = self::string_to_time_stamp($time_string);

            if ($date_stamp === false || $time_stamp === false)
            {
                return false;
            }

            $newdate = strtotime("$date_stamp $time_stamp");

            $i = 1;
            foreach ($adjustments as $adjustment)
            {
                if ($i >= 1)
                {
                    $newdate =  strtotime($adjustment, $newdate);
                }
                $i++;
            }

            return date('Y-m-d H:i:s', $newdate);
        }

        return date('Y-m-d H:i:s', strtotime($string));
    }


    /*
	|--------------------------------------------------------------------------
	| Splits the input string to date and time parts
	|--------------------------------------------------------------------------
	|
	|
	|
	*/
    private static function string_splitter($string)
    {
        // convert multiple spaces to one and trim spaces
        $string = trim(preg_replace('!\s+!', ' ', $string));

        $string_parts = explode(' ', $string);

        // If no space is found in $string, we consider the $string to be a time
        if (count($string_parts) == 1)
        {
            $date_str = null;
            $time_str = $string_parts[0];
        }
        // If only one space is found in $sting, we determine both date and time
        elseif (count($string_parts) == 2)
        {
            $date_str = $string_parts[0];
            $time_str = $string_parts[1];
        }
        // if more than one spaces are found, then we return false
        else
        {
            return false;
        }

        return array($date_str, $time_str);
    }

    /*
	|--------------------------------------------------------------------------
	| Converts the mixed input string to date
	|--------------------------------------------------------------------------
	| Input can be:
    |      1) (:num)               - date number
    |      2) (:num).(:num)        - date number, separator, month number
    |      3) (:num).(:num).(:num) - date number, month number, year, separators
    |
    |  You don't have to use dot "." as separator, check the string_to_date_array() function to see your options!
	|
	*/
    private static function string_to_date_stamp($date_str)
    {
        $today_day   = date('j');
        $today_month = date('n');
        $today_year  = date('Y');

        list ($input_day, $input_month, $input_year) = self::string_to_date_array($date_str);

        // If we give only one number (the day number)
        if ( !isset($input_month) )
        {
            if ($input_day < $today_day)
            {
                $stamp_date = $today_year .'-'. $today_month .'-'. $input_day;
                $unix_date = strtotime ( $stamp_date );
            }
            else
            {
                $difference = $input_day - $today_day;
                $stamp_date = $today_year .'-'. $today_month .'-'. $today_day;
                $unix_date = strtotime ( "+$difference day" , strtotime ( $stamp_date ) ) ;
            }

            if ( $unix_date === false ) return false;

            return date("Y-m-d", $unix_date);
        }
        // If we give day and month number
        elseif ( !isset($input_year) )
        {
            $unix_date = strtotime ( "$today_year-$input_month-$input_day" );
            if ( $unix_date === false ) return false;
            return date("Y-m-d", $unix_date);
        }
        else
        {
            $unix_date = strtotime ( "$input_year-$input_month-$input_day" );
            if ( $unix_date === false ) return false;
            return date("Y-m-d", $unix_date);
        }
    }


    /*
	|---------------------------------------------------------------------------------
	| Tries to determine the number of the date, month and year given the input string.
    | Helper function of string_to_date_stamp()
	|---------------------------------------------------------------------------------
	| Examples: 'input' => [ day, month, year ]
    |
    |   Default: EUR
    |       '1'     =>  [1, null, null]
	|       '1.2'   =>  [1, 2, null]
	|       '1.2.3' =>  [1, 2, 3]
	|       '1/2/3' =>  [1, 2, 3] -> with EUR default, symbol doesn't matter
	|
    |   Default: USA
	|       '1'     =>  [1, null, null]
	|       '1/2'   =>  [2, 1, null]
	|       '1/2/3' =>  [2, 1, 3]
	|       '1-2-3' =>  [2, 1, 3] -> with USA default, symbol doesn't matter
    |
	|   Default: ISO
	|       '1'     =>  [1, null, null]
	|       '1-2'   =>  [2, 1, null]
	|       '1-2-3' =>  [3, 2, 1]
	|       '1_2_3' =>  [2, 1, 3] -> with ISO default, symbol doesn't matter
    |
	|   Default: PHP (The first symbol determines the format)
	|       '1'     =>  [1, null, null]
    |
	|       '1.2'   =>  EUR format =>  [1, 2, null]
	|       '1.2.3' =>  EUR format =>  [1, 2, 3]
    |
    |       '1/2'   =>  USA format =>  [2, 1, null]
	|       '1/2/3' =>  USA format =>  [2, 1, 3]
    |
	|       '1-2'   =>  ISO format =>  [2, 1, null]
	|       '1-2-3' =>  ISO format =>  [3, 2, 1]
    |
	|       '1_2_3' =>  format could not be determined => EUR format =>  [1, 2, 2]
	|
	*/
    private static function string_to_date_array($string)
    {

        // Get digit characters
        preg_match_all('!([0-9]+)(\D)*!', $string, $temp_array);

        // $temp_array[0] contains all the matches
        // $temp_array[1] contains digits
        // $temp_array[2] contains the separators

        // If default_format is 'php', the separator will determine the current format
        $separator = isset($temp_array[2][0]) ? $temp_array[2][0] : '';
        $current_format = self::get_custom_format($separator);

        $input_day = date('j');
        $input_month = null;
        $input_year  = null;

        $count = count($temp_array[1]);

        if ( $count == 1 )
        {
            $input_day   = $temp_array[1][0];
        }
        elseif ($count >= 2)
        {
            if ($current_format == 'eur')
            {
                $input_day   = $temp_array[1][0];
                $input_month = $temp_array[1][1];
                $input_year  = isset($temp_array[1][2]) ? $temp_array[1][2] : null;
            }
            elseif ( $current_format == 'usa' )
            {
                if ( $count == 2 )
                {
                    $input_day   = $temp_array[1][1];
                    $input_month = $temp_array[1][0];
                }
                else // $count >= 3
                {
                    $input_day   = $temp_array[1][1];
                    $input_month = $temp_array[1][0];
                    $input_year  = $temp_array[1][2];
                }
            }
            else // iso
            {
                if ( $count == 2 )
                {
                    $input_day   = $temp_array[1][1];
                    $input_month = $temp_array[1][0];
                }
                else // $count >=3
                {
                    $input_day   = $temp_array[1][2];
                    $input_month = $temp_array[1][1];
                    $input_year  = $temp_array[1][0];
                }
            }

        }
        return array($input_day, $input_month, $input_year);
    }


    private static function string_to_time_stamp($string)
    {
        $input_minutes = $input_seconds = '00';
        $adjustments = array();

        preg_match_all('!([0-9]+)(\D)*!', $string, $split_array);

        $count = count($split_array[0]);

        if ( $count < 1  || $count > 3)
            return false;

        if ( $count == 3 )
        {
            $input_seconds = $split_array[1][2];
            if ($input_seconds >= 60)
            {
                $adjustments[] = "+$input_seconds seconds";
                $input_seconds = 0;
            }
            $input_seconds = str_pad($input_seconds, 2, '0', STR_PAD_LEFT);
        }
        if ( $count >= 2 )
        {
            $input_minutes = $split_array[1][1];
            if ($input_minutes >= 60)
            {
                $adjustments[] = "+$input_minutes minutes";
                $input_minutes = 0;
            }
            $input_minutes = str_pad($input_minutes, 2, '0', STR_PAD_LEFT);
        }
        if ( $count >= 1 )
        {
            $input_hour = $split_array[1][0];
            if ($input_hour >= 24)
            {
                $adjustments[] = "+$input_hour hours";
                $input_hour = 0;
            }
            $input_hour = str_pad($input_hour, 2, '0', STR_PAD_LEFT);
        }

        return array("$input_hour:$input_minutes:$input_seconds", $adjustments);
    }


    /*
	|---------------------------------------------------------------------------------
	| Returns the date format depending on the $default_format and the input character
	|---------------------------------------------------------------------------------
	| If the default format is 'eur' or 'usa', the default format is returned.
    | If the default format is 'php', the $separator is used to determine which format will be used. The private static
	| table $formats defines the relationship between separators and formats.
	|
	*/
    private static function get_custom_format($separator = '')
    {
        if ( self::$default_format == 'php' )
        {
            // Validate $separator
            if ( strlen($separator) > 0 )
            {
                // To maximize input tolerance use the first character
                $separator = substr($separator, 0, 1);

                if ( isset(self::$formats[$separator]) )
                {
                    return self::$formats[$separator];
                }
            }

            return 'eur'; // If could not be determined, return 'eur' (sorry us people)
        }
        else
        {
            return self::$default_format;
        }
    }

}