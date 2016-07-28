<div id="twitterease_instructions">
	<h2>Instructions</h2>

	<p>Before you can install a feed on your website you have to be registered as a Twitter developer and create a Twitter application. Herewith the process for generating the required credentials:</p>

	<ol>
		<li>Go to <a target="_blank" href="https://dev.twitter.com">https://dev.twitter.com</a> and create a profile.</li>
		<li>Now go to <a target="_blank" href="https://apps.twitter.com">https://apps.twitter.com</a></li>
		<li>Click Create New App</li>
		<li>Complete the <code>Create an application</code> form</li>
		<li>Now click the <code>Keys and Access Tokens</code> tab</li>
		<li>Click <code>Create my access token</code> at the bottom of the page</li>
		<li>Now open the <a href="<?php echo site_url(); ?>/wp-admin/options-general.php?page=twitterease_settings&amp;tab=standard">Settings</a> tab and paste the following, found on the Twitter Application page.
			<ol>
				<li>Access Token</li>
				<li>Access Token Secret</li>
				<li>Consumer Key</li>
				<li>Consumer Key Secret</li>
			</ol>
		</li>
		<li>Then continue to fill in your Twitter Username and complete the remaining settings and click Save Settings</li>
		<li>Now, to display the Twitter Feed
			<ol>
				<li>Paste the shortcode <code>[tweasy]</code> wherever you want to display the Twitter feed or;</li>
				<li>Go to <a href="<?php echo site_url(); ?>/wp-admin/widgets.php">Widgets</a> and use the <code>twEasy</code> Widget to place your Twitter Feed</li>
			</ol>
		</li>
	</ol>
</div>