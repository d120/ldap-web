<?php
/**
 *  ldap-web
 *  Copyright (C) 2016 - 2019 Max Weller
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
$isAdmin = is_group_member($boundUserDN, "cn=fss,$groupBase");
$formActionEncoded = E($_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"]);


if (isset($_GET["user"])) {
  $editDN = "uid=$_GET[user]," . $peopleBase;
  $editAllowed = $isAdmin;
} else {
  $editDN = $boundUserDN;
  $editAllowed = true;
}

include("header.php");


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
    if (! empty($_POST["pgpCertID"]) && !empty($_POST["pgpKey"])) {
      $obj["pgpCertID"]=$_POST["pgpCertID"];
      $obj["pgpKey"]=$_POST["pgpKey"];
    }
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
      $ok = ldap_modify($ds, $editDN, $obj);
      if ($ok) echo "Änderungen gespeichert. <script>xxxsetTimeout(function(){location=location;},900);</script>";
      else echo "ERROR: ".ldap_error($ds);
      exit;
  }


  $sr = ldap_read($ds, $editDN, "(objectclass=*)");
  $entry = ldap_first_entry($ds, $sr);
  $data = ldap_get_attributes($ds, $entry);

  echo "<div class=row>\n<div class=col-md-6>";  //------columns -------
  echo "<h3>Group Membership</h3><ul>";

  $sr = ldap_search($ds, $groupBase, "(member=$editDN)", ["cn"]);
  $groups = ldap_get_entries($ds, $sr);
  foreach($groups as $group) {
      if (is_array($group)) {
          $cn = E($group["cn"][0]);
          echo "<li><form action='groupmod.php' method='post' onsubmit='return confirm(\"".E($editDN)."\\n\\nRemove user from group $cn?\")'>";
          if ($isAdmin) echo "<input type='hidden' name='group' value='$cn'><input type='hidden' name='member_remove' value='".E($editDN)."'><input type='submit' value='x' class='btn btn-xs btn-danger'><input type='hidden' name='csrftoken' value='".E($_COOKIE["csrftoken"])."'> ";
          echo "<a href='listusers.php?group=$cn'>$cn</a></form></li>";
      }
  }
  echo "</ul>\n";
  if ($isAdmin) {
    echo "<form action='groupmod.php' method='post'><input type='hidden' name='csrftoken' value='".E($_COOKIE["csrftoken"])."'>\n";
    echo "<select name='group' style='color:black;background:white'><option></option>\n";
    foreach(get_group_list() as $cn) echo "<option>".E($cn)."</option>";
    echo "</select>\n<input type='hidden' name='member_add' value='".E($editDN)."'><input type='submit' value='+' class='btn btn-sm btn-success'><input type='hidden' name='csrftoken' value='".E($_COOKIE["csrftoken"])."'></form>\n";
  }

  echo "</div>\n<div class=col-md-6>";  //------columns -------
  if ($editAllowed) {
    echo "<h3>Phone Numbers</h3>";
    echo "<form action='$formActionEncoded' method='post' class='form' enctype='multipart/form-data'>\n";
    echo '<input type="hidden" name="csrftoken" value="'.E($_COOKIE["csrftoken"]).'">';
  
    echo "<div class='form-group'><label for='mobile_phone'>Mobile phone number</label>";
    echo "<input type='text' class='form-control' id='mobile_phone' name='mobile_phone' id='mobile_phone' value='".E($data['mobile'])."' pattern='\\+?[0-9 ()]+'></div>\n";
    echo "<div class='form-group'><label for='mobile_phone'>Home phone number</label>";
    echo "<input type='text' class='form-control' id='home_phone' name='home_phone' value='".E($data['homePhone'])."' pattern='\\+?[0-9 )(]+'></div>\n";
    echo "<div class='form-group'><label for='login_shell'>Login Shell</label>";
    echo "<input type='text' class='form-control' id='login_shell' name='login_shell' value='".E($data['loginShell'])."'></div>\n";
  
  
    $birthdate = sprintf('%04d-%02d-%02d', $data['birthyear'][0] , $data['birthmonth'][0] , $data['birthday'][0]);
  
    echo "<div class='form-group'><label for='birthdate'>Birth Date</label>";
    echo "<input type='date' class='form-control' id='birthdate' name='birthdate' value='".E($birthdate)."' ></div>\n";
  
    echo "<div class='form-group'><label for='pgpKey'>PGP key</label>";
    echo "<textarea class='form-control' id='pgpKey' name='pgpKey' value='' placeholder='-----BEGIN PGP PUBLIC KEY BLOCK   ....' onblur='checkKey(this)'></textarea><input type='hidden' name='pgpCertID' id='pgpCertID' value=''><p class='help-block' id='pgpKeyInfo'></p></div>\n";
  
    echo "<div class='form-group'><label for='jpegPhoto'>Profile Image</label>";
    echo "<input type='file' class='' id='jpegPhoto' name='jpegPhoto'></div>\n";
  
  
    echo "<div class='form-group'>";
    echo "<input type='submit' name='submit_phone' value='Änderungen speichern' class='btn btn-primary'></div>\n";
  
    echo "</form>";
  }
  echo "</div></div>\n";  //------columns -------

  echo "<h3>Detailed Account Information</h3>\n";
  $shortfields = ['uidNumber', 'gidNumber', 'uid', 'givenName', 'sn', 'displayName', 'cn', 'birthday', 'birthmonth', 'birthyear'];
  echo "<table class=table><tr>\n";
  foreach($shortfields as $k) echo '<th>'.$k.'</th>';
  echo '</tr><tr>';
  foreach($shortfields as $k) echo '<td>'.E($data[$k]).'</td>';
  echo "</table>\n";

  print_attrs_table($data, $shortfields);


  $sr = ldap_read($ds, $editDN, "(objectclass=*)", ["modifyTimestamp","modifiersName","createTimestamp","creatorsName"]);
  $entry = ldap_first_entry($ds, $sr);
  $oper_data = ldap_get_attributes($ds, $entry);

  #print_attrs_table($oper_data, []);
  echo "<div style='color: #999'>\n";
  echo "<p>Created ".date('r',strtotime($oper_data["createTimestamp"][0]))."  by ".E($oper_data["creatorsName"][0])."</p>";
  echo "<p>Last modified ".date('r',strtotime($oper_data["modifyTimestamp"][0]))."  by ".E($oper_data["modifiersName"][0])."</p>";
  echo "<p><a href='change_passwd.php?modifyUser=".E($data['uid'][0])."'>Change password</a></p></div>";

  ?>
<style>
table li{ word-break: break-all; white-space: pre-wrap; font: 10pt monospace; }
</style>
<script src="js/openpgp.min.js"></script>
<script>
// set the relative web worker path
function formatKeyId(keyid) {
  var k = keyid.toHex().toUpperCase();
  return k.substr(0,4)+" "+k.substr(4,4)+" "+k.substr(8,4)+" "+k.substr(12,4);
}
async function iniPgp() {
  await window.openpgp.initWorker({ path:'js/openpgp.worker.js' });
  
  console.log("init done");
  async function showKeyInfo(asciiarmoredkey, showIn) {
    console.log(asciiarmoredkey);
    showIn.innerText="Trying to parse PGP key...";
    var result = await window.openpgp.key.readArmored(asciiarmoredkey);
    console.log(result);
    var key = result.keys[0];
    if (!key) {showIn.innerText= "Failed to parse PGP key: "+result.err; return}
    console.log(key);
    var out="";
    out +="key id: "+formatKeyId(key.keyPacket.getKeyId())+"\n";
    out +="fingerprint: "+key.keyPacket.getFingerprint()+"\n";
    var expiry = await key.getExpirationTime();
    out +="created: "+key.keyPacket.created+"\n";
    out +="expires: "+expiry+"\n";
    key.users.forEach(function(x) {
      out+="* "+x.userId.userid+"\n"
    })
    
    showIn.innerText = out;
    K=key
  }
  
  var pgpKeyField=document.querySelector(".key-pgpKey .attr-value textarea");
  if (pgpKeyField) {
    var pgpKey=pgpKeyField.value
    var field=document.querySelector(".key-pgpKey .attr-value ul");
    var showIn = document.createElement("li");
    field.insertBefore(showIn, field.firstChild);
    
    if (pgpKey) await showKeyInfo(pgpKey, showIn);
  }
  
  var certIdField=document.getElementById("pgpCertID");
  var pgpKeyInfo=document.getElementById("pgpKeyInfo");
  window.checkKey=async function (el) {
    
    var asciiarmoredkey = el.value;
    if (asciiarmoredkey == "") {el.setCustomValidity(""); pgpKeyInfo.innerText="";certIdField.value="";return;}
    
    var result = await window.openpgp.key.readArmored(asciiarmoredkey);
    console.log(result);
    var key = result.keys[0];
    if (!key) {el.setCustomValidity("Failed to parse PGP key: "+result.err);pgpKeyInfo.innerText="Failed to parse PGP key: "+result.err; certIdField.value=""; return}
    
    var out="";
    out +="key id: "+formatKeyId(key.keyPacket.getKeyId())+"\n";
    out +="fingerprint: "+key.keyPacket.getFingerprint()+"\n";
    var expiry = await key.getExpirationTime();
    out +="created: "+key.keyPacket.created+"\n";
    out +="expires: "+expiry+"\n";
    key.users.forEach(function(x) {
      out+="* "+x.userId.userid+"\n"
    })
    if (expiry<new Date()) {el.setCustomValidity("PGP key is expired");pgpKeyInfo.innerText="ERROR: PGP key is expired.\n "+out; certIdField.value=""; return}
    
    el.setCustomValidity("");pgpKeyInfo.innerText="OK: valid PGP key.\n "+out; certIdField.value=formatKeyId(key.keyPacket.getKeyId());
  }
  
}
iniPgp();

</script>


