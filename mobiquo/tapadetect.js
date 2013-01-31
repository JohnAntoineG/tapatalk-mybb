function detectTapatalk() {
    if (document.cookie.indexOf("tapatalk_redirect4=false") < 0) {
        if (!navigator.userAgent.match(/Opera/i)) {
            if ((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i))) {
                setTapatalkCookies();
                if ((tapatalk_iphone_msg != '') && confirm(tapatalk_iphone_msg))
                    window.location = tapatalk_iphone_url;
            } else if(navigator.userAgent.match(/iPad/i)) {
                setTapatalkCookies();
                if ((tapatalk_ipad_msg != '') && confirm(tapatalk_ipad_msg))
                    window.location = tapatalk_ipad_url;
            } else if(navigator.userAgent.match(/Silk/)) {
                setTapatalkCookies();
                if ((tapatalk_kindle_msg != '') && confirm(tapatalk_kindle_msg) && navigator.userAgent.match(/Android 2/i))
                    window.location = tapatalk_kindle_url;
                else if((tapatalk_kindle_hd_msg != '') && confirm(tapatalk_kindle_hd_msg) && navigator.userAgent.match(/Android 4/i))
                	window.location = tapatalk_kindle_hd_url;
            } else if(navigator.userAgent.match(/Android/i)) {
            	if(navigator.userAgent.match(/mobile/i))
            	{
	                setTapatalkCookies();
	                if ((tapatalk_android_msg != '') && confirm(tapatalk_android_msg))
	                    window.location = tapatalk_android_url;
            	}
            } else if(navigator.userAgent.match(/BlackBerry/i)) {
                setTapatalkCookies();
                if (confirm("This forum has an app for BlackBerry! Click OK to learn more about Tapatalk."))
                    window.location = "http://appworld.blackberry.com/webstore/content/46654?lang=en";
            } 
        }
    }
}

function setTapatalkCookies() {
    var date = new Date();
    var days = 90;
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = "; expires="+ date.toGMTString();
    var domain = "; path=/";
    document.cookie = "tapatalk_redirect4=false" + expires + domain; 
}

detectTapatalk();