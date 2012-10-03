$(document).ready(function() {
	$(".notice_data-text").bind("input", function(event) {
		$(this).next(".count").html($(this).val().length);
	});
	$(".notice_data-text").bind("keyup", function(event) {
		$(this).next(".count").html($(this).val().length);
	});
//	$("#newnotice").focus();
	
	$("header.slidecontrol").click(function(event) {
		$(this).next("section").slideToggle(600);
	});
});
