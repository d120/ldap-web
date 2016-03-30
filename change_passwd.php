<?php

include ".init.php";
require_bind_user_basicauth();
include("header.php");

$editDN = $boundUserDN;
if (isset($_GET["modifyUser"])) $editDN = get_user_dn($_GET["modifyUser"]);
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


  <form action="change_passwd.php?<?=E($_SERVER["QUERY_STRING"])?>" method="post">
<input type="hidden" name="csrftoken" value="<?=E($_COOKIE["csrftoken"])?>">
<div class="form-group">
<label for="username">Username</label>
<input type="text" class="form-control" name="userDN" id="username" value="<?=E($editDN)?>">
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
</div>

