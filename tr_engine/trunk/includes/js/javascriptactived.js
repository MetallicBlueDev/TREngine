function javaScriptActived(name) {
	if (navigator.cookieEnabled) {
		var cookie_name = name;
		if (document.cookie.indexOf(cookie_name + '=') < 0) {
			document.cookie = cookie_name + '=' + escape(1);
			document.location.reload();
		}
	}
}