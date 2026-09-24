=== Stats4U ===
Contributors: lwitsolutions
Tags: counter, visitor counter, hit counter, statistics, consent
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A visitor counter as an image. Paste the code from stats4u.net, pick a place, and let your consent banner decide when it loads.

== Description ==

The plugin puts a Stats4U visitor counter on your site as a single `<img>`.

* **Paste what you copied.** Paste the code you got on stats4u.net - HTML,
  BBCode, Markdown, the script version or just the image address - or only the
  counter number. Design, colors, size and every other setting you chose there
  come along. No counter yet? One button takes you to the free creator on
  stats4u.net, and a button at its end brings the code back.
* **Placed right, with any theme.** Below your theme's footer (the default),
  inside it, at the end of the page, below posts and pages, or fixed in a
  corner - on all pages, only on the front page or only on single posts and
  pages. Or place it yourself with the block "Stats4U counter" or the
  shortcode `[stats4u]`.
* **Consent built in.** Choose your consent tool and the counter loads only
  after the visitor agreed. Supported: Axeptio, Borlabs Cookie 3, CCM19,
  Civic Cookie Control, Complianz, consented.eu, consentmanager, Cookie
  Compliance (Hu-manity.co), Cookie Information, Cookie-Script, CookieAdmin,
  Cookiebot, CookieConsent 3, CookieFirst, CookieHub, CookieYes (also legacy
  mode), Didomi, GDPR Cookie Compliance (Moove), iubenda, Klaro!, OneTrust /
  CookiePro, Osano, Real Cookie Banner, Termly, Usercentrics and the WP
  Consent API - or any tool that blocks scripts, with your own attributes.
  Without a consent tool it is a plain image: no script at all.
* **One request.** 797 bytes median over 580 designs, over the wire with gzip
  (measured on 2026-09-06). Check it yourself at https://www.stats4u.net/weight.
* **No cookie.** No Set-Cookie header in any response. The image that is
  served IS the counting.
* **No account.** Your counter number is all you need.
* **Size, language, dark mode, what to count.** Sharp at any size for the
  modern designs; the words in the counter in the language of your choice or
  of each page; light, dark or following the device; every page view or
  unique visitors.

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

* On every page view of your site that shows the counter, the visitor's
  browser loads the counter image from
  `https://www.stats4u.net/c/<counter number>-<design>.png`. As with any image
  from another server, this request carries the visitor's IP address, the
  browser's user agent and the Referer header as the browser's referrer policy
  allows (usually the address of your site), plus the counter number and the
  design settings in the image address. Stats4U counts the visit from this
  request. No cookie is set and nothing is read from the visitor's device.
  If you chose a consent tool, this happens only after the visitor agreed.
* On the plugin's settings page and in the block editor, your own browser
  loads the same image as a preview, marked "display only" (rl=1) so that it
  is not counted.
* If you click "Create a free counter on stats4u.net", your browser opens
  stats4u.net with the address of this settings page as a parameter
  (`wpback`), so that a button at the end of the creator can bring the code
  back. That address is only used for that button.
* Your WordPress server itself never contacts stats4u.net.

If you tick "link the counter to its public statistics page" (off by
default), the image is wrapped in a link to `https://www.stats4u.net/live/<counter number>`.

The service is provided by LW IT Solutions Company Lukas Wójcik, Łódź, Poland:

* Terms of use: https://www.stats4u.net/terms
* Privacy policy: https://www.stats4u.net/privacy
* Paragraph for your own privacy policy: https://www.stats4u.net/privacy-embed

== Installation ==

1. Install and activate the plugin.
2. Under *Settings -> Stats4U*, paste the code you got on stats4u.net - or
   click "Create a free counter on stats4u.net" and follow the creator; its
   last step brings the code back.
3. Click *Save Changes*. The counter appears below your theme's footer.
   Change the place, the look and the consent tool on the same page.

== Frequently Asked Questions ==

= Where exactly does the automatic counter appear? =

By default right below your theme's footer: behind the element that the theme
marks as its site footer (`<footer>`, `role="contentinfo"`, `site-footer`,
the footer part of a block theme, an Elementor footer ...). Tested with
Twenty Twenty-Four and -Five, Astra, GeneratePress, Hello Elementor, Kadence,
Neve, OceanWP and themes whose page body is a flex or grid container. If a
theme has no recognisable footer, the counter goes to the end of the page.
You can also choose "inside the footer", "below the content of posts and
pages", a fixed corner, or place it yourself with the block or the shortcode.

