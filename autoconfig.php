<?php
header("Content-Type: text/xml");
include ("/var/www/html/usermgmt/.init.php");

ldap_bind($ds, $serviceBindDN, $serviceBindPassword);

$searchFor = ldap_escape(urldecode($_GET["emailaddress"]), null, LDAP_ESCAPE_FILTER);

$sr = ldap_search($ds, $peopleBase, "(mail=$searchFor)", [ "uid","displayName","mobile","homePhone" ]);
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
