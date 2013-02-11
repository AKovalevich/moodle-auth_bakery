<?php

/**
 * @author Foundation IDEA Southeast Europe
 * @company IDEA-SEE
 * @link http://www.idebate.mk/
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package bakery
 *
 * Authentication Plugin: Bakery SSO (IDEA)
 *
 * No authentication localy at all, everything goes through a Bakery master server. User information encrypted in the Bakery CHOCOLATECHIP cookie.
 *
 * 2013-02-05  File created.
 */

if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.');
	// It must be included from a Moodle page
}

require_once ($CFG -> libdir . '/authlib.php');
require_once ($CFG -> dirroot . '/auth/bakery/includes/BakeryFunctions.php');

/**
 * Plugin for Bakery SSO authentication.
 */
class auth_plugin_bakery extends auth_plugin_base {

	/**
	 * Constructor.
	 */
	function auth_plugin_bakery() {
		$this -> authtype = 'bakery';
		$this -> config = get_config('auth/bakery');
	}

	/**
	 * Returns true if the username and password work or don't exist and false
	 * if the user exists and the password is wrong.
	 *
	 * @param string $username The username
	 * @param string $password The password
	 * @return bool Authentication success or failure.
	 */
	function user_login($username, $password) {
		return true;
	}

	/**
	 * Updates the user's password.
	 *
	 * called when the user password is updated.
	 *
	 * @param  object  $user        User table object
	 * @param  string  $newpassword Plaintext password
	 * @return boolean result
	 *
	 */
	function user_update_password($user, $newpassword) {
		$user = get_complete_user_data('id', $user -> id);
		return update_internal_user_password($user, $newpassword);
	}

	/**
	 * No local passwords needed at all.
	 *
	 * @return bool
	 */
	function prevent_local_passwords() {
		return true;
	}

	/**
	 * Deletes CHOCOLATECHIP cookie.
	 *
	 * @return bool
	 */
	function logoutpage_hook() {
		$cookieDomain = $this -> config -> cookiedomain;
		$type = 'CHOCOLATECHIP';
		setcookie($type, '', $_SERVER['REQUEST_TIME'] - 36000, '/', $cookieDomain, FALSE);
		return;
	}

	/**
	 * Function to enable SSO (it runs before user_login() is called)
	 * If a valid CHOCOLATECHIP cookie is not found, the user will be forced to the
	 * master bakery login page where have to authenticate the user.
	 *
	 * @return logged in USER
	 */
	function loginpage_hook() {
		global $CFG, $USER, $DB;
		global $key, $cookieDomain, $slaveURL, $masterURL, $defaultCountry;
		$key = $this -> config -> skey;
		$cookieDomain = $this -> config -> cookiedomain;
		$masterURL = $this -> config -> masterurl;
		$slaveURL = $this -> config -> slaveurl;
		$defaultCountry = $this -> config -> defaultcountry;
		$mdBakery['slave'] = validateCookie();
		if (!empty($mdBakery['slave'])) {
			$username = $mdBakery['slave']['name'];
			$user = authenticate_user_login($username, null);
			if ($user) {
				complete_user_login($user);
				$urltogo = $CFG -> wwwroot . '/';
				$userMail = $USER -> email;
				// If dummie change init url through edit user form
				$userInit = $USER -> idnumber;
				// Don't check for username because of user freedom for Firstname and Lastname display
				if ($userMail != $mdBakery['slave']['mail'] || $userInit != $mdBakery['slave']['init']) {
					$emptyString = " ";
					// Or just "default" string
					$user -> idnumber = $mdBakery['slave']['init'];
					$fName = ucfirst($mdBakery['slave']['name']);
					$user -> firstname = $fName;
					$user -> lastname = $emptyString;
					$user -> email = $mdBakery['slave']['mail'];
					$user -> city = $emptyString;
					$user -> country = $defaultCountry;
					$DB -> update_record('user', $user);
				}
				redirect($urltogo);
			}
		} else {
			if (isloggedin() && !isguestuser()) {
				require_logout();
			} else {
				$master_redirect = $masterURL . 'user/login?return_dest=' . urlencode($slaveURL . 'login/index.php');
				header('Location: ' . $master_redirect);
			}
		}
	}

	/**
	 * Returns true if this authentication plugin can change the user's
	 * password.
	 *
	 * @return bool
	 */
	function can_change_password() {
		return true;
	}

	/**
	 * Returns the URL for changing the user's pw, or empty if the default can
	 * be used.
	 *
	 * @return moodle_url
	 */
	function change_password_url() {
		// Not so reliable if on Bakery Master are used URL alisas (which should not), but much faster than old method, so using this! ;)
		global $USER;
		$masterURL = $this -> config -> masterurl;
		// Set protocol because of some server configuration (http:// given, but https:// needed, so maybe problem occur)
		$curMuRLpro = explode('/', $masterURL);
		$userInit = $curMuRLpro[0] . '//' . $USER -> idnumber;
		return $userInit;
	}

	/**
	 * Returns true if plugin allows resetting of internal password.
	 *
	 * @return bool
	 */
	function can_reset_password() {
		return false;
	}

	/**
	 * Prints a form for configuring this authentication plugin.
	 *
	 * This function is called from admin/auth.php, and outputs a full page with
	 * a form for configuring this plugin.
	 *
	 * @param array $page An object containing all the data for this page.
	 */
	function config_form($config, $err, $user_fields) {
		include "config.html";
	}

	/**
	 * Processes and stores configuration data for this authentication plugin.
	 */
	function process_config($config) {
		// Set to defaults if undefined
		if (!isset($config -> skey)) {
			$config -> skey = '';
		}
		if (!isset($config -> cookiedomain)) {
			$config -> cookiedomain = '';
		} else {
			if (substr($config -> cookiedomain, 0, 1) != '.') {
				// No preceding dot! Add one!
				$config -> cookiedomain = '.' . $config -> cookiedomain;
			}
			// Remove trailing slash
			$config -> cookiedomain = rtrim($config -> cookiedomain, '/');
		}
		if (!isset($config -> masterurl)) {
			$config -> masterurl = 'http://';
		} else {
			if (substr($config -> masterurl, -1) != '/') {
				// No slash at the end! Add one!
				$config -> masterurl = $config -> masterurl . '/';
			}
		}
		if (!isset($config -> slaveurl)) {
			$config -> slaveurl = 'http://';
		} else {
			if (substr($config -> slaveurl, -1) != '/') {
				// No slash at the end! Add one!
				$config -> slaveurl = $config -> slaveurl . '/';
			}
		}
		if (!isset($config -> defaultcountry)) {
			$config -> defaultcountry = 'US';
		} else {
			// Remove single quotes from begining and end if string is copied from php file (think for dummies ;) )
			$config -> defaultcountry = trim($config -> defaultcountry, "'");
		}
		// Save Settings into DB
		set_config('skey', $config -> skey, 'auth/bakery');
		set_config('cookiedomain', $config -> cookiedomain, 'auth/bakery');
		set_config('masterurl', $config -> masterurl, 'auth/bakery');
		set_config('slaveurl', $config -> slaveurl, 'auth/bakery');
		set_config('defaultcountry', $config -> defaultcountry, 'auth/bakery');
		return true;
	}
}
?>