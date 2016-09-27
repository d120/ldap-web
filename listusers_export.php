<?php

/**
 * Echos a line with needed \r\n at the end.
 * $line The line that gets echoed.
 */
function vcard_print($line) {
	echo $line . "\r\n";
}

/**
 * Echos a line that is formatted with sprintf. All parameters get passed to sprintf.
 */
function vcard_printf(/* $format, ...$args */) {
	$line = call_user_func_array("sprintf", func_get_args());
	echo vcard_print($line);
}

function vcard_E($str) {
	return html_entity_decode(E($str));
}

/**
 * Prints a complete VCARD for the given userobject.
 * $user The user object, that should be converted to VCARD
 */
function vcard_print_person($user) {
	// Get data from user object
	$nameparts = explode(" ", vcard_E($user["displayname"]));

	$firstname = implode(" ", array_slice($nameparts, 0, -1));
	$lastname = end($nameparts);
	$mobile = vcard_E($user["mobile"]);
	$homephone = vcard_E($user["homephone"]);
	$email = vcard_E($user["uid"]) ."@d120.de";

	// Print vcard for this user
	vcard_print("BEGIN:VCARD");
	vcard_print("VERSION:3.0");
	vcard_printf("FN:%s %s", $firstname, $lastname);
	vcard_printf("N:%s;%s;;;", $lastname, $firstname);
	vcard_printf("EMAIL;TYPE=Fachschaft:%s", $email);
	if ($mobile)
		vcard_printf("TEL;TYPE=cell:%s", $mobile);
	if ($homephone)
		vcard_printf("TEL;TYPE=home:%s", $homephone);
	vcard_print("END:VCARD");
}


if ($_GET['export'] == "vcf") {
	// you're ready to export some cool stuff!

	// Clean content already sent...
	ob_clean();

	// Set content type so the output gets recognized as vcard
	header("Content-Type: text/vcard");
	$filename = isset($_GET["group"]) && $_GET["group"] ? $_GET["group"] . ".vcf" : "contacts.vcf";
	header("Content-Disposition: attachment; filename=". $filename);

	// foreach user print its vcard data...
	foreach ($users as $user) {
		vcard_print_person($user);
	}

	// Exit script early so no html code gets echoed.
	exit;

}