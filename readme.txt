=== YourStoryz ===
Contributors: yourstoryz, concept7
Tags: api, freemium
Requires at least: 6.4
Requires PHP: 8.3
Tested up to: 7.1
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
The YourStoryz plugin connects your WordPress site to your YourStoryz account.

* Settings page to store your API token and test the connection.
* Choose the source: a company and, optionally, a department.
* Preview page that lists the video stories of the selected source (read-only).

On uninstall the plugin removes its token, source setting and cached data.

== Installation ==
1. Upload the `yourstoryz-plugin` folder to `/wp-content/plugins/`.
2. Activate the plugin through the \'Plugins\' menu in WordPress.
3. Configure your API key and settings from the YourStoryz menu.

== Frequently Asked Questions ==
= How do I get an API key? =
Follow the instructions on the plugin settings page to obtain and enter your API key.

= Why is my API key validation failing? =
Ensure the key is correct and has required permissions.

== Third party integrations ==
This plugins relies on the Yourstoryz API to fetch your personal content. You need an API key to use this plugin.
The API key can be obtained by creating an account on the YourStoryz platform. You can sign up at https://dashboard.yourstoryz.com/en/register or via the YourStoryz app available on iOS and Android.
The terms and conditions for the API can be found at https://yourstoryz.com/wp-content/uploads/2023/12/yourstoryz-termsconditions-apple-eula-2022.pdf
The privacy policy can be found at https://yourstoryz.com/privacy-policy-cookies/

== Changelog ==
= 1.0 =
Initial release.
= 1.1 =
Small bugfix and add support for markdown content.
= 1.2 =
Small bugfix
= 1.3 =
Small bugfix
= 1.3.1 =
Add horizontal/vertical styling for video embed
= 1.3.2 =
Typo fix
= 2.0.0 (in development) =
Rewrite on top of the YourStoryz PHP SDK. New settings page with token and connection test, company and department selection, and a read-only preview of video stories. Requires PHP 8.3 and WordPress 6.4. Adds uninstall cleanup.
