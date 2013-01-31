/*!
 * jQuery Smart Banner
 * Copyright (c) 2012 Arnold Daniels <arnold@jasny.net>
 * Based on 'jQuery Smart Web App Banner' by Kurt Zenisek @ kzeni.com
 */
!function(jQuery) {
    var SmartBanner = function(options) {
        this.origHtmlMargin = parseFloat(jQuery('html').css('margin-top')) // Get the original margin-top of the HTML element so we can take that into account
        this.options = jQuery.extend({}, jQuery.smartbanner.defaults, options)
        
        var standalone = navigator.standalone // Check if it's already a standalone web app or running within a webui view of an app (not mobile safari)

        // Detect banner type (iOS or Android)
        if (this.options.force) {
            this.type = this.options.force
        } else if (navigator.userAgent.match(/iPod|iPad|iPhone/i) != null) {
            this.type = 'ios'
        } else if (navigator.userAgent.match(/Android|Kindle Fire/i) != null) {
            this.type = 'android'
        }
        
        // Don't show banner if device isn't iOS or Android, website is loaded in app or user dismissed banner
        if (!this.type || standalone || this.getCookie('sb-closed') || this.getCookie('sb-installed')) {
            return
        }
        
        // Calculate scale
        this.scale = this.options.scale == 'auto' ? jQuery(window).width() / window.screen.width : this.options.scale
        if (this.scale < 1) this.scale = 1

        // Get info from meta data
        var meta = jQuery(this.type=='android' ? 'meta[name="google-play-app"]' : 'meta[name="apple-itune-app"]')
        if (meta.length == 0) return
        
        this.appId = /app-id=([^\s,]+)/.exec(meta.attr('content'))[1]
        this.title = this.options.title ? this.options.title : jQuery('title').text().replace(/\s*[|\-·].*jQuery/, '')
        this.author = this.options.author ? this.options.author : (jQuery('meta[name="author"]').length ? jQuery('meta[name="author"]').attr('content') : window.location.hostname)

        // Create banner
        this.create()
        this.show()
        this.listen()
    }
        
    SmartBanner.prototype = {

        constructor: SmartBanner
    
      , create: function() {
            var iconURL
              , link= 'market://details?id=com.quoord.tapatalkHD'
              , inStore=this.options.price ? this.options.price + ' - ' + (this.type=='android' ? this.options.inGooglePlay : this.options.inAppStore) : ''
              , gloss=this.options.iconGloss === null ? (this.type=='ios') : this.options.iconGloss

            jQuery('body').append('<div id="smartbanner" class="'+this.type+'"><div class="sb-container"><a href="#" class="sb-close">&times;</a><span class="sb-icon"></span><div class="sb-info"><strong>'+
            		this.title+'</strong><span>'+this.author+'</span></div><div class="tt-buttons"><a href="'+this.options.url+'" class="sb-button"><span>'+this.options.button+'</span></a>'+
            		'<a href="'+link+'" class="open-button"><span>Install</span></a>'
            		+'</div></div></div>')
            
            if (this.options.icon) {
                iconURL = this.options.icon
            } else if (jQuery('link[rel="apple-touch-icon-precomposed"]').length > 0) {
                iconURL = jQuery('link[rel="apple-touch-icon-precomposed"]').attr('href')
                if (this.options.iconGloss === null) gloss = false
            } else if (jQuery('link[rel="apple-touch-icon"]').length > 0) {
                iconURL = jQuery('link[rel="apple-touch-icon"]').attr('href')
            }
            if (iconURL) {
                jQuery('#smartbanner .sb-icon').css('background-image','url('+iconURL+')')
                if (gloss) jQuery('#smartbanner .sb-icon').addClass('gloss')
            } else{
                jQuery('#smartbanner').addClass('no-icon')
            }

            this.bannerHeight = jQuery('#smartbanner').outerHeight() + 2
            if (this.scale > 1) {
                jQuery('#smartbanner')
                    .css('top', parseFloat(jQuery('#smartbanner').css('top')) * this.scale)
                    .css('height', parseFloat(jQuery('#smartbanner').css('height')) * this.scale)
              
                jQuery('#smartbanner .sb-container')
                    .css('-webkit-transform', 'scale('+this.scale+')')
                    .css('-msie-transform', 'scale('+this.scale+')')
                    .css('-moz-transform', 'scale('+this.scale+')')
                    .css('width', jQuery(window).width() / this.scale)
            }
        }
        
      , listen: function () {
            jQuery('#smartbanner .sb-close').on('click',jQuery.proxy(this.close, this))
            jQuery('#smartbanner .sb-button').on('click',jQuery.proxy(this.install, this))
        }
        
      , show: function(callback) {
            jQuery('#smartbanner').stop().animate({top:0},this.options.speedIn).addClass('shown')
            jQuery('html').animate({marginTop:this.origHtmlMargin+(this.bannerHeight*this.scale)},this.options.speedIn,'swing',callback)
        }
        
      , hide: function(callback) {
            jQuery('#smartbanner').stop().animate({top:-1*this.bannerHeight*this.scale},this.options.speedOut).removeClass('shown')
            jQuery('html').animate({marginTop:this.origHtmlMargin},this.options.speedOut,'swing',callback)
        }
      
      , close: function(e) {
            e.preventDefault()
            this.hide()
            this.setCookie('sb-closed','true',this.options.daysHidden)
        }
       
      , install: function(e) {
            //this.hide()
            //this.setCookie('sb-installed','true',this.options.daysReminder)
        }
       
      , setCookie: function(name, value, exdays) {
            var exdate = new Date()
            exdate.setDate(exdate.getDate()+exdays)
            value=escape(value)+((exdays==null)?'':'; expires='+exdate.toUTCString())
            document.cookie=name+'='+value+'; path=/;'
        }
        
      , getCookie: function(name) {
            var i,x,y,ARRcookies = document.cookie.split(";")
            for(i=0;i<ARRcookies.length;i++) {
                x = ARRcookies[i].substr(0,ARRcookies[i].indexOf("="))
                y = ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1)
                x = x.replace(/^\s+|\s+jQuery/g,"")
                if (x==name) {
                    return unescape(y)
                }
            }
            return null
        }
      
      // Demo only
      , switchType: function() {
          var that = this
          
          this.hide(function() {
            that.type = that.type=='android' ? 'ios' : 'android'
            var meta = jQuery(that.type=='android' ? 'meta[name="google-play-app"]' : 'meta[name="apple-itune-app"]').attr('content')
            that.appId = /app-id=([^\s,]+)/.exec(meta)[1]
            
            jQuery('#smartbanner').detach()
            that.create()
            that.show()
          })
        }
    }

    jQuery.smartbanner = function(option) {
        var jQuerywindow = jQuery(window)
        , data = jQuerywindow.data('typeahead')
        , options = typeof option == 'object' && option
      if (!data) jQuerywindow.data('typeahead', (data = new SmartBanner(options)))
      if (typeof option == 'string') data[option]()
    }
    
    // override these globally if you like (they are all optional)
    jQuery.smartbanner.defaults = {
        title: null, // What the title of the app should be in the banner (defaults to <title>)
        author: null, // What the author of the app should be in the banner (defaults to <meta name="author"> or hostname)
        price: 'Free', // Price of the app
        inAppStore: 'On the App Store', // Text of price for iOS
        inGooglePlay: 'In Google Play', // Text of price for Android
        icon: null, // The URL of the icon (defaults to <meta name="apple-touch-icon">)
        iconGloss: null, // Force gloss effect for iOS even for precomposed
        button: 'View In App', // Text for the install button
        scale: 'auto', // Scale based on viewport size (set to 1 to disable)
        speedIn: 300, // Show animation speed of the banner
        speedOut: 400, // Close animation speed of the banner
        daysHidden: 15, // Duration to hide the banner after being closed (0 = always show banner)
        daysReminder: 90, // Duration to hide the banner after "VIEW" is clicked *separate from when the close button is clicked* (0 = always show banner)
        force: null // Choose 'ios' or 'android'. Don't do a browser check, just always show this banner
    }
    
    jQuery.smartbanner.Constructor = SmartBanner

}(window.jQuery);
