App Banner
===================

## Usage ##
    <html>
      <head>
            ...
            <!-- Tapatalk Banner head start -->
            <link href="'.$tapatalk_dir_url.'/smartbanner/appbanner.css" rel="stylesheet" type="text/css" media="screen">
            <script type="text/javascript">
                var is_mobile_skin     = '.$is_mobile_skin.';
                var app_ios_id         = '.intval($settings['app_ios_id']).';
                var app_android_url    = "'.addslashes($settings['app_android_url']).'";
                var app_kindle_url     = "'.addslashes($settings['app_kindle_url']).'";
                var app_banner_message = "'.addslashes(str_replace("\n", '<br />', $settings['app_banner_message'])).'";
                var app_forum_name     = "'.addslashes($settings['board_name']).'";
                var app_location_url   = "'.addslashes($app_location_url).'";
            </script>
            <script src="'.$tapatalk_dir_url.'/smartbanner/appbanner.js" type="text/javascript"></script>
            <!-- Tapatalk Banner head end-->
            ...
      </head>
      <body>
        ...
        <!-- Tapatalk Banner body start -->
            <script type="text/javascript">tapatalkDetect()</script>
        <!-- Tapatalk Banner body end -->
        ...
      </body>
    </html>

## Options ##
    $.smartbanner({
      title: null, // What the title of the app should be in the banner (defaults to <title>)
      author: null, // What the author of the app should be in the banner (defaults to <meta name="author"> or hostname)
      price: 'Free', // Price of the app
      inAppStore: 'In the App Store', // Text of price for iOS
      inGooglePlay: 'In Google Play', // Text of price for Android
      icon: null, // The URL of the icon (defaults to <meta name="apple-touch-icon">)
      iconGloss: null, // Force gloss effect for iOS even for precomposed
      button: 'VIEW', // Text for the install button
      scale: 'auto', // Scale based on viewport size (set to 1 to disable)
      speedIn: 300, // Show animation speed of the banner
      speedOut: 400, // Close animation speed of the banner
      daysHidden: 15, // Duration to hide the banner after being closed (0 = always show banner)
      daysReminder: 90, // Duration to hide the banner after "VIEW" is clicked *separate from when the close button is clicked* (0 = always show banner)
      force: null // Choose 'ios' or 'android'. Don't do a browser check, just always show this banner
    })

  [1]: http://developer.apple.com/library/ios/#documentation/AppleApplications/Reference/SafariWebContent/PromotingAppswithAppBanners/PromotingAppswithAppBanners.html
