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
require ".init.php";
require_bind_user_basicauth();

include("header.php");

if (count($_POST) && $_POST["submit_phone"]) {
    if (! $_POST["mobile_phone"])  $_POST["mobile_phone"] = array();
    if (! $_POST["home_phone"])  $_POST["home_phone"] = array();
    if ($_POST["birthdate"] && ($timestamp = strtotime($_POST["birthdate"])) !== false) {
      $birthday = date('j', $timestamp);
      $birthmonth = date('n', $timestamp);
      $birthyear = date('Y', $timestamp);
    } else {
      $birthday = $birthmonth = $birthyear = array();
    }
    $ok = ldap_modify($ds, $boundUserDN, array("mobile" => $_POST["mobile_phone"], "homePhone" => $_POST["home_phone"],
                        "birthday" => $birthday, "birthmonth"=>$birthmonth, "birthyear"=>$birthyear ));
   if ($ok) echo "Änderungen gespeichert. <script>setTimeout(function(){location=location;},900);</script>";
   else echo "ERROR: ".ldap_error($ds);
   exit;
}

$sr = ldap_read($ds, $boundUserDN, "(objectclass=*)");
$entry = ldap_first_entry($ds, $sr);
$data = ldap_get_attributes($ds, $entry);

echo "<div class=row><div class=col-md-6>";  //------columns -------
echo "<h3>Group Membership</h3>";

$sr = ldap_search($ds, $groupBase, "(member=$boundUserDN)", ["cn"]);
$groups = ldap_get_entries($ds, $sr);
foreach($groups as $group) {
  if (is_array($group))
    echo "<li>".$group["cn"][0]."</li>";
}

echo "</div><div class=col-md-6>";  //------columns -------
echo "<h3>Phone Numbers</h3>";
echo "<form action='?' method='post' class='form'>";

echo "<div class='form-group'><label for='mobile_phone'>Mobile phone number</label>";
echo "<input type='text' class='form-control' id='mobile_phone' name='mobile_phone' id='mobile_phone' value='".E($data['mobile'])."' pattern='\\+?[0-9 ()]+'></div>";
echo "<div class='form-group'><label for='mobile_phone'>Home phone number</label>";
echo "<input type='text' class='form-control' id='home_phone' name='home_phone' value='".E($data['homePhone'])."' pattern='\\+?[0-9 )(]+'></div>";

$birthdate = sprintf('%04d-%02d-%02d', $data['birthyear'][0] , $data['birthmonth'][0] , $data['birthday'][0]);

echo "<div class='form-group'><label for='birthdate'>Birth Date</label>";
echo "<input type='date' class='form-control' id='birthdate' name='birthdate' value='".E($birthdate)."' ></div>";



echo "<div class='form-group'>";
echo "<input type='submit' name='submit_phone' value='Änderungen speichern' class='btn btn-primary'></div>";

echo "</form>";

echo "</div></div>";  //------columns -------

echo "<h3>Detailed Account Information</h3>";
$shortfields = ['uidNumber', 'gidNumber', 'uid', 'givenName', 'sn', 'displayName', 'cn', 'birthday', 'birthmonth', 'birthyear'];
echo "<table class=table><tr>";
foreach($shortfields as $k) echo '<th>'.$k.'</th>';
echo '</tr><tr>';
foreach($shortfields as $k) echo '<td>'.E($data[$k]).'</td>';
echo "</table>";

echo "<table class=table>";
foreach($data as $k=>$v) {
  if(!is_array($v) || array_search($k, $shortfields) !== FALSE)
    continue;
  echo "<tr><td>".htmlentities($k)."</td><td><ul>";
  array_shift($v);
  foreach($v as $l) {
    if ($k == "jpegPhoto") {
      echo "<li><img src='data:image/jpeg;base64,";
      echo base64_encode($l);
      echo "' style='width: 200px; height: 200px;' /></li>";
    }
    else
      echo "<li>".htmlentities($l)."</li>";
  }
  echo "</ul></td></tr>\n";
}
echo "</table>";
?>
<style>
table li{ word-break: break-all; white-space: pre-wrap; font: 10pt monospace; }
</style>


