/*jshint esversion: 6 */

function updateCost(idCountry) {
	const xhttp = new XMLHttpRequest();
	xhttp.onload = function() {
		document.getElementById("demo").innerHTML =
			this.responseText;
	}
	xhttp.open("POST", "\'' . DOL_URL_ROOT . '/core/ajax/ajaxstatusprospect.php\'");
	xhttp.send();
}
