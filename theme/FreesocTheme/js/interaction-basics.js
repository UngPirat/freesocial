$(document).ready(function() {
	$(".notice_data-text").bind('input', function(e) {
		$(this).next(".count").html($(this).val().length);
	});
	$(".notice_data-text").bind('keyup', function(e) {
		$(this).next(".count").html($(this).val().length);
	});
//	$("#newnotice").focus();
	
	$("header.slidecontrol").click(function(e) {
		$(this).next("section").slideToggle(600);
		if ($(this).next("section").is(":visible")) {
			$("#newnotice").focus();
		}
	});

	$('#newnotice').bind('keydown', function (e) {
		if (e.ctrlKey && e.keyCode == 13) {
			$("#newnotice-submit").click();
		}
	});
	$('.notice .action').live('click', function (e) {
		var element = $(this);
// fuck you webkit		[action, id] = $(element).attr('id').split('-');
		var action = $(element).attr('id').split('-')[0];
		var id = $(element).attr('id').split('-')[1];

		// if we generate a textarea
		var actionsection = $('#notice-' + id + ' + section.form');
		
		if (actionsection.hasClass(action)) {
			var textarea = actionsection.find('textarea');
			textarea.focus();
		} else {
			notice = $('#notice-' + id);
			var r = $.ajax('/ajax/' + action + '-' + id)
				.done(function(html) {
					$(notice).after(html);
					$(element).trigger('click');
				});
			actionsection.remove();
		}
		return false;
	});

	$(".show-more").bind('click', function(e) {
		var conv = $(this).parent();
		var r = $.ajax('/ajax/' + $(this).parent().attr('id'))
			.done(function(html) {
				$(conv).replaceWith(html);
			});
		$(this).html(loadingIcon());
//		$(this).parent().load('/ajax/' + $(this).parent().attr('id') +  ' article');
		return false;
	});

	function loadingIcon() {
		return '<img src="/theme/FreesocTheme/img/loading.gif" />';
	}
});
