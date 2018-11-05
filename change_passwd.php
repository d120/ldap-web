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

include ".init.php";

if (isset($_GET['tok'])) {
  $token = openssl_decrypt(base64_decode($_GET['tok']), 'aes128', $serviceBindPassword);
  $info = explode(':', $token);
  if (count($info) != 2)  die("Invalid password reset token!");
  $editDN = get_user_dn($info[0]);
  bind_user($info[0], $info[1]);

} else {
  require_bind_user_basicauth();
  $editDN = $boundUserDN;
  if (isset($_GET["modifyUser"])) $editDN = get_user_dn($_GET["modifyUser"]);
}

include("header.php");

$sr = ldap_read($ds, $editDN, "(objectclass=*)");
$entry = ldap_first_entry($ds, $sr);
$userAttrs = ldap_get_attributes($ds, $entry);
?>

<h3>Change LDAP Password</h3>
<?php
function change_pw($editDN) {
  global $ds;
  require_csrftoken();
   if (preg_match('/^\s+|\s+$/', $_POST["new_pw"])==1) return "Cowardly refusing to set password beginning or ending with whitespace characters";
   if (strlen($_POST["new_pw"]) < 8) return "Das Passwort muss mindestens acht Zeichen lang sein.";
   if ($_POST["pw2"] != $_POST["new_pw"]) return "Das wiederholte Passwort muss gleich dem neuen Passwort sein.";

   $newhash = makehash($_POST["new_pw"]);echo "<!-- $old \n$new -->";

   $ok = ldap_modify($ds, $editDN, array("userPassword" => $newhash));
   if ($ok) return TRUE; else return "LDAP-Fehler";
}

if ($_POST["change_pw"] && $_POST["userDN"] == $editDN) {
   $ok = change_pw($editDN);
   if ($ok === true) {
     echo "<div class='alert alert-success'>Passwort geändert</div>";
     return;
   } else {
       echo "<div class='alert alert-danger'><h4>Fehler</h4>$ok</div>";
   }
}

?>


<form action="change_passwd.php?<?=E($_SERVER["QUERY_STRING"])?>" method="post" name="ldapAccountMgrChangePassword" autocomplete="off">
<input type="hidden" name="csrftoken" value="<?=E($_COOKIE["csrftoken"])?>">
<div class="form-group">
<label for="userDN">Username</label>
<input type="text" class="form-control"  id="userDN" value="<?=E($editDN)?>" disabled>
<input type="hidden" class="form-control" name="userDN" value="<?=E($editDN)?>">
</div>
<!--
<div class="form-group">
<label for="old_pw">Passwort</label>
<input type="password" class="form-control" name="old_pw" id="old_pw" placeholder="Altes Passwort">
</div>
-->

<div class="form-group">
<label for="new_pw">Neues Passwort</label>
<input type="password" class="form-control" name="new_pw" id="new_pw" placeholder="min. 8 Zeichen">
</div>


<div class="form-group">
<label for="pw2">Neues Passwort wiederholen</label>
<input type="password" class="form-control" name="pw2" id="pw2">
</div>



<input type="submit" class="btn btn-primary" name="change_pw" value="Passwort ändern">

</form>

<?php
if(isset($_GET['modifyUser'])) {
  if (isset($_POST['send_reset_link']) && strlen($_POST['mailadr']) > 3) {
    echo "<div class='alert alert-danger'>";

    $bytes = openssl_random_pseudo_bytes(8);
    $randpwd = bin2hex($bytes);

    $encpwd = urlencode(base64_encode(openssl_encrypt($_GET['modifyUser'] . ':' . $randpwd, 'aes128', $serviceBindPassword)));
    $newhash = makehash($randpwd);

    $ok = ldap_modify($ds, $editDN, array("userPassword" => $newhash));
    if ($ok) {
      $mailcontent = "Hallo,
um dein Passwort fuer den Account $_GET[modifyUser] zu setzen, klicke auf den folgenden Link:

https://glados.d120.de/usermgmt/change_passwd.php?tok=$encpwd
";
      $ok=mail($_POST['mailadr'], 'Passwort zuweisen', $mailcontent, 'Content-Type: text/plain;charset=utf8');
      if($ok)echo "Passwort-Reset-Link wurde an die Adresse ".E($_POST['mailadr'])." versandt.";
      else echo "Fehler beim Mailversand!";
    } else echo "LDAP-Modify failed";
    echo "</div>";
  }
?>
<h3>Passwort-Reset-Link versenden</h3>
<form action="change_passwd.php?<?=E($_SERVER["QUERY_STRING"])?>" method="post">
<p>Achtung: Das alte Passwort wird beim Versenden des Links direkt ungültig.</p>

<div class="form-group">
<label for="pw2">Senden an Mail-Adresse (<a href="javascript:" onclick="document.getElementById('mailadr').value='<?= $userAttrs["mail"][0]?>';"><?= $userAttrs["mail"][0]?></a>)</label>
<input type="email" class="form-control" name="mailadr" id="mailadr">
</div>

<input type="submit" class="btn btn-secondary" name="send_reset_link" value="Versenden">

</form>
<?php } ?>

</div>

