$(document).ready(function() {
	$("#newnotice").bind('input', function(event) {
	    var currentString = $("#newnotice").val()
		$("#newnotice + .count").html(currentString.length);
	});
	$("#newnotice").bind('keyup', function(event) {
	    var currentString = $("#newnotice").val()
		$("#newnotice + .count").html(currentString.length);
	});
	$("#newnotice").focus();
});
