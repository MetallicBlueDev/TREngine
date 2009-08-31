$().ajaxSend(function(r,s){$("#loader").show();});
$().ajaxStop(function(r,s){$("#loader").fadeOut('fast');});

function displayMessage(message) {$('#block_message').empty().append(message).show();}

function validLogon(formId, loginId, passwordId) {
	$(formId).submit(function(){
		var isLogin = false;
		var isPassword = false;
		var login = $(loginId);
		var password = $(passwordId);
		if (checkLogin(login.val())) {
			login.removeClass('error');
			isLogin = true;
		} else {
			login.addClass('error');
			isLogin = false;
		}
		if (checkPassword(password.val())) {
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

function validForgetLogin(formId, mailId) {
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

function validForgetPass(formId, loginId) {
	$(formId).submit(function(){
		var login = $(loginId);
		if (checkLogin(login.val())) {
			login.removeClass('error');
			postForm(this);
		} else {
			login.addClass('error');
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
function checkPassword(password) {
	return (password.length >= 5);
}
function checkLogin(login) {
	var filter = new RegExp('^[A-Za-z0-9_-]{3,16}$');
	return (login.length >= 3 && filter.test(login));
}
function checkMail(mail) {
	var filter = new RegExp('^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$');
	return filter.test(mail);
}