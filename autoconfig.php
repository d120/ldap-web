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
header("Content-Type: text/xml");
include ("/var/www/html/usermgmt/.init.php");

ldap_bind($ds, $serviceBindDN, $serviceBindPassword);

$searchFor = ldap_escape(urldecode($_GET["emailaddress"]), null, LDAP_ESCAPE_FILTER);
if (strstr($searchFor, '@')) $searchFor = substr($searchFor, 0, strpos($searchFor, '@'));

$sr = ldap_search($ds, $peopleBase, "(mailAlias=$searchFor)", [ "uid","displayName","mobile","homePhone" ]);
$users = ldap_get_entries($ds, $sr);

$username = "FEHLER_BENUTZER_NICHT_GEFUNDEN";
if ($users["count"] == 1) {
  $username = $users[0]["uid"][0];
}

?><?xml version="1.0" encoding="UTF-8"?>

<clientConfig version="1.1">
    <emailProvider id="mail.d120.de">
        <domain>d120.de</domain>
        <displayName>Fachschaft Informatik</displayName>
        <displayShortName>d120.de</displayShortName>
        <incomingServer type="imap">
            <hostname>mail.d120.de</hostname>
            <port>993</port>
            <socketType>SSL</socketType>
            <authentication>password-cleartext</authentication>
            <username><?= $username ?></username>
        </incomingServer>
        <outgoingServer type="smtp">
            <hostname>mail.d120.de</hostname>
            <port>587</port>
            <socketType>STARTTLS</socketType>
            <authentication>password-cleartext</authentication>
            <username><?= $username ?></username>
        </outgoingServer>
        <documentation url="https://www.fachschaft.informatik.tu-darmstadt.de/trac/fs/wiki/Anleitungen/E-Mail">
            <descr lang="de">Fachschaftsserver im Mailclient konfigurieren</descr>
        </documentation>
    </emailProvider>
</clientConfig>
