<?php
/*
 *    SimpleDate 0.1: Basic date functions.
 *    Copyright (C) 2014 Jon Stockton
 * 
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(SIMPLESITE!=1)
	die("Can't access this file directly.");

class SimpleDate
{
	public static function getDOW($day, $month, $year)
	{
		$months=array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		if(is_numeric($month)) {
			$m=(int)$month;
			if($m<=12 && $m>0) {
				$month=$months[$m-1];
			} else {
				return false;
			}
		} else if(!in_array($month, $months)) {
			return false;
		}
		$dateString="${day} ${month} ${year}";
		$timestamp=strtotime($dateString);
		return date("l", $timestamp);
	}

	public static function getDMY($timestamp)
	{
	}

	public static function timestamp()
	{
		return time();
	}

	public static function getObject($passTime=true, $timezone="GMT")
	{
		return new SimpleDateObject((($passTime)?time():null), $timezone);
	}

	public static function mil2AMPM($milTime)
	{
	}

	public static function mil2Timestamp($milTime)
	{
	}

	public static function isDST($timestamp)
	{
		/*
		public bool IsDST(int day, int month, int dow)
    		{
        		//January, february, and december are out.
      		  	if (month < 3 || month > 11) { return false; }

      		  	//April to October are in
      		  	if (month > 3 && month < 11) { return true; }
        		int previousSunday = day - dow;

        		//In march, we are DST if our previous sunday was on or after the 8th.
       		 	if (month == 3) { return previousSunday >= 8; }

       		 	//In november we must be before the first sunday to be dst.

      		  	//That means the previous sunday must be before the 1st.
      		  	return previousSunday <= 0;
    		}
	 	
			If that code really does agree with TimeZone.IsDayLightSavingsTime() for all dates from 1800 to 2006, then TimeZone.IsDayLightSavingsTime() is broken. Daylight savings time didn't even exist at a national level in the US until 1966 (except for certain periods during the two World Wars). And until 2007, it started the first Sunday in April and ended the last Sunday in October, except for a period in 1974–1975 when DST ran year-round. –  Anomie Apr 8 '11 at 5:03
  	 	
			The documentation says that it is not historic. It only applies the rule for the current culture. There is another facility to get accurate historic data. –  captncraig Apr 8 '11 at 12:54
  	 	
			So at least the brokenness is documented. –  Anomie Apr 8 '11 at 13:27
		*/
	}
}
class SimpleDateObject
{
	private $unix_timestamp=null;
	private $timezone="GMT";

	public function __construct($timestamp=null, $timezone="GMT")
	{
		$this->unix_timestamp=$timestamp;
		$this->timezone=$timezone;
	}

	public function getTimestamp()
	{
		if(is_null($this->unix_timestamp)) {
			return time();
		} else {
			return $this->unix_timestamp;
		}
	}

	public function toGMT()
	{
		return $this->toTimezone("GMT");
	}

	public function toTimezone($timezone)
	{
		// Will use strtotime()
		switch($timezone)
		{ 
			default:
				break;
		}
	}

	public function format($time_format)
	{
		return date($time_format, ((is_null($this->timestamp))?time():$this->timestamp));
	}

	public function milTime($leading_zeros=true)
	{
		return (date((($leading_zeros)?"H":"G").":i:s e", $this->getTimestamp()));
	}
}
?>
