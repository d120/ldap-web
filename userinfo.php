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

if (isset($_GET["user"])) {
  $theUserDN = "uid=$_GET[user]," . $peopleBase;
} else {
  $theUserDN = $boundUserDN;
}

if (count($_POST) && $_POST["submit_phone"]) {
    if (! $_POST["mobile_phone"])  $_POST["mobile_phone"] = array();
    if (! $_POST["home_phone"])  $_POST["home_phone"] = array();
    if (! $_POST["login_shell"]) $_POST["login_shell"] = array();
    if ($_POST["birthdate"] && ($timestamp = strtotime($_POST["birthdate"])) !== false) {
      $birthday = date('j', $timestamp);
      $birthmonth = date('n', $timestamp);
      $birthyear = date('Y', $timestamp);
    } else {
      $birthday = $birthmonth = $birthyear = array();
    }
    $obj = array("mobile" => $_POST["mobile_phone"], "homePhone" => $_POST["home_phone"], "loginShell" =>$_POST["login_shell"],
                        "birthday" => $birthday, "birthmonth"=>$birthmonth, "birthyear"=>$birthyear );
    if (count($_FILES) && is_uploaded_file($_FILES['jpegPhoto']['tmp_name'])) {
        try {
            $tmpfname = tempnam(sys_get_temp_dir(), 'FOO'); // good 
            $fn = $_FILES['jpegPhoto']['tmp_name'];
            $size = getimagesize($fn);
            $ratio = $size[0]/$size[1]; // width/height
            if ($size[0]<500 && $size[1]<500) {
              $width=$size[0]; $height=$size[1];
            } elseif( $ratio > 1) {
                $width = 500; $height = 500/$ratio;
            } else {
                $width = 500*$ratio; $height = 500;
            }
            var_dump($size,$ratio,$width,$height);echo"<hr>";
            $src = imagecreatefromstring(file_get_contents($fn));
            $dst = imagecreatetruecolor($width,$height);
            imagecopyresampled($dst,$src,0,0,0,0,$width,$height,$size[0],$size[1]);
            imagedestroy($src);
            imagejpeg($dst, $tmpfname, 50);
            imagedestroy($dst);


            $obj['jpegPhoto'] = file_get_contents($tmpfname);
            unlink($tmpfname);
        } catch (Exception $e) {
            echo "Bild konnte nicht verarbeitet werden! $e";
            exit;
        }
    }
    $ok = ldap_modify($ds, $theUserDN, $obj);
    if ($ok) echo "Änderungen gespeichert. <script>xxxsetTimeout(function(){location=location;},900);</script>";
    else echo "ERROR: ".ldap_error($ds);
    exit;
}

$sr = ldap_read($ds, $theUserDN, "(objectclass=*)");
$entry = ldap_first_entry($ds, $sr);
$data = ldap_get_attributes($ds, $entry);

echo "<div class=row>\n<div class=col-md-6>";  //------columns -------
echo "<h3>Group Membership</h3><ul>";

$sr = ldap_search($ds, $groupBase, "(member=$theUserDN)", ["cn"]);
$groups = ldap_get_entries($ds, $sr);
foreach($groups as $group) {
    if (is_array($group))
        echo "<li><a href='listusers.php?group=".$group["cn"][0]."'>".$group["cn"][0]."</a></li>";
}

echo "</ul></div>\n<div class=col-md-6>";  //------columns -------
echo "<h3>Phone Numbers</h3>";
echo "<form action='".E($_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"])."' method='post' class='form' enctype='multipart/form-data'>\n";

echo "<div class='form-group'><label for='mobile_phone'>Mobile phone number</label>";
echo "<input type='text' class='form-control' id='mobile_phone' name='mobile_phone' id='mobile_phone' value='".E($data['mobile'])."' pattern='\\+?[0-9 ()]+'></div>\n";
echo "<div class='form-group'><label for='mobile_phone'>Home phone number</label>";
echo "<input type='text' class='form-control' id='home_phone' name='home_phone' value='".E($data['homePhone'])."' pattern='\\+?[0-9 )(]+'></div>\n";
echo "<div class='form-group'><label for='login_shell'>Login Shell</label>";
echo "<input type='text' class='form-control' id='login_shell' name='login_shell' value='".E($data['loginShell'])."'></div>\n";


$birthdate = sprintf('%04d-%02d-%02d', $data['birthyear'][0] , $data['birthmonth'][0] , $data['birthday'][0]);

echo "<div class='form-group'><label for='birthdate'>Birth Date</label>";
echo "<input type='date' class='form-control' id='birthdate' name='birthdate' value='".E($birthdate)."' ></div>\n";


echo "<div class='form-group'><label for='jpegPhoto'>Profile Image</label>";
echo "<input type='file' class='' id='jpegPhoto' name='jpegPhoto'></div>\n";


echo "<div class='form-group'>";
echo "<input type='submit' name='submit_phone' value='Änderungen speichern' class='btn btn-primary'></div>\n";

echo "</form>";

echo "</div></div>\n";  //------columns -------

echo "<h3>Detailed Account Information</h3>\n";
$shortfields = ['uidNumber', 'gidNumber', 'uid', 'givenName', 'sn', 'displayName', 'cn', 'birthday', 'birthmonth', 'birthyear'];
echo "<table class=table><tr>\n";
foreach($shortfields as $k) echo '<th>'.$k.'</th>';
echo '</tr><tr>';
foreach($shortfields as $k) echo '<td>'.E($data[$k]).'</td>';
echo "</table>\n";

echo "<table class=table>\n";
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


