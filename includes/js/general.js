jQuery(document).ready( function() {
	// Put your JS in here, and it will run after the DOM has loaded.
	
	// jQuery.post( ajaxurl, {
	// 	action: 'my_peer_session_action',
	// 	'cookie': encodeURIComponent(document.cookie),
	// 	'parameter_1': 'some_value'
	// }, 
	// function(response) { 
	// 	... 
	// } );

});


function bsPeerSessionUnfade() {
	const elementList = document.getElementsByClassName("fadeable");
	for (i = 0; i < elementList.length; ++i) {
		const element = elementList[i];
		if (!element) {
			continue;
		}
		if (element.classList) {
			element.classList.add("fadeable--unfade");
			element.classList.remove("fadeable--fade");
		}
	}
}

function bsPeerSessionFade() {
	const elementList = document.getElementsByClassName("fadeable");
	for (i = 0; i < elementList.length; ++i) {
		const element = elementList[i];
		if (!element) {
			continue;
		}
		if (element.classList) {
			element.classList.add("fadeable--fade");
			element.classList.remove("fadeable--unfade");
		}
	}
}