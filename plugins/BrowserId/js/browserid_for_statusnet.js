function browserid_for_statusnet() {
	navigator.id.get(function(assertion) {
	    if (assertion) {
			var r = new XMLHttpRequest();
			var params = "assertion="+ assertion;
			r.open("POST", "/main/browseridlogin", true);
			r.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			r.setRequestHeader("Content-Length", params.length);
			r.onreadystatechange = function () {
				if (r.readyState != 4 || r.status != 200)
					return;
				window.location.reload();
			};
			r.send(params);
	    } else {
			alert ("not working");
	        // something went wrong!  the user isn't logged in.
    	}
	});
}
