<?php
// 'THE MODEL'

require("config.inc.php");
require("OAuth/OAuth.php");
require("Yahoo/YahooOAuthApplication.class.php");

$app = new YahooOAuthApplication(CONSUMER_KEY, CONSUMER_SECRET, APP_ID);

if(isset($_GET['logout'])) {
   // if a session exists and the logout flag is detected
   // clear the session tokens and reload the page.
   // YahooSession::clearSession();
   oauth_unset_cookie('yos-social-rt');
   oauth_unset_cookie('yos-social-at');
   header("Location: /apps/popup/index.php");
}

if(array_key_exists("in_popup", $_GET)) {
   // print_r($_COOKIE);
   $request_token = oauth_get_cookie('yos-social-rt');
   $app->token = $app->getAccessToken($request_token, $_GET['oauth_verifier']);
   $app->token->expires = 'foobar';
   oauth_set_cookie('yos-social-at', $app->token, $app->token->expires_in);
   close_popup();
   exit;
   
} else {
   $token = oauth_get_cookie('yos-social-at');
   if($token && isset($token->yahoo_guid)) {
      // set the token in the SDK
      $app->token = $token;

      // do it!
      $profile = $app->getProfile($token->yahoo_guid)->profile;
      
      if(isset($_GET['update'])) {
         $update = $app->insertUpdate(array(
            'title' => "cloned the yos-social-php5 SDK on Github",
            'description' => "A PHP5 SDK for YQL",
            'link' => "http://github.com/yahoo/yos-social-php5",
            'imgURL' => 'http://github.com/images/modules/header/logov3.png', 
            'imgWidth' => '100',
            'imgHeight' => '45'
         ));
      }
   } else {
      $callback_params = array('in_popup' => true);
      $callback = sprintf("%s://%s%s?%s", ($_SERVER["HTTPS"] == 'on') ? 'https' : 'http', $_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"], http_build_query($callback_params));
      $request_token = $app->getRequestToken($callback);

      oauth_set_cookie('yos-social-rt', $request_token, $request_token->expires_in);

      $auth_url = $app->getAuthorizationUrl($request_token);
   }
}

function close_popup() 
{
?>
 <script type="text/javascript">
  window.close();
 </script>
<?
}

function oauth_get_cookie($name) 
{
   return unserialize(base64_decode($_COOKIE[$name]));
}

function oauth_set_cookie($name, $data, $expires = 3600) 
{
   setcookie($name, base64_encode(serialize($data)), time() + $expires);
}

function oauth_unset_cookie($name) 
{
   setcookie($name, '', time()-600);
}

// NOW THE 'VIEW'
?>
<!DOCTYPE html>
<html>
   <head>
      <title>Yahoo! OAuth Example</title>
		
      <!-- Combo-handled YUI JS files: --> 
      <script type="text/javascript" src="http://yui.yahooapis.com/combo?2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
      <script type="text/javascript" src="js/popupmanager.js"></script>
      
      <!-- Combo-handled YUI CSS files: --> 
      <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.7.0/build/reset-fonts-grids/reset-fonts-grids.css&amp;2.7.0/build/base/base-min.css">
   </head>
   <body>
	<?php
		if(isset($token) && isset($profile)) {
		   // if a session does exist and the profile data was 
			// fetched without error, print out a simple usercard.
			printf("<img src=\"%s\"/><p><h2>Hi <a href=\"%s\" target=\"_blank\">%s!</a></h2></p>\n", 
					$profile->image->imageUrl, $profile->profileUrl, $profile->nickname);

			if($profile->status->message != "") {
				$statusDate = date('F j, y, g:i a', strtotime($profile->status->lastStatusModified));
				printf("<p><strong>&#8220;</strong>%s<strong>&#8221;</strong> on %s</p>", 
						$profile->status->message, $statusDate);	
			}
			
			if(isset($update)) {
			   print '<p>';
			   print_r($update);
			   print '</p>';
			} 
			
			print "<button id='uptBtn'>Post Update</button>";
			print "<p><a href=\"?logout\">Logout</a></p>";
		} else {
			// if a session does not exist, output the 
			// login / share button linked to the auth_url.
			printf("<a href=\"%s\" id=\"yloginLink\"><img src=\"http://l.yimg.com/a/i/ydn/social/updt-spurp.png\"></a>\n", $auth_url);
		}
	?>
	<script type="text/javascript">
		var _gel = function(el) {return document.getElementById(el)};
		
		YAHOO.util.Event.onDOMReady(function() {
		   if(_gel('yloginLink')) {
				YAHOO.util.Event.addListener("yloginLink", "click", function(event) {
				   // block the url from opening like normal
   				YAHOO.util.Event.preventDefault(event);
   				var auth_url = _gel("yloginLink").href;
   				// open pop-up using the auth_url
   				PopupManager.open(auth_url,600,435);
				});
			}
			
			if(_gel('uptBtn')) {
				YAHOO.util.Event.addListener('uptBtn', "click", function(event) {
				   document.location = document.location+'?update';
				}); 
			}
		}); 
	</script>
</body>
</html>
