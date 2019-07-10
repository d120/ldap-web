<?php
require ".init.php";
require_bind_user_basicauth();
include("header.php");

$sr = ldap_search($ds, $groupBase, "(cn=".$_POST["group"].")");
$groupInfo = ldap_get_entries($ds, $sr)[0];
$editDN = $groupInfo["dn"];


if (!empty($_POST["member_remove"]) && is_string($_POST["member_remove"])) {
	$memberDN = $_POST["member_remove"];
	$ok = ldap_mod_del($ds, $editDN, array("member" => $memberDN));
	check_ldap_error($ok, "Admin action failed - maybe the user isn't member of the group, or the group would be empty without this user?");
	if ($ok) echo "<div class='alert alert-success'>Benutzer aus Gruppe entfernt</div>";
}

if (!empty($_POST["member_add"]) && is_string($_POST["member_add"])) {
	$memberDN = $_POST["member_add"];
	$ok = ldap_mod_add($ds, $editDN, array("member" => $memberDN));
	check_ldap_error($ok, "Admin action failed - maybe the user is already member of the group");
	if ($ok) echo "<div class='alert alert-success'>Benutzer in Gruppe eingefügt</div>";
}


$sr = ldap_read($ds, $memberDN, "(objectclass=*)", array("uid"));
$userInfo = ldap_get_entries($ds, $sr)[0];
//var_dump($memberDN, $userInfo);


echo "<p><a class='btn btn-default' href='userinfo.php?user=".E($userInfo["uid"])."'>Go to User Info</a> <a class='btn btn-default'  href='listusers.php?group=".E($groupInfo["cn"])."'>Go to List</a></p> ";

#if ($_GET["target"] == "member") header("Location: userinfo.php?user=".);
#else header("Location: listusers.php?group=".);

