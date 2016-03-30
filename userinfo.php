<?php
require ".init.php";
require_bind_user_basicauth();

include("header.php");

echo "<h3>Group Membership</h3>";
$sr = ldap_search($ds, $groupBase, "(member=$boundUserDN)", ["cn"]);
$groups = ldap_get_entries($ds, $sr);
foreach($groups as $group) {
  if (is_array($group))
    echo "<li>".$group["cn"][0]."</li>";
}
//var_dump($groups);

echo "<h3>General Information</h3>";
$sr = ldap_read($ds, $boundUserDN, "(objectclass=*)");
$entry = ldap_first_entry($ds, $sr);
$data = ldap_get_attributes($ds, $entry);
echo "<table class=table>";
foreach($data as $k=>$v) {
  if(!is_array($v))continue;
  echo "<tr><td>".htmlentities($k)."</td><td><ul>";
  array_shift($v);
  foreach($v as $l) echo "<li>".htmlentities($l)."</li>";
  echo "</ul></td></tr>\n";
}
echo "</table>";
?>
<style>
table li{ word-break: break-all; white-space: pre-wrap; font: 10pt monospace; }
</style>


