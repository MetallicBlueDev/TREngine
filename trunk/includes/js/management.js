function validSetting(formId, siteNameId, mailId) {
	$(formId).submit(function(){
		var isSiteName = false;
		var isMail = false;
		if ($(siteNameId).val().length > 0) {
			$(siteNameId).removeClass('error');
			isSiteName = true;
		} else {
			$(siteNameId).addClass('error');
		}
		if (checkMail($(mailId).val())) {
			$(mailId).removeClass('error');
			isMail = true;
		} else {
			$(mailId).addClass('error');
		}
		if (isSiteName && isMail) {postForm(this);}
		return false;
	});
}