<?php
/**
 *  ldap-web
 *  Copyright (C) 2016  Max Weller, Johannes Lauinger
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
require_bind_user_basicauth();

$isAdmin = is_group_member($boundUserDN, "cn=fss,$groupBase");
$editDN = $boundUserDN;
if (isset($_GET["modifyUser"])) $editDN = get_user_dn($_GET["modifyUser"]);
include("header.php");
?>

<h3>SSH Public Key hinzufügen</h3>
<?php
function add_pubkey($editDN) {
   global $ds;
   require_csrftoken();
   $newkey = trim(str_replace(array("\r", "\n"), '', $_POST["pubkey"]));

   if (strlen($newkey) < 9) return "Das ist zu kurz für einen Key!";
   if (substr($newkey, 0, 4) !== "ssh-" && substr($newkey, 0, 6) !== "ecdsa-") return "Das sieht nicht nach einem SSH Public Key aus.";
   if (strpos($newkey, ' ') === false) return "Das sieht nicht nach einem SSH Public Key aus.";

   $ok = ldap_mod_add($ds, $editDN, array("sshPublicKey" => $newkey));
   if ($ok) return TRUE; else return "LDAP-Fehler";
}

if ($_POST["add_pubkey"] && $_POST["userDN"] == $editDN) {
   $ok = add_pubkey($editDN);
   if ($ok === true) {
     echo "<div class='alert alert-success'>SSH-Key hinzugefügt</div>";
   } else {
     echo "<div class='alert alert-danger'><h4>Fehler</h4>$ok</div>";
   }
}
?>

<form action="manage_pubkeys.php?<?=E($_SERVER["QUERY_STRING"])?>" method="post" name="ldapAccountMgrAddPubkey" autocomplete="off">
  <input type="hidden" name="csrftoken" value="<?=E($_COOKIE["csrftoken"])?>">
  <div class="form-group">
    <label for="userDN">Username</label>
    <input type="text" class="form-control" name="userDN" id="userDN" value="<?=E($editDN)?>">
  </div>
  <div class="form-group">
    <label for="pubkey">Neuer SSH Public Key (wird hinzugefügt)</label>
    <textarea class="form-control" name="pubkey" id="pubkey" placeholder="Beispiel: ssh-ed25519 AAAA... wesen@pc"></textarea>
  </div>
  <input type="submit" class="btn btn-primary" name="add_pubkey" value="Public Key hinzufügen">
</form>


<div style="padding-top: 1rem;"></div>
<h3>Vorhandene SSH Public Keys verwalten</h3>
<?php
function del_pubkey($editDN) {
   global $ds;
   require_csrftoken();
   $delkey = $_POST["pubkey"];

   $ok = ldap_mod_del($ds, $editDN, array("sshPublicKey" => $delkey));
   if ($ok) return TRUE; else return "LDAP-Fehler: ".ldap_error($ds);
}

if ($_POST["del_pubkey"] && $_POST["userDN"] == $editDN) {
   $ok = del_pubkey($editDN);
   if ($ok === true) {
     echo "<div class='alert alert-success'>SSH-Key entfernt</div>";
   } else {
     echo "<div class='alert alert-danger'><h4>Fehler</h4>$ok</div>";
   }
}

$sr = ldap_read($ds, $editDN, "(objectclass=*)");
$entry = ldap_first_entry($ds, $sr);
$data = ldap_get_attributes($ds, $entry);
$pubkeys = $data["sshPublicKey"];
array_shift($pubkeys);
?>

<table class=table>
  <tr>
    <th>Public Key</th>
    <th>Aktion</th>
  </tr>
<?php foreach($pubkeys as $pubkey): ?>
  <tr>
    <td class="breakall"><?= E($pubkey) ?></td>
    <td>
      <form action="manage_pubkeys.php?<?=E($_SERVER["QUERY_STRING"])?>" method="post" name="ldapAccountMgrDelPubkey" autocomplete="off">
      <input type="hidden" name="csrftoken" value="<?=E($_COOKIE["csrftoken"])?>">
      <input type="hidden" name="userDN" id="userDN" value="<?=E($editDN)?>">
      <input type="hidden" name="pubkey" value="<?=E($pubkey)?>" />
      <input type="submit" class="btn btn-primary" name="del_pubkey" value="Löschen">
      </form>
    </td>
  </tr>
<?php endforeach ?>
</table>

<style>
  td.breakall { word-break: break-all; white-space: pre-wrap; font: 10pt monospace; }
</style>

