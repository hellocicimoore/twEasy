(function($) {

	$( document ).ready( function() {

		var tweets = JSON.parse( tWE_tweets );
		tweets.reverse();

		if ( tweets.length ) {

			$('#twitterEase, #twitterEaseWidget').html( '<ul></ul>' );

			$.each( tweets, function(i) {

				// Setup date variable
				var date = new Date();
				var v = tweets[i].created_at.split(' ');
				var utc = Date.parse(v[1]+" "+v[2]+", "+v[5]+" "+v[3]+" UTC");
				date.setTime(utc);

				// Activate Hashtags
				var hashtags = tweets[i].entities.hashtags;
				if ( typeof hashtags != "undefined" && hashtags.length ) {
					for (j = 0; j < hashtags.length; j++) {
						tweets[i].text = tweets[i].text.replace('#' + hashtags[j].text, '<a target="_blank" class="twitstyle" href="https://twitter.com/search?q=%23'+ hashtags[j].text + '">#' + hashtags[j].text + '</a>');
					}
				}

				// Active user mentions
				var tweeps = tweets[i].entities.user_mentions;
				if ( typeof tweeps != "undefined" && tweeps.length ) {
					for (k = 0; k < tweeps.length; k++) {
						tweets[i].text = tweets[i].text.replace('@' + tweeps[k].screen_name, '<a target="_blank" class="twitstyle" href="http://www.twitter.com/' + tweeps[k].screen_name + '">@' + tweeps[k].screen_name + '</a>');
					}
				}

				// Activate URLS
				var urls = tweets[i].entities.urls;
				if ( typeof urls != "undefined" && urls.length ) {
					for (l = 0; l < urls.length; l++) {
						tweets[i].text = tweets[i].text.replace(urls[l].url, '<a target="_blank" class="twitstyle" href="' + urls[l].expanded_url + '">' + urls[l].display_url + '</a>');
					}
				}

				// Media
				var media = tweets[i].entities.media;
				if ( typeof media != "undefined" && media.length ) {
					for (l = 0; l < media.length; l++) {
						tweets[i].text = tweets[i].text.replace(media[l].url, '<a target="_blank" class="twitstyle" href="' + media[l].expanded_url + '">' + media[l].url + '</a>','g');
					}
				}

				var tweet = '<li>' + tweets[i].text + '</li>';

				// Append each tweet
				$('#twitterEase ul, #twitterEaseWidget ul').prepend( tweet );
			});

		}

	});

})( jQuery );