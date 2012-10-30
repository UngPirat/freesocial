$(document).ready(function() {
	$("body:not(.newnotice-action) header.slidecontrol").next("section").hide();
	$("body.newnotice-action #newnotice").focus();
	
	$('.preview .thumb').each(function() {
		this.href += '/file';
	});
	$('.preview .thumb').fancybox();
});
