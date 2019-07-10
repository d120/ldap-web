<?php
/**
 *  ldap-web
 *  Copyright (C) 2016  Max Weller
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
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
function check_ldap_error($ok, $errtitle) {
  global $ds;
  if (!$ok) {
    echo "<div class='alert alert-danger'><h4>$errtitle</h4>\n<p>".ldap_error($ds)."</p></div>";
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

function imagecreatefromfile( $filename , $typename="" ) {
  if (!file_exists($filename)) {
    throw new InvalidArgumentException('File "'.$filename.'" not found.');
  }
  if ($typename=="") $typename=$filename;
  switch ( strtolower( pathinfo( $typename, PATHINFO_EXTENSION ))) {
  case 'jpeg':
  case 'jpg':
    return imagecreatefromjpeg($filename);
    break;

  case 'png':
    return imagecreatefrompng($filename);
    break;

  case 'gif':
    return imagecreatefromgif($filename);
    break;

  default:
    throw new InvalidArgumentException('File "'.$filename.'" is not valid jpg, png or gif image.');
    break;
  }
}

function print_attrs_table($data, $ignoreAttrs) {
  echo "<table class=table>\n";
  foreach($data as $k=>$v) {
    if(!is_array($v) || array_search($k, $ignoreAttrs) !== FALSE)
      continue;
    echo "<tr class='attr-row key-$k'><td>".htmlentities($k)."</td><td class='attr-value'><ul>";
    array_shift($v);
    foreach($v as $l) {
      if ($k == "jpegPhoto") {
        echo "<li><img src='data:image/jpeg;base64,";
        echo base64_encode($l);
        echo "' style='width: 200px; height: 200px;' /></li>";
      } elseif ($k == "pgpKey") {
        echo "<li><textarea style='width:80%;height:60px;' onfocus='this.select();'>".htmlentities($l)."</textarea></li>";
      } else
        echo "<li>".htmlentities($l)."</li>";
    }
    echo "</ul></td></tr>\n";
  }
  echo "</table>";

}

function get_group_list() {
  global $ds, $groupBase;
  $sr = ldap_search($ds, $groupBase, "(objectclass=*)", ["cn"]);
  $group_items = ldap_get_entries($ds, $sr);
  $groups = [];
  foreach($group_items as $group) {
    if (!$group['cn'])continue;
    $groups[] = $group["cn"][0];
  }
  natcasesort($groups);
  return $groups;
}

function random_passwd() {
  $bytes = openssl_random_pseudo_bytes(8);
  return bin2hex($bytes);
}

