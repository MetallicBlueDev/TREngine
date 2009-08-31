$().ajaxSend(function(r,s){$("#loader").show();});
$().ajaxStop(function(r,s){$("#loader").fadeOut('fast');});

function displayMessage(message) {alert(message);$('#block_message').empty().append(message).show();}

function validLogin(formId, loginId, passwordId) {
	$(formId).submit(function(){
		var isLogin = false;
		var isPassword = false;
		var login = $(loginId);
		var password = $(passwordId);
		var filter = new RegExp('^[A-Za-z0-9_-]{3,16}$');
		if (login.val().length >= 3 && filter.test(login.val())) {
			login.removeClass('error');
			isLogin = true;
		} else {
			login.addClass('error');
			isLogin = false;
		}
		if (password.val().length >= 5) {
			password.removeClass('error');
			isPassword = true;
		} else {
			password.addClass('error');
			isPassword = false;
		}
		if (isLogin && isPassword) {postForm(this);}
		return false;
	});
}

function validMail(formId, mailId) {
	$(formId).submit(function(){
		var mail = $(mailId);
		if (checkMail(mail.val())) {
			mail.removeClass('error');
			postForm(this);
		} else {
			mail.addClass('error');
		}
		return false;
	});
}

function validLink(divId, link) {
	$(divId).load(link);
	return false;
}

function postForm(form) {
	disableForm(form);
	$.ajax({
		type: 'POST',
		data: $(form).serialize(),
		url: $(form).attr('action'),
		success: function(message){ displayMessage(message); }
	});
	enableForm(form);
}

function disableForm(form) {
	var submitButton = $(form).find("input[type='submit']");
	$(submitButton).attr("value", $(submitButton).attr("value") + "...");
	$(submitButton).attr("disabled", "disabled");
}

function enableForm(form) {
	var submitButton = $(form).find("input[type='submit']");
	$(submitButton).attr("value", $(submitButton).attr("value").substr(0,  $(submitButton).attr("value").length - 3));
	$(submitButton).removeAttr("disabled");
}

function checkMail(mail) {
	var filter = new RegExp('^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$');
	return filter.test(mail);
}