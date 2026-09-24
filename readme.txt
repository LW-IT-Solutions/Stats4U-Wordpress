=== Stats4U ===
Contributors: lwitsolutions
Tags: counter, hit counter, visitor counter, statistics, privacy
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A visitor counter as an image. No script, no cookie, one request.

== Description ==

The plugin puts a Stats4U visitor counter on your site as a single `<img>`.
Nothing else.

* **One request.** 797 bytes median over 580 designs, over the wire with gzip
  (measured on 2026-09-06). Check it yourself at https://www.stats4u.net/weight -
  the curl command is right there.
* **No cookie.** No Set-Cookie header in any response. The image that is
  served IS the counting.
* **No script.** So it also counts for readers who turn scripts off.
* **No account.** Get a counter number at stats4u.net, type it in, done.
* **Dark mode** that follows the visitor's own setting.
* **Your statistics** are public at stats4u.net/live/ plus your counter number,
  or private if you set it so at stats4u.net.

A ready-made paragraph for your privacy policy is at
https://www.stats4u.net/privacy-embed - tailored to your counter number, in
19 languages.

The plugin interface is available in 19 languages: English, Arabic, Bulgarian,
Chinese (Simplified), Czech, Dutch, French, German, Indonesian, Italian,
Japanese, Korean, Polish, Portuguese (Portugal), Romanian, Russian, Spanish,
Turkish and Ukrainian - the same languages as stats4u.net. A site in another
variant of one of these languages (for example de_AT, es_MX or pt_BR) gets
the nearest one.

== External services ==

This plugin connects to Stats4U (https://www.stats4u.net/), the visitor
counter service it is made for. Without it, the plugin does nothing.

What is sent and when:

* On every page view of your site that shows the counter (shortcode or
  footer), the visitor's browser loads the counter image from
  `https://www.stats4u.net/c/<counter number>-<design>.png`. As with any image
  from another server, this request carries the visitor's IP address, the
  browser's user agent and the Referer header as the browser's referrer policy
  allows (usually the address of your site), plus the counter number and the
  design settings in the image address. Stats4U counts the
  visit from this request. No cookie is set and nothing is read from the
  visitor's device.
* On the plugin's settings page in your admin area, your own browser loads the
  same image once as a preview, marked "display only" (rl=1) so that it is not
  counted.
* Your WordPress server itself never contacts stats4u.net.

If you tick "link the counter to its public statistics page" (off by
default), the image is wrapped in a link to `https://www.stats4u.net/live/<counter number>`.

The service is provided by LW IT Solutions Company Lukas Wójcik, Łódź, Poland:

* Terms of use: https://www.stats4u.net/terms
* Privacy policy: https://www.stats4u.net/privacy
* Paragraph for your own privacy policy: https://www.stats4u.net/privacy-embed

== Installation ==

1. Install and activate the plugin.
2. Under *Settings -> Stats4U*, enter your counter number.
   You can get one at https://www.stats4u.net/ without signing up.
3. Either tick "show automatically in the footer" or put the shortcode
   `[stats4u]` wherever you like.

== Frequently Asked Questions ==

= Does the plugin send anything to stats4u.net while I am in the admin area? =

Your server does not. Your browser loads the preview image on the settings
page, marked so that it is not counted.

= Do I need a cookie banner? =

The image sets no cookie and reads nothing from the device. Which duties apply
to your site is a question for your jurisdiction; what actually happens is
described at https://www.stats4u.net/privacy-embed.

= What happens if stats4u.net is unreachable? =

Then one image is missing from your page, and nothing else. An `<img>` does
not block page rendering.

= Why is the counter not a link? =

Because a plugin should not put links on your site that you did not ask for.
Tick "link the counter to its public statistics page" in the settings if you
want visitors to be able to click through to your statistics.

= What are "shape" and "palette"? =

They only apply to design 950, the adjustable counter. Pick them in the
creator at stats4u.net and copy the names out of the image address. There are
far too many to list here, and a list here would go stale.

= Does deleting the plugin delete my counter? =

No. Deleting the plugin removes its single option from your database and
nothing else. The counter belongs to you and keeps its history - your numbers
should not disappear because you tried a plugin and removed it again.

== Screenshots ==

1. The settings page: counter number, design, dark mode, footer and link options, with a live preview.
2. The counter in the footer of a site.

== Changelog ==

= 1.3.0 =
* 16 more interface languages, 19 in total - the same as stats4u.net.
* Sites in a regional variant (de_AT, es_MX, pt_BR, fr_CA ...) get the
  nearest bundled translation instead of English.
* Findings of Plugin Check 2.1.0 fixed: output of the settings texts now
  escaped at the echo, a prefixed variable in uninstall.php, plugin name in
  readme and header the same.

= 1.2.0 =
* The link from the counter to its statistics page is now an option, off by
  default. Until 1.1.0 every counter was a link.
* The preview on the settings page no longer counts as a visit.
* All output escaped with wp_kses(); links in the settings texts built with
  esc_url().
* Readme: section on the external service, current measurements.

= 1.1.0 =
* Shape and palette can now be set. They were in the settings but had no
  field, so design 950 stayed on "pill" and "mint" on every site.
* Interface strings are English now, with German and Polish translations
  bundled. Until 1.0.0 the source strings were German, so WordPress showed
  German on every blog - including English and Polish ones.
* Both were found by installing the plugin in a real WordPress for the first
  time, not by reading the code.

= 1.0.0 =
* First release: shortcode, footer, dark mode, settings page.

== Upgrade Notice ==

= 1.3.0 =
The settings page now speaks 19 languages.

= 1.2.0 =
The counter is no longer a link unless you switch that on in the settings.
