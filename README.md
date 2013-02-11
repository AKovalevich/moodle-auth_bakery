moodle-auth_bakery
==================

SSO Authentication via a Bakery [Drupal] Master Server


Overview
--------

auth_bakery allows the implementation of a Moodle site as a Bakery slave to an existing Bakery master, where Bakery is a SSO (single sign-on) solution developed for Drupal, allowing for multiple websites to share the authentication information coming from the Bakery master.

Bakery relies on cookies, specifically 'CHOCOLATECHIP' and requires that all sites sharing the SSO are to also share a domain.

For more on Bakery, please see here:

http://drupal.org/project/bakery


Installation
------------

To install:

1. Copy the contents of the ```/bakery/``` folder to the ```<MOODLE_PATH>/auth/``` folder.
2. Login as Admin to Moodle, go to Site Administration > Plugins > Authentication and enable the Bakery SSO (IDEA) plugin.
3. Go to the plugin settings and fill in the required options.
4. Logout as Admin.


Technical Specifications
------------------------

auth_bakery relies on the standard Moodle provided hooks:

1. ```loginpage_hook()``` sends the user to the master server, checks for the CHOCOLATECHIP cookie, unpacks it and creates and/or updates the Moodle user and logs them in.
2. ```logoutpage_hook()``` destroys the CHOCOLATECHIP cookie, thus logging out the user from the master as well.


Note
----

At present, Moodle requires each user to have a value for the country field. This is represented by a 2-character ISO country code. CHOCOLATECHIP, on the other hand, does not contain this value so it can either be left blank or filled in with a default value.

The only issue with not having a value is that the Moodle edit screen will throw an exception. We have chosen to fill in a default value, and that is set with the settings screen.

Future
------

Older versions of Moodle to not automatically log out after the Master Bakery server logs out. Fix coming soon.

Licence
-------

auth_bakery is licensed under the MIT license (http://opensource.org/licenses/MIT).
