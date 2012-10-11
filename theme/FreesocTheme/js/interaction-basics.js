$(document).ready(function() {
	$(".notice_data-text").bind("input", function(e) {
		$(this).next(".count").html($(this).val().length);
	});
	$(".notice_data-text").bind("keyup", function(e) {
		$(this).next(".count").html($(this).val().length);
	});
//	$("#newnotice").focus();
	
	$("header.slidecontrol").click(function(e) {
		$(this).next("section").slideToggle(600);
		if ($(this).next("section").is(":visible")) {
			$("#newnotice").focus();
		}
	});

	$('#newnotice').keydown(function (e) {
		if (e.ctrlKey && e.keyCode == 13) {
			$("#newnotice-submit").click();
		}
	});
});
