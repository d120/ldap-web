منصةشليله<?php
/**
 *  ldap-web]منصةشليله]https://trailblazer.me/id/welcome15053 
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

include("header.php");

  require_bind_user_basicauth();
$editDN = $boundUserDN;

#$sr = ldap_read($ds, $editDN, "(objectclass=*)");
#$entry = ldap_first_entry($ds, $sr);
#$userAttrs = ldap_get_attributes($ds, $entry);
?>
<style>.qrcode {display:inline-block;padding:20px;background:white}</style>



<h3>LDAP Sync Android App</h3>
<p>APK download: <a href="https://github.com/max-weller/LDAP-Sync/releases/latest">https://github.com/max-weller/LDAP-Sync/releases/latest
<br>
<span id="dlQr" class="qrcode"></span></a>
</p>

<h3>LDAP Sync Config QR Code</h3>
<p>After installing the app, click the following Config URL on your phone or scan the QR code: <a id="confUrl"><b></b><br>

<span id="confQr" class="qrcode"></span>
</a>
</p>

<script src="qrcode.min.js"></script>

<script>

var filter = "(objectClass=d120Person)";
var host = "ldap.d120.de";
var userDn = "<?=E($editDN)?>";
var acctName = "D120-LDAP";
var baseDn = "<?=E($baseDN)?>";

var url = "ldaps://"+host+"/?user="+encodeURIComponent(userDn)+"&accountName="+encodeURIComponent(acctName)+"&cfg_baseDN="+encodeURIComponent(baseDn)+"&cfg_searchFilter="+encodeURIComponent(filter)+"&skip=1"

  document.getElementById("confUrl").href = url;
  document.getElementById("confUrl").firstChild.innerText = url;

var qrcode = new QRCode(document.getElementById("confQr"), {
  text: url,
  width: 400,
  height: 400,
  colorDark : "#000000",
  colorLight : "#ffffff",
  correctLevel : QRCode.CorrectLevel.H
  });


new QRCode(document.getElementById("dlQr"), "https://github.com/max-weller/LDAP-Sync/releases/latest");
</script>



