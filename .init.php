<?php
require(".dbconfig.php");

$ds=ldap_connect($ldapHost);
ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);


$urlPath = dirname($_SERVER["PHP_SELF"]);
if (!isset($_COOKIE["csrftoken"])) {
  $_COOKIE["csrftoken"] = mt_rand(0,mt_getrandmax());
  setcookie("csrftoken", $_COOKIE["csrftoken"], 0, $urlPath);
}

function require_csrftoken() {
  if ($_POST["csrftoken"] !== $_COOKIE["csrftoken"])
    die("csrftoken is missing or invalid, please try again");
}

define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
define('CONTROLLER', basename($_SERVER["SCRIPT_FILENAME"]));
function makehash($pw) {
//   return "{SHA}" . base64_encode(sha1($pw, true));
  return "{crypt}" . crypt($pw, '$6$');
}

function E($str) {
  if (is_array($str)){unset($str['count']);$str=implode("\n",$str);}
  return htmlentities($str, ENT_QUOTES, 'utf-8');
}

function bind_user_basicauth() {
  if ($_SERVER["PHP_AUTH_USER"] && $_SERVER["PHP_AUTH_PW"]) {
    if (bind_user($_SERVER["PHP_AUTH_USER"],  $_SERVER["PHP_AUTH_PW"])) return true;
  }
  header("WWW-Authenticate: Basic realm=\"Please authenticate with your LDAP Account\"");
  header("HTTP/1.1 401 Unauthorized");
  return FALSE;
}

function require_bind_user_basicauth() {
global $ds;
  if (!bind_user_basicauth()) {
    include("header.php");
    echo "<div class='alert alert-danger'><h4>Please authenticate with your LDAP Account</h4>\n<p>".ldap_error($ds)."</p></div>";
    exit;
  }
}

function bind_user($user,$pw){
global $ds;
  $user = strtolower(trim($user));
  $userDN = get_user_dn($user);
  $ok = ldap_bind($ds, $userDN, $pw);
  if ($ok) $GLOBALS["boundUserDN"] = $userDN; else $GLOBALS["boundUserDN"] = FALSE;
  return $ok;
}

function get_user_dn($username) {
global $peopleBase;
  return "uid=$username,$peopleBase";
}

function is_group_member($userdn, $groupdn) {
global $ds;
  $result = ldap_read($ds, $groupdn, "(member={$userdn})", ['cn']);
  if ($result === FALSE) { return FALSE; };
  $entries = ldap_get_entries($ds, $result);
  return ($entries['count'] > 0);
}