= Do I need a cookie banner? =

The image sets no cookie and reads nothing from the device. Which duties apply
to your site is a question for your jurisdiction; what actually happens is
described at https://www.stats4u.net/privacy-embed. If your site uses a
consent tool anyway, choose it under *Consent* and the counter waits for the
visitor's consent.

= My consent tool is not in the list. =

If it supports the WP Consent API, choose that. If it blocks scripts that
carry a certain attribute, choose "another tool" and enter what it wants on
such a script, for example `type="text/plain" data-category="statistics"`.

= Some tools ask me to create a service first. =

Tools that decide per service (Usercentrics, Klaro!, Real Cookie Banner,
Axeptio, consented.eu, Civic) need Stats4U as a service in their own
settings. Create it in the statistics category and enter its name under
*Category or service* - the settings page tells you which name it expects.
Until the service exists, those tools never release the counter.

= Will I lose visitors in my numbers? =

With a consent tool, visitors who decline are not counted - so yes, the
numbers will be lower than without one. Without a consent tool nothing
changes.

= How do I change the design? =

Create or change it on stats4u.net and paste the new code. The plugin keeps
your placement and consent settings; only the counter changes. Pasting just a
number keeps the design and changes only the counter.

= Does the plugin send anything to stats4u.net while I am in the admin area? =

Your server does not. Your browser loads the preview image on the settings
page and in the block editor, marked so that it is not counted.

= What happens if stats4u.net is unreachable? =

Then one image is missing from your page, and nothing else. An `<img>` does
not block page rendering.

= Why is the counter not a link? =

Because a plugin should not put links on your site that you did not ask for.
Tick "link the counter to its public statistics page" in the settings if you
want visitors to be able to click through to your statistics.

= Does deleting the plugin delete my counter? =

No. Deleting the plugin removes its single option from your database and
nothing else. The counter belongs to you and keeps its history - your numbers
should not disappear because you tried a plugin and removed it again.

== Screenshots ==

1. Your counter: paste the code from stats4u.net - or create one there and come back with a click.
2. Appearance, placement and consent on the same page.
3. The counter right below the footer of a theme.

== Changelog ==

= 1.4.0 =
* Paste the code from stats4u.net instead of typing a number: HTML, BBCode,
  Markdown, the script version, an image address or just the number. Every
  setting from the creator comes along.
* "Create a free counter on stats4u.net" - the creator's last step brings the
  code back to the settings page.
* Consent: 27 consent tools and the WP Consent API, or your own attributes.
  The counter loads only after consent; tools that can release an image
  themselves get no script at all.
* Placement: below or inside the theme's footer, end of the page, below the
  content, fixed corner, or nowhere automatically; alignment; all pages,
  front page only or single posts and pages only.
* New block "Stats4U counter".
* Size, language of the counter and what to count (page views or unique
  visitors) can be set.
* The footer is now found as its outermost element (GeneratePress wraps its
  `<footer>` in a `<div class="site-footer">`), and marked for lazy-load
  plugins to leave alone - a lazily loaded counter would only count visitors
  who scroll down.

= 1.3.1 =
* The automatic counter now sits right below your theme's footer instead of
  at the very end of the page. Themes that lay out <body> as a flex or grid
  container showed it beside the page, halfway down.

= 1.3.0 =
* 16 more interface languages, 19 in total - the same as stats4u.net.
* Sites in a regional variant (de_AT, es_MX, pt_BR, fr_CA ...) get the
  nearest bundled translation instead of English.
* Findings of Plugin Check 2.1.0 fixed.

= 1.2.0 =
* The link from the counter to its statistics page is now an option, off by
  default.
* The preview on the settings page no longer counts as a visit.
* All output escaped.

= 1.1.0 =
* Shape and palette can be set; interface strings in English with German and
  Polish translations.

= 1.0.0 =
* First release: shortcode, footer, dark mode, settings page.

== Upgrade Notice ==

= 1.4.0 =
Paste the code from stats4u.net, choose where the counter goes and let your consent tool decide when it loads. Your settings are kept.

= 1.3.1 =
The automatic counter now works with themes that lay out the page body as a flex or grid container.
