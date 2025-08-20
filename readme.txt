=== Cookie Consent for Google Tag Manager ===
Contributors: lifterlmsexpert
Tags: cookie consent, google tag manager, gtm, ga4, gdpr, privacy, analytics, consent management
Requires at least: 6.1
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Cookie Consent for Google Tag Manager helps you stay compliant with privacy regulations by managing how and when tracking scripts are loaded.

== Description ==

Cookie Consent for Google Tag Manager is a comprehensive solution for managing cookie consent and Google Tag Manager implementation on your WordPress site. The plugin helps you stay compliant with privacy regulations like GDPR by managing how and when tracking scripts are loaded.

= Key Features =

* Display a customizable cookie consent banner for GTM and GA4
* Block Google Tag Manager scripts until users give consent
* Log all consent actions (Accept, Decline, No Action) with IP/location and timestamp
* Show filterable statistics and reports in the admin dashboard
* Configure banner style, notice position, and icon placement
* Enable or disable traffic source tracking (referrer, UTM parameters)
* Lightweight and optimized for fast performance

= Privacy and GDPR =

This plugin is designed with privacy in mind:
* No tracking scripts load until consent is given
* Clear opt-in/opt-out choices for users
* Complete consent action logging
* Data export and deletion tools for GDPR compliance
* Detailed documentation of data handling

= For Developers =

* Clean, well-documented code
* WordPress coding standards compliant
* Extensible through filters and actions
* Comprehensive API documentation

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/cookie-consent-for-google-tag-manager`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'GTM Consent' in your WordPress admin to configure settings

= Quick Setup =

1. Enter your Google Tag Manager ID
2. Customize the consent banner text and appearance
3. Configure which tracking features to enable
4. Test the consent mechanism

== Frequently Asked Questions ==

= Does this plugin block Google Tag Manager by default? =
Yes, GTM is blocked until the user gives explicit consent. This helps with GDPR compliance.

= What information is stored about users? =
The plugin logs: consent choice (accept/decline), IP address (optional), timestamp, and traffic source (if enabled). All data can be exported or deleted through WordPress privacy tools.

= Is this plugin GDPR compliant? =
Yes, the plugin is designed for GDPR compliance. It blocks tracking until consent is given and provides necessary tools for data subject rights.

== Changelog ==

= 1.0.0 =
* Initial release
* Cookie consent banner with customizable appearance
* Google Tag Manager and GA4 integration
* Consent logging with IP/location tracking
* Admin dashboard with consent statistics
* Traffic source tracking capability
* GDPR compliance tools
* Comprehensive API documentation

== Upgrade Notice ==

= 1.0.0 =
Initial release of Cookie Consent for Google Tag Manager

== Privacy ==

This plugin is designed to help website owners comply with privacy regulations:

* Stores user consent choices
* Logs IP addresses (optional)
* Tracks traffic sources (optional)
* Provides data export tools
* Includes data deletion capabilities

For full details, visit our [Privacy Policy](https://lifterlmsexpert.com/privacy-policy/).

== License ==

This plugin is licensed under the GPL v2 or later.
