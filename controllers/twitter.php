<?php defined('SYSPATH') OR die('No direct access allowed.');



class Twitter_Controller extends Controller {
	
	public function index() {
		
	}
	
	public function connect() {
		$config  = Kohana::config('twitter');
		$connection = new Twitter($config['CONSUMER_KEY'], $config['CONSUMER_SECRET']);

		// TODO: could manually determine that if set to empty
		$callback_url = $config['OAUTH_CALLBACK'];
		if (empty($callback_url))
			$callback_url = url::site('twitter/callback', 'http');
			
		$request_token = $connection->getRequestToken($callback_url);
		$token = $request_token['oauth_token'];
		
		$session = Session::instance();
		$session->set('oauth_token', $token);
		$session->set('oauth_token_secret', $request_token['oauth_token_secret']);
		
		/* If last connection failed don't display authorization link. */
		switch ($connection->http_code) {
		  case 200:
		    /* Build authorize URL and redirect user to Twitter. */
		    $url = $connection->getAuthorizeURL($token);
		    header('Location: ' . $url); 
		    break;
		  default:
		    /* Show notification if something went wrong. */
		    echo 'Could not connect to Twitter. Refresh the page or try again later.';
		}		
	}
	
	public function callback() {
		$config  = Kohana::config('twitter');
		
		if (isset($_REQUEST['denied'])) {
			header('Location: ' . (empty($config['DENY_URL']) ? url::site('/', 'http') : $config['DENY_URL'] ));
			return;
		}
		
		$session = Session::instance();
		/* If the oauth_token is old redirect to the connect page. */
		if (isset($_REQUEST['oauth_token']) && $session->get('oauth_token') !== $_REQUEST['oauth_token']) {
			$session->set('oauth_status', 'oldtoken');
			$session->set('oauth_token', NULL);
			header('Location: ' . url::site('twitter/connect', 'http'));
			return;
		}
		
		$connection = new Twitter($config['CONSUMER_KEY'], $config['CONSUMER_SECRET'], 
			$session->get('oauth_token'), $session->get('oauth_token_secret'));
		
		$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

		$session->set('twitter_token', $access_token);
		
		/* Remove no longer needed request tokens */
		$session->delete('oauth_token', 'oauth_token_secret');
		
		header('Location: ' . (empty($config['ALLOW_URL']) ? url::site('/', 'http') : $config['ALLOW_URL'] ));
	}
	
}