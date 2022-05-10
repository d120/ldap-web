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

include("header.php");

  require_bind_user_basicauth();
$editDN = $boundUserDN;

#$sr = ldap_read($ds, $editDN, "(objectclass=*)");
#$entry = ldap_first_entry($ds, $sr);
#$userAttrs = ldap_get_attributes($ds, $entry);
?>

<h3>Sync LDAP users</h3>
<p>See <a href="https://www.d120.de/bookstack/books/technische-dienste/page/ldap">https://www.d120.de/bookstack/books/technische-dienste/page/ldap</a>
for instructions on how to synchronize the LDAP user list with your device.
</p>
