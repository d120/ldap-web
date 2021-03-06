<?php

/**
 * Echos a line with needed \r\n at the end.
 * $line The line that gets echoed.
 */
function vcard_binary_print($line = '') {
	if (strlen($line) > 75) {
		echo substr($line, 0, 75) . "\r\n";
		$line = substr($line, 75);
		foreach (str_split($line, 74) as $l) {
			echo " ". $l . "\r\n";
		}
	} else {
		echo $line . "\r\n";
	}
}

function vcard_print($line = '') {
	echo $line . "\r\n";
}

/**
 * Echos a line that is formatted with sprintf. All parameters get passed to sprintf.
 */
function vcard_printf(/* $format, ...$args */) {
	$line = call_user_func_array("sprintf", func_get_args());
	vcard_print($line);
}

function vcard_field($obj, $key) {
	$key = strtolower($key);
	if (in_array($key, $obj)) {
		if ($obj[$key]["count"] > 0) {
			$value = $obj[$key][0];
			return $value;
		}
	}
	return false;
}

/**
 * Prints a complete VCARD for the given userobject.
 * $user The user object, that should be converted to VCARD
 */
function vcard_print_person($user) {
	// Get data from user object
	// MUST exist
	$firstname = vcard_field($user, "givenname");
	$lastname = vcard_field($user, "sn");
	// MAY exist
	$mobile = vcard_field($user, "mobile");
	$homephone = vcard_field($user, "homephone");
	$email = vcard_field($user, "mail");
	$birthyear = vcard_field($user, "birthyear");
	$birthmonth = vcard_field($user, "birthmonth");
	$birthday = vcard_field($user, "birthday");
	$photo = vcard_field($user, "jpegphoto");

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
	if ($photo)
		vcard_binary_print("PHOTO;ENCODING=b;TYPE=JPEG:". base64_encode($photo));
	vcard_print("END:VCARD");
	vcard_print();
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
