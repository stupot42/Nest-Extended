<?php
// The Nest API Class file
require_once('../libs/nest/nest.class.php');

// The Nest-Beyond Configuration
require_once('../config.php');

//Capture date and time of script execution
$runTime = $date = date('Y-m-d H:i:s');

//Create a new Nest Object
$nest = new Nest();

//Used to return current outside temperature and away status
//Uncomment out the three lines below to view what getUserLocations returns [JSON Format]
$locations = $nest->getUserLocations();

//Used to return current inside temperature, current inside humidity, current mode, target temperature, time to target temperature, current heat state, current ac state
//Uncomment out the three lines below to view what getDeviceInfo returns [JSON Format]
$infos = $nest->getDeviceInfo();

//If away mode, read temperature array to get target temperature.
if (is_array($infos->target->temperature)) {
	if (strpos($infos->current_state->mode,'heat') !== false) {
		$targettemp = $infos->target->temperature[0];
	} elseif(strpos($infos->current_state->mode,'ac') !== false) {
		$targettemp = $infos->target->temperature[1];
	}
} else {
	$targettemp = $infos->target->temperature;
}

//Connect to the Database
$con=mysql_connect($hostname,$username, $password) OR DIE ('Unable to connect to database! Please try again later.');
mysql_select_db($dbname);

//Insert Current Values into Nest Database Table
$query = 'INSERT INTO nest (log_datetime, location, outside_temp, outside_humidity, away_status, leaf_status, current_temp, current_humidity, temp_mode, target_temp, time_to_target, target_humidity, heat_on, humidifier_on, ac_on, fan_on, battery_level, is_online) VALUES ("'.$runTime.'", "'.$locations[0]->postal_code.'", "'.$locations[0]->outside_temperature.'", "'.$locations[0]->outside_humidity.'", "'.$locations[0]->away.'", "'.$infos->current_state->leaf.'", "'.$infos->current_state->temperature.'", "'.$infos->current_state->humidity.'", "'.$infos->current_state->mode.'", "'.$targettemp.'", "'.$infos->target->time_to_target.'","'.$infos->target->humidity.'","'.$infos->current_state->heat.'","'.$infos->current_state->humidifier.'","'.$infos->current_state->ac.'","'.$infos->current_state->fan.'","'.$infos->current_state->battery_level.'","'.$infos->network->online.'")';
$result = mysql_query($query);	

//Close mySQL DB connection
mysql_close($con);

//Set the humidity level if enabled.
if ($set_humidity === 1) {
	require_once('nest-humidity.php');
}

/* Helper functions */
function json_format($json) { 
    $tab = "  "; 
    $new_json = ""; 
    $indent_level = 0; 
    $in_string = false; 

    $json_obj = json_decode($json); 

    if($json_obj === false) 
        return false; 

    $json = json_encode($json_obj); 
    $len = strlen($json); 

    for($c = 0; $c < $len; $c++) 
    { 
        $char = $json[$c]; 
        switch($char) 
        { 
            case '{': 
            case '[': 
                if(!$in_string) 
                { 
                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1); 
                    $indent_level++; 
                } 
                else 
                { 
                    $new_json .= $char; 
                } 
                break; 
            case '}': 
            case ']': 
                if(!$in_string) 
                { 
                    $indent_level--; 
                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char; 
                } 
                else 
                { 
                    $new_json .= $char; 
                } 
                break; 
            case ',': 
                if(!$in_string) 
                { 
                    $new_json .= ",\n" . str_repeat($tab, $indent_level); 
                } 
                else 
                { 
                    $new_json .= $char; 
                } 
                break; 
            case ':': 
                if(!$in_string) 
                { 
                    $new_json .= ": "; 
                } 
                else 
                { 
                    $new_json .= $char; 
                } 
                break; 
            case '"': 
                if($c > 0 && $json[$c-1] != '\\') 
                { 
                    $in_string = !$in_string; 
                } 
            default: 
                $new_json .= $char; 
                break;                    
        } 
    } 

    return $new_json; 
}

function jlog($json) {
    if (!is_string($json)) {
        $json = json_encode($json);
    }
    echo json_format($json) . "\n";
}