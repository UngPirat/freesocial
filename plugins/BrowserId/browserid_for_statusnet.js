function browserid_for_statusnet() {
	navigator.id.get(function(assertion) {
	    if (assertion) {
			window.location = "/main/browseridauth?assertion=" + encodeURIComponent(assertion);
	        // This code will be invoked once the user has successfully
	        // selected an email address they control to sign in with.
	    } else {
			alert ("not working");
	        // something went wrong!  the user isn't logged in.
    	}
	});
}
