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
	// MUST exist
	$firstname = vcard_E($user["givenname"]);
	$lastname = vcard_E($user["sn"]);
	// MAY exist
	$mobile = vcard_E($user["mobile"]);
	$homephone = vcard_E($user["homephone"]);
	$email = vcard_E($user["mail"]);
	$birthyear = vcard_E($user["birthyear"]);
	$birthmonth = vcard_E($user["birthmonth"]);
	$birthday = vcard_E($user["birthday"]);

	// Print vcard for this user
	vcard_print("BEGIN:VCARD");
	vcard_print("VERSION:3.0");
	vcard_printf("FN:%s %s", $firstname, $lastname);
	vcard_printf("N:%s;%s;;;", $lastname, $firstname);
	if ($email)
		vcard_printf("EMAIL;TYPE=Fachschaft:%s", $email);
	if ($mobile)
		vcard_printf("TEL;TYPE=cell:%s", $mobile);
	if ($homephone)
		vcard_printf("TEL;TYPE=home:%s", $homephone);
	if ($birthyear && $birthmonth && $birthday)
		vcard_printf("BDAY:%'02u-%'02u-%'02u", $birthyear, $birthmonth, $birthday);
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
		if(!$user['sn']) continue;
		vcard_print_person($user);
	}

	// Exit script early so no html code gets echoed.
	exit;

}