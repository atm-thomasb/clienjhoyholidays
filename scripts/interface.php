<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}


// Request treatment
$action = GETPOST("action");
if ($action == "getCost") {
	$destination = GETPOST("destination");
	$response = getDefaultCost($destination);
	exit(json_encode($response));
}

// Puts default price according to selected country
function getDefaultCost($destination)
{
	global $db;

	if ($destination < 0 || !ctype_digit($destination)) {
		return ["message" => "Pays Invalide.", "error" => "error"];
	}

	$sql = "SELECT cost";
	$sql .= " FROM " . MAIN_DB_PREFIX . "c_enjoyholidays_country_costs";
	$sql .= " WHERE country = $destination AND active=1";
	$resql = $db->query($sql);
	# Check database error
	if ($resql) {
		$obj = $db->fetch_object($resql);
	} else {
		return ["message" => "Erreur de base de données." . $db->lasterror(), "error" => "error"];
	}

	if (isset($obj->cost)) {
		$cost = $obj->cost;
		$message = "Success.";
	} else {
		$cost = getDolGlobalString('CLIENJOYHOLIDAYS_DEFAULT_COST');
		# Check conf error
		if ($cost === "" || $cost === "default value") {
			return ["cost" => $cost, "message" => "Warning : Default cost configuration isn't set.", "error" => "warning",];
		} else if ($cost < 0 || !ctype_digit($cost)) {
			return ["cost" => $cost, "message" => "Warning : Default cost configuration is invalid.", "error" => "warning",];
		} else {
			$message = "Success (Default_Cost).";
		}
	}

	return ["cost" => $cost, "message" => $message, "error" => "succes"];
}
