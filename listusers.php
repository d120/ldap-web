<?php
require ".init.php";
require_bind_user_basicauth();

$isAdmin = is_group_member($boundUserDN, "cn=fss,$groupBase");

if ($_GET['filter']=='telefon')
    $docTitle = "Telefonliste der Fachschaft Informatik an der TU Darmstadt - ";

include("header.php");

echo "<div class=row>";

echo "<div class='col-md-9'>";
$searchBase = $peopleBase;
if (!empty($_GET["baseDN"])) {
  $searchBase = $_GET["baseDN"];
}

if (!empty($_GET["group"])) {
  echo "<h3>Members of ".htmlentities($_GET["group"])."</h3>";
  /*$sr = ldap_search($ds, $groupBase, "(cn=".$_GET["group"].")");
  $members = ldap_get_entries($ds, $sr)[0]['member'];
  natcasesort($members);
  $users = [];
  foreach($members as $member) {
    $sr2 = ldap_read($ds, $member, '(objectclass=*)');
    $users[] = ldap_get_entries($ds, $sr2)[0];
  }
  */
  $query =  "(memberOf=cn=".$_GET['group'].",$groupBase)";
} else if (!empty($_GET["query"])) {
  $query = $_GET["query"];
  
} else {
  echo "<h3>Users</h3>";
  $query =  "(objectclass=*)";
}
echo "<form action='listusers.php' method='get' class='hidden-print'><input style='width:100%;background:#33333a;color:#aaa;border:1px solid #44444b;padding:5px;' type=search name=query value='".E($query)."'></form><br>";

$sr = ldap_search($ds, $searchBase, $query, [ "uid","displayName","mobile","homePhone","loginShell","sn","givenName","mail","birthday","birthmonth","birthyear","jpegPhoto","userPassword","pgpKey" ]);
ldap_sort($ds, $sr, 'uid');
$users = ldap_get_entries($ds, $sr);

// Load any export functionality for listusers.php (does nothing per default)
require "listusers_export.php";

echo "<table class='table user-list'>";
echo "<thead><tr><th>User Name</th><th>Full Name</th><th>Email</th><th>Mobile / Home Phone</th><th></th></tr></thead>";
echo "<tbody>\n\n";
foreach($users as $u) {
  if(!$u['uid'])continue;
  if ($_GET['filter']=='telefon'){
    if (!E($u['mobile']) && !E($u['homephone'])) continue;
    $shell = E($u['loginshell']);
    if (!$shell || $shell=='/bin/false' || $shell == '/sbin/nologin') continue;
  }
  $class="";
  if (strpos(E($u['userpassword']), "account_locked") !== false) $class="account-locked";
  echo "<tr class='$class' x='".E($u['userpassword'])."'><td><a href='userinfo.php?user=".E($u['uid'])."'>".E($u['uid'])."</a></td>";
  echo "<td>".E($u['displayname'])."</td>";
  echo "<td><a href='mailto:".E($u['mail'])."'>".str_replace('tu-darmstadt.de','tu...',str_replace('fachschaft.informatik.tu-darmstadt.de','fachsch...',E($u['mail'])))."</a></td>";
  echo "<td>".E($u['mobile'])."<br>";
  echo "".E($u['homephone'])."</td>";
  echo "<td class=hidden-print>";
  echo "<a href='userinfo.php?user=".E($u['uid'])."'><img alt=View src=info.png></a> ";
  if ($isAdmin) echo "<a href='change_passwd.php?modifyUser=".E($u['uid'])."'><img alt=Edit src=wrench.png></a> ";
  if ($u['pgpkey']) echo "<a href='data:application/octet-stream;base64,".base64_encode($u['pgpkey'][0])."' download='".E($u['uid']).".asc'><img alt='PGP Key' src=css/cert.png></a> ";
  echo "</td>";
//var_dump($u);
  echo "</tr>\n";
}
echo "</tbody>";
echo "</table>";
?>
<style>
table li{ word-break: break-all; white-space: pre-wrap; font: 10pt monospace; }
.list-group-item { padding-top: 5px; padding-bottom: 5px; }
@media print {
    table.user-list td { padding: 0 8px!important; border: 0 none!important; }
    table.user-list tr:nth-child(odd) td { background: #eeffee !important; }
    table.user-list { font-size: 10pt; font-family: Times; }
    h3 { font-size:14pt; font-family: Times; }
    a::after{display:none}
}
.account-locked,.account-locked a {color:#777; text-decoration: line-through;}
</style>

<?php
echo "</div>";

echo "<div class='col-md-3 hidden-print'>";
echo "<h3>Quick actions</h3><div class=list-group>\n";
echo "  <a href='listusers.php?filter=telefon&group=fachschaft' class=list-group-item>Telefonliste</a>\n";
$query = !empty($_SERVER["QUERY_STRING"]) ? htmlspecialchars($_SERVER["QUERY_STRING"]) . "&" : "";
echo "  <a href='listusers.php?{$query}export=vcf' class='list-group-item' target='_blank'>VCF exportieren</a>\n";
echo "</div>";


echo "<h3>Filter by group</h3><div class=list-group>\n";
echo "  <a href=? class=list-group-item>(all)</a>\n";
$groups = get_group_list();
foreach($groups as $cn) {
    echo "  <a href='?group=$cn' class='list-group-item ".($cn==$_GET["group"]?"active":"")."'>$cn</a>\n";
}
//var_dump($groups);
echo "</div></div>";

echo "</div>";

