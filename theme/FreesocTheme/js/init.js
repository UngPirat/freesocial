$(document).ready(function() {
	$("header.slidecontrol").next("section").hide();
	$('.preview .thumb').each(function() {
		this.href += '/file';
	});
	$('.preview .thumb').fancybox();
});
