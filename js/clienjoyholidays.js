/*jshint esversion: 6 */

function init(action) {
	if (action === "updateCost"){
		const selectDestination = $('#destination');
		selectDestination.on('change', function () {
			if (!$('#cost').val()) {
				updateCost(selectDestination.val())
			}
		});
	}
}


function updateCost(destination) {
	idToken = $("[name='token']").first().val()
	$.ajax({
		type: 'POST',
		url: 'scripts/interface.php',
		dataType: 'json',
		data: {
			destination: destination,
			token: idToken,
			action: "getCost"
		},

		success: function (obj, textstatus) {
			switch (obj.error) {
				case "error":
					$.jnotify(obj.message, "error");
					break;
				case "warning":
					$.jnotify(obj.message, "warning");
					break;
				default:
					$("#cost").val(obj.cost);
			}
		}
	})
}
