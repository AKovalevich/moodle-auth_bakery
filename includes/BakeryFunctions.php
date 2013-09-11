<?php

/**
 * Validates a bakery cookie.
 *
 * @param $type string default CHOCOLATECHIP (identifies user).
 * @return the validated and decrypted cookie in an array or FALSE.
 */
function validateCookie($type = 'CHOCOLATECHIP') {
	global $key, $cookieDomain, $cookie;

	if (!isset($_COOKIE[$type]) || !$key || !$cookieDomain) {
		return FALSE;
	}

	$data = base64_decode($_COOKIE[$type]);

	$signature = substr($data, 0, 64);
	$encrypted_data = substr($data, 64);

	if ($signature !== hash_hmac('sha256', $encrypted_data, $key)) {
		return FALSE;
	}

	$cookie = unserialize(bakeryMix($encrypted_data, 0));

	$valid = FALSE;
	if ($cookie['timestamp'] + 3600 >= $_SERVER['REQUEST_TIME']) {
		$valid = TRUE;
	}
	return $valid ? $cookie : $valid;
}

/**
 * Encrypt or decrypt text.
 *
 * @param $text, The text that you want to encrypt.
 * @param $crypt = 1 if you want to crypt, or 0 if you want to decrypt.
 */
function bakeryMix($text, $crypt) {

	global $key;

	$td = mcrypt_module_open('rijndael-128', '', 'ecb', '');
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

	$key = substr($key, 0, mcrypt_enc_get_key_size($td));

	mcrypt_generic_init($td, $key, $iv);

	if ($crypt) {
		// Base64 encode the encrypted text because the result may contain
		// Characters that are not stored consistently in cookies.
		$encrypted_data = base64_encode(mcrypt_generic($td, $text));
	} else {
		$encrypted_data = mdecrypt_generic($td, $text);
	}

	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	return $encrypted_data;
}

/*
 * Get a user id from the bakery init field. (NOT USED!!!)
 */
function getUserIDByBakeryInit($init) {
	$disinit = explode('/', $init);
	return $disinit[2];
}

/*
 * Destroy bakery cookies. Call this when user logs out from local slave. (NOT USED!!!)
 */
function eatCookie($type = 'CHOCOLATECHIP') {
	global $cookieDomain;
	setcookie($type, '', $_SERVER['REQUEST_TIME'] - 36000, '/', $cookieDomain, FALSE);
}
?>
