<?php

require ".init.php";
if (!isset($enableOauth2) || $enableOauth2 !== TRUE) { header("HTTP/1.1 418 I'm a teapot"); die("oauth2 support not enabled"); }

require_once('./oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

$storage = new OAuth2\Storage\Pdo($databaseConfig);

// configure the server for OpenID Connect
$config['use_openid_connect'] = true;
$config['issuer'] = 'fachschaft.informatik.tu-darmstadt.de';

// Pass a storage object or array of storage objects to the OAuth2 server class
$server = new OAuth2\Server($storage, $config);

$scope = new OAuth2\Scope(array(
	'default_scope' => 'basic',
  'supported_scopes' => array('basic','openid','profile','email',/*'address',*/'phone'),
));
$server->setScopeUtil($scope);

// configure keys
$keyStorage = new OAuth2\Storage\Memory(array('keys' => array(
    'public_key'  => $openidConnectPubkey,
    'private_key' => $openidConnectPrivkey,
)));

$server->addStorage($keyStorage, 'public_key');

// Add the "Authorization Code" grant type (this is where the oauth magic happens)
$server->addGrantType(new OAuth2\OpenID\GrantType\AuthorizationCode($storage));
$server->addGrantType(new OAuth2\GrantType\RefreshToken($storage, [ "unset_refresh_token_after_use" => false ]));

if ($_SERVER["PATH_INFO"] == "/authorize") {
	
	$request = OAuth2\Request::createFromGlobals();
	$response = new OAuth2\Response();
	
	// validate the authorize request
	if (!$server->validateAuthorizeRequest($request, $response)) {
		$response->send();
		die;
	}
	
	require_bind_user_basicauth();
	
	// display an authorization form
	if (empty($_POST)) {
		$baseHref="..";
	include("header.php");
	 
	  exit('
	<form method="post">
	  <label>Do You Authorize '.E($request->query['client_id']).'?</label><br />
	  <input type="submit" name="authorized" value="yes">
	  <input type="submit" name="authorized" value="no">
	</form>');
	}
	
	
	$is_authorized = ($_POST['authorized'] === 'yes');
	$server->handleAuthorizeRequest($request, $response, $is_authorized, $boundUserDN);
	
	$response->send();
	//https://glados.d120.de/usermgmt/oauth2.php/authorize?client_id=PyOphase&response_type=code&state=foo&scope=basic
	//INSERT INTO oauth_clients (client_id, client_secret, redirect_uri) VALUES ("PyOphase", "yieX9ihima5zieS2aiT7eapuUwee7yoo", "https://www.d120.de/ophase/");
	//curl -u PyOphase:yieX9ihima5zieS2aiT7eapuUwee7yoo https://glados.d120.de/usermgmt/oauth2.php/token -d 'grant_type=authorization_code&code=bdfa0944a650f06b7590d87a91e70b5037f6f73c'

} elseif ($_SERVER["PATH_INFO"] == "/token") {
	
	// Handle a request for an OAuth2.0 Access Token and send the response to the client
	$server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
	
} elseif ($_SERVER["PATH_INFO"] == "/userinfo") {
	
	if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
		$server->getResponse()->send();
		die;
	}
	
	$token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());
	
	ldap_bind($ds, $serviceBindDN, $serviceBindPassword);
	
	$fields=['displayName'=>'name','givenName'=>'given_name', 'sn'=>'family_name', 'uid'=>'preferred_username', 'mail'=>'email','mobile'=>'phone_number'];
	$sr = ldap_read($ds, $token['user_id'], "(objectclass=*)", array_keys($fields));
	$entry = ldap_first_entry($ds, $sr);
	$attrs = ldap_get_attributes($ds, $entry);
	$out = ['sub'=>$token['user_id']];
	
	foreach ($fields as $ldapkey => $outkey) {
		$out[$outkey] = $attrs[$ldapkey][0];
	}
	header("Content-Type: application/json");
	echo json_encode($out);

} elseif ($_SERVER["PATH_INFO"] == "/openid-configuration") {
	header("Content-Type: application/json");
	$root="https://ldap.d120.de/usermgmt";
	echo json_encode([
		"issuer" => "https://ldap.d120.de/",
		"authorization_endpoint" => "$root/oauth2.php/authorize",
		"token_endpoint" => "$root/oauth2.php/token",
		"userinfo_endpoint" => "$root/oauth2.php/userinfo",
		"jwks_uri" => "$root/oauth2_keys.json",
		"response_types_supported" => ["code","id_token","token id_token"],
		"subject_types_supported" => [ "public", "pairwise" ],
		"id_token_signing_alg_values_supported" => ["RS256"]
	]);
}

