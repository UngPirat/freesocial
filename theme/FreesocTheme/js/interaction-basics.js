$(document).ready(function() {
	$(".notice_data-text").live('input', function(e) {
		$(this).next(".count").html($(this).val().length);
	});
	$(".notice_data-text").live('keyup', function(e) {
		$(this).next(".count").html($(this).val().length);
	});
//	$("#newnotice").focus();
	
	$("header.slidecontrol").click(function(e) {
		$(this).next("section").slideToggle(600);
		if ($(this).next("section").is(":visible")) {
			$("#newnotice").focus();
		}
	});

	$('form.newnotice').live('submit', function(e) {
		var newnotice = $(this).parent();
		e.preventDefault();
		$(this).ajaxSubmit({
			data: { ajax: 'true' },
			replaceTarget: true,
			target: newnotice,
		});
	});

// ajaxform necessary to send files?
/*		var form = $(this).parent();
		var data = $(this).serializeArray();
		data.push({"name":"ajax","value":"true"});
		$.ajax({type: 'POST',
				url: $(this).attr('action'),
				data: data,
				type: 'post'
			})
			.done(function(data) {
				$(form).replaceWith(data);
			});

		return false;
	});
*/

	$('.notice_data-text').live('keydown', function (e) {
		if (e.ctrlKey && e.keyCode == 13) {
			$('#' + $(this).attr('id') + '-submit').click();
		}
	});
	$('.notice .action').live('click', function (e) {
		e.preventDefault();

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
	});

	$(".show-more").live('click', function(e) {
		e.preventDefault();

		var conv = $(this).parent();
		var r = $.ajax('/ajax/' + $(this).parent().attr('id'))
			.done(function(html) {
				$(conv).replaceWith(html);
			});
		$(this).html(loadingIcon());
//		$(this).parent().load('/ajax/' + $(this).parent().attr('id') +  ' article');
	});

	function loadingIcon() {
		return '<img src="/theme/FreesocTheme/img/loading.gif" />';
	}
});
