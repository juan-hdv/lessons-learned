/*
 * 
 * Autor: From the blog http://jordanhollinger.com/2012/06/08/disable-the-back-button-using-html5
 * general Use Functions
 * 
 * Prevent back navigation from browser ç
 * 
 * Use:
 * For each page called that you what to prevent back navigation,
 * just inform with a url hash, that it can't go back
 * For example: 
 * Call http://localhost/app/page1.html#no.back  
 * -> This page will not allow goBack through browser left arrow 
 * 
 */
var history_api = typeof history.pushState !== 'undefined'
// The previous page asks that it not be returned to
if ( location.hash == '' ) { // #no-back
  // Push "#no-back" onto the history, making it the most recent "page"
	if ( history_api ) { 
  		history.pushState(null, '', '');
	} else {
  		//location.hash = '#stay';
	}
/*	
	// When the back button is pressed, it will harmlessly change the url
	// hash from "#stay" to "#no-back", which triggers this function
	window.onhashchange = function() {
		// User tried to go back; warn user, rinse and repeat
		if ( location.hash == '' ) { // #no-back
			alert("You shall not pass!");
			if ( history_api ) {
				history.pushState(null, '', '#stay');
			} else { 
				// location.hash = '#stay';
			}
	    }
	}
*/
} // End if
