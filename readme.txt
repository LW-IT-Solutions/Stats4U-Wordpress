=== Stats4U ===
Contributors: lwitsolutions
Tags: counter, visitor counter, hit counter, statistics, consent
Requires at least: 5.8
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a free visitor counter to WordPress with a block, shortcode, or automatic placement. Works with supported consent tools.

== Description ==

Add a free visitor counter to your WordPress site with the Stats4U block,
the `[stats4u]` shortcode, or automatic placement below your footer. The
plugin loads the official Stats4U script with the design and settings you
choose. See the [Stats4U WordPress setup guide](https://www.stats4u.net/en/guide/wordpress).

* **Paste what you copied.** Paste the code you got on stats4u.net - HTML,
  BBCode, Markdown, the script code or just the image address - or only the
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
* **Statistics worth reading.** Every count carries the page and where the
  visitor came from; on top come time on page, scroll depth and who is online
  right now. You read it all on stats4u.net.
* **Light.** The script is 12.6 kB over the wire (Brotli, measured on
  2026-09-26) and cached for an hour; each page view adds the counter image
  (about half a kilobyte) and one tiny settings request.
* **No cookie.** The script sets none and stores nothing on the device.
* **No account.** Your counter number is all you need.
* **Size, language, dark mode, what to count.** Sharp at any size for the
  modern designs; the words in the counter in the language of your choice or
  of each page; light, dark or following the device; every page view or
  unique visitors.

A ready-made paragraph for your privacy policy is at
https://www.stats4u.net/privacy-embed - tailored to your counter number, in
19 languages.

The words in the counter come in 19 languages - the same as on stats4u.net.
The settings page is written in English and ready for translation on
translate.wordpress.org; WordPress installs the translation for your language
by itself once it is there.

== External services ==

This plugin loads the script of Stats4U (https://www.stats4u.net/), the
visitor counter service it is made for. Without it, the plugin does nothing.

Optional WooCommerce reporting is off by default. It is switched on in two
places: in the Stats4U owner settings, where you create a shop secret, and under
Settings > Stats4U shop, where you enter the counter number and that secret.
Only then does your WordPress server itself send data to Stats4U: a POST over
HTTPS to `https://www.stats4u.net/index.php?action=insights_sale&s4uid=<counter number>`,
signed with your shop secret. It contains the paid and the refunded amount in
minor currency units, the currency, the time of payment and a keyed
(pseudonymous) order reference - an HMAC of your site address and the order ID.
No customer fields and no product details are sent. A report is sent:

* when WooCommerce marks an order as paid (`woocommerce_payment_complete`,
  usually during the customer's checkout or the payment provider's
  confirmation),
* when the status of an order changes to processing or completed - also when
  you change it in the admin area,
* when an order is refunded,
* when a failed report is retried through WP-Cron (up to eight times, from
  2 minutes to 6 hours apart; WP-Cron runs during any request to your site),
* when you click "Retry failed reports" on the Stats4U shop page.

Only orders paid within the last 400 days are sent. Switching reporting on does
not send earlier orders in bulk; an order paid before that is sent only if one
of the events above happens to it later. Duplicate reports are safe. Stats4U
accepts EUR, PLN, USD, GBP, CHF, CZK, SEK, HUF and RON; an order in another
currency, or with an amount that cannot be read, is not sent and is listed on
the Stats4U shop page instead. Failed reports show a notice for administrators
on the Dashboard, the plugin list, the Stats4U settings page and the order
screens; it can be hidden and comes back only when another report fails.
Deactivating the plugin removes pending retries.

Contact and download categories and successful form submits are counted by the
Stats4U script (s4u.js) in the visitor's browser, not by the plugin; the plugin
has no code for them. They are switched on in your counter's owner settings on
stats4u.net. For Contact Form 7 the script waits for its `wpcf7mailsent` event;
other forms must call `s4u.formSuccess()` after successful server processing.
A submit alone never counts as a successful form. No form values or contact
addresses are sent.

What is sent and when:

* On every page view of your site that shows the counter, the visitor's
  browser loads `https://www.stats4u.net/s4u.js` (cached for up to an hour).
  The script then:
    * loads the counter image from `https://www.stats4u.net/index.php`
      (`action=pic`) with the counter number, the design settings, the address
      of the page, the address the visitor came from (referrer), the language
      of the page and a random number against caching. Stats4U counts the
      visit from this request.
    * asks once per page for the counter's own settings (`action=cfg`: does it
      refresh itself, count clicks, have a public statistics page) - with the
      counter number only.
    * when the visitor leaves the page or switches away from it, sends how
      many seconds the page was visible, how far down it was scrolled (in
      percent), the page address and - for a page that was loaded normally -
      its load time (`action=puls`). Stats4U sorts these into ranges and adds
      them up per day.
    * while the page is visible, sends the counter number once a minute, at
      most 120 times (`action=ping`), for the "online now" figure. This counts
      nothing.
    * sends an event (`action=event`) when a visitor clicks an element you
      marked with `data-s4u-event` - or, only if you switched this on for your
      counter on stats4u.net, any link. The event name is the link's target,
      shortened to its host or path.
    * only if you switched these categories on for your counter on
      stats4u.net: an event (`action=event`) when a visitor clicks a contact or
      download link, or after a form was sent successfully (see above). Form
      values and contact addresses are not sent.
    * only if the code you pasted contains `data-screen="1"` (a choice in the
      creator on stats4u.net): the screen size, the window size and the pixel
      ratio, sent together with the time on page.
  Like any request to another server, these carry the visitor's IP address
  and the browser's user agent. No cookie is set and nothing is stored on the
  visitor's device. If you chose a consent tool, none of this happens before
  the visitor agreed.
* On the plugin's settings page and in the block editor, your own browser
  loads a preview image of the counter, marked "display only" (rl=1) so that
  it is not counted.
* If you click "Create a free counter on stats4u.net", your browser opens
  stats4u.net with the address of this settings page as a parameter
  (`wpback`), so that a button at the end of the creator can bring the code
  back. That address is only used for that button.
* If you click "Request a new counter number", your browser asks
  `https://www.stats4u.net/index.php` (`action=wpneu`) for a free counter
  number, sending only the language of your admin area. Nothing is created
  there until the counter is first seen on your site.
* If you send feedback from the settings page, your browser sends your
  message to `https://www.stats4u.net/index.php` (`action=wpfeedback`),
  together with the plugin version and the language of your admin area - plus
  your email address if you enter one, and your site address and counter
  number only if you tick the box for them. The message is stored so that it
  can be answered, and stats4u.net forwards it, with the details you entered,
  to its operator through Telegram (terms: https://telegram.org/tos, privacy:
  https://telegram.org/privacy). Neither the plugin nor your browser contacts
  Telegram. Nothing is sent before you click "Send feedback".
* Your WordPress server contacts stats4u.net only with WooCommerce reporting
  switched on, as described above (paid, completed or refunded orders, WP-Cron
  retries, "Retry failed reports"). Without it, every request listed here comes
  from a browser.

The Stats4U script links the counter to its statistics page,
`https://www.stats4u.net/live/<counter number>` - as on every site that uses
the code from stats4u.net. This link comes from the service, not from the
plugin. A counter that is set to "not public" on stats4u.net links to the
Stats4U home page, `https://www.stats4u.net`, instead.

The service is provided by LW IT Solutions Company Lukas Wójcik, Łódź, Poland:

* Terms of use: https://www.stats4u.net/terms
* Privacy policy: https://www.stats4u.net/privacy
* Paragraph for your own privacy policy: https://www.stats4u.net/privacy-embed

== Installation ==

1. Install and activate the plugin.
2. Open *Settings -> Stats4U* and choose the wizard - or the custom
   configuration, if you prefer all settings at once.
3. Paste the code you got on stats4u.net, click "Create a free counter on
   stats4u.net" (its last step brings the code back), or request a new
   counter number right there.
4. Save. The counter appears below your theme's footer; change the place, the
   look and the consent tool any time.

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

= How do I put the counter into a theme template? =

Write `<?php echo do_shortcode( '[stats4u]' ); ?>` where it should appear -
in footer.php, for example. A shortcode typed into a PHP file as plain text is
not processed. Set *Where to show it* to "nowhere automatically", otherwise the
counter appears twice. The settings page shows the same line under
*Placement*.

= Why the script and not just the counter image? =

Up to version 1.4.0 the plugin used the image alone. It was lighter, but
your statistics got almost nothing from it: a browser sends only your site's
domain along with an image from another server, not the page, and nothing
about where the visitor came from or how long they stayed. The script sends
the page and the referrer with every count and adds time on page, scroll
depth and the "online now" figure.

= Do I need a cookie banner? =

The script sets no cookie and stores nothing on the device; what it sends is
listed under *External services*. Which duties apply to your site is a
question for your jurisdiction; a description for your privacy policy is at
https://www.stats4u.net/privacy-embed. If your site uses a consent tool
anyway, choose it under *Consent* and the counter waits for the visitor's
consent.

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
numbers will be lower than without one. The same happens with optimisation
plugins that hold back JavaScript until the first click or scroll: visitors
who do neither are not counted. Exclude `stats4u.net/s4u.js` there.

= My theme changes pages without a full reload. =

Then the counter counts the first page only, unless the theme lets the
Stats4U script run again for each new page - the way the navigation of
stats4u.net itself does: before swapping the page, fire the event
`s4u:seiteweg` on `document` (it closes the time on the old page), and
afterwards insert the counter's script tag again. With a consent tool,
`window.stats4uAn()` fills new placeholders after the swap; it exists only
once the visitor agreed.

= How do I change the design? =

Create or change it on stats4u.net and paste the new code - or use "Create a
free counter on stats4u.net": when you come back, the settings page shows the
new design at once, and *Save Changes* puts it on your site. The plugin keeps
your placement and consent settings; only the counter changes. Pasting just a
number keeps the design and changes only the counter.

= Does the plugin send anything to stats4u.net while I am in the admin area? =

Your server does only with WooCommerce reporting switched on: when you set an
order to processing or completed, refund an order or click "Retry failed
reports", and when WP-Cron runs a pending retry during an admin request.
Otherwise your server sends nothing. Your browser loads the preview image on
the settings page and in the block editor, marked so that it is not counted,
and contacts stats4u.net when you click "Send feedback" or "Request a new
counter number".

= What happens if stats4u.net is unreachable? =

Then the counter is missing from your page, and nothing else. The script loads
asynchronously and does not hold up your page.

= Why is the counter a link? =

The Stats4U script links every counter to its statistics page, so visitors
can click through to your numbers - the same on every site that uses the code
from stats4u.net. If your statistics should stay private, set the counter to
"not public" on stats4u.net: the link then goes to the Stats4U home page
instead of your numbers.

= Does deleting the plugin delete my counter? =

No. Deleting the plugin removes from your database what the plugin stored:
its settings, the shop settings and lists, pending retries, the remembered view
of its settings page and a hidden shop notice, and the order reference and
retry count it stored on WooCommerce orders. The counter belongs to you and
keeps its history - your numbers should not disappear because you tried a
plugin and removed it again.

== Screenshots ==

1. Choose the setup wizard or open all Stats4U settings.
2. Set up the counter, design, placement and consent step by step.
3. Use the settings menu to manage your counter and its display options.
4. See the visitor counter displayed below a WordPress theme’s footer.

== Changelog ==

= 1.9.0 =
* Placement: a live preview of the page in which the counter glides to the
  chosen place and alignment, with the kinds of pages it appears on.
* Consent: a diagram of what happens on a visit - with a consent tool, what
  happens on accept and on decline, and a warning when a required ID is missing.
* Custom configuration: a dot in the menu for every section with unsaved
  changes, "Discard changes" (asks first) and a check mark after saving.
* Shop page: the automatic retries of each failed report as a row of dots
  with the time of the next attempt.
* Shop page: an example report, line by line, explaining every field, and what
  never appears in a report.
* Select fields no longer put their arrow right next to the text.
* Readme: a shorter summary, a link to the Stats4U WordPress setup guide and
  clearer screenshot captions.

= 1.8.0 =
* Shop page rebuilt as cards: a status badge, tiles for reporting, shop secret,
  failed reports and orders not sent, a switch for reporting and a diagram of
  exactly what a report contains (and what it never contains).
* Failed and not-sent orders are listed as tables with links to the orders;
  clearing the list asks for confirmation in a dialog of the plugin.
* Settings page: an overview of where the counter is shown, when it loads and
  what it counts; a dot in the menu when the consent settings need attention.
* Copy buttons for the shortcode and the PHP template line, a loading
  indicator for "Request a new counter number" (which asks before replacing an
  existing number) and a character counter for feedback.
* If the preview image cannot be loaded, the settings page and the block show
  a short note instead of a broken image; the block without a counter shows a
  placeholder with a link to the settings.
* No change to what is sent, when or where.

= 1.7.2 =
* Readme and settings texts describe exactly what shop reporting sends, when
  and to which address, and which events come from the Stats4U script rather
  than from the plugin. Shop input is sanitized where it is read.
* The notice about failed shop reports appears only on the Dashboard, the
  plugin list, the Stats4U settings page and the order screens, and can be
  hidden. Orders in an unsupported currency are listed on the shop page
  instead of being treated as failed reports.
* Order meta keys use the prefix `_stats4u_`; values stored by 1.7.0 and 1.7.1
  are kept and moved. Uninstalling also removes this order meta, deactivating
  removes pending retries.
* The settings page script is a separate, readable file (admin.js); the large
  banner on the settings page is replaced by one line.

= 1.7.1 =
* Tested with WordPress 7.2.
* The Stats4U counter block uses block API version 3, so the post editor
  keeps running in its iframe (no deprecation notice in the browser console).

= 1.7.0 =
* Optional WooCommerce reporting (off by default, Settings > Stats4U shop):
  confirmed payments and refunds are sent to Stats4U with amount, currency
  and a keyed order reference, signed with your shop secret. No customer
  fields or product details. Failed reports retry up to eight times.
* Uninstalling also removes the shop settings and pending retries.

= 1.6.0 =
* Every value is escaped right where it is printed. The counter below or
  inside your theme's footer is now put in place by an output buffer
  callback; the plugin no longer prints the page itself.
* Error messages about a pasted code are escaped.
* The settings page remembers "wizard" or "custom configuration" only when
  you choose it on the page itself.
* Translations now come from translate.wordpress.org instead of files bundled
  with the plugin.

= 1.5.0 =
* The counter is now the official Stats4U script (s4u.js) with every setting
  from the creator, instead of the counter image alone. Your statistics get
  the page and the referrer of every visit, time on page, scroll depth and the
  "online now" figure. The counter still appears exactly where you place it.
* Coming back from the creator on stats4u.net shows the new design at once,
  with *Save Changes* right below it. Before, the settings page still showed
  the old design until you saved.
* Consent: a placeholder waits where the counter goes and is filled with the
  script after consent - with every tool. Cookie Information and Civic now use
  their blocking of script files.
* The link to the statistics page now comes from the Stats4U script itself,
  as everywhere the code from stats4u.net is used; the plugin's own switch is
  gone. A counter set to "not public" on stats4u.net links to the Stats4U
  home page.
* `data-private`, `data-alias` and `data-screen` from a pasted script code are
  kept.
* The settings page shows the full code of your counter, ready to copy or to
  change, and under *Placement* the line for a theme template.
* A feedback form at the end of the settings page: tell us what is missing or
  not working.
* A new start: choose between a wizard that walks you through counter,
  appearance, placement and consent step by step, and a custom configuration
  with all settings sorted in a menu. The wizard can be restarted at any time.
* "Request a new counter number" gets a free number from stats4u.net in one
  click - in the design you have set.

= 1.4.0 =
* Paste the code from stats4u.net instead of typing a number: HTML, BBCode,
  Markdown, the script version, an image address or just the number. Every
  setting from the creator comes along.
* "Create a free counter on stats4u.net" - the creator's last step brings the
  code back to the settings page.
* Consent: 27 consent tools and the WP Consent API, or your own attributes.
* Placement: below or inside the theme's footer, end of the page, below the
  content, fixed corner, or nowhere automatically; alignment; all pages,
  front page only or single posts and pages only.
* New block "Stats4U counter".
* Size, language of the counter and what to count (page views or unique
  visitors) can be set.

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

= 1.9.0 =
Live previews for placement and consent, unsaved-changes markers, retry timeline and an example report on the shop page. No change to what is sent. Your settings are kept.

= 1.8.0 =
Clearer shop page with a diagram of what a report contains, an overview on the settings page and copy buttons. No change to what is sent. Your settings are kept.

= 1.7.2 =
Exact texts about shop reporting, a shop notice that can be hidden, and order data from 1.7.x is kept. Your settings are kept.

= 1.7.1 =
Compatible with WordPress 7.2; the counter block is updated for the iframe editor. Your settings are kept.

= 1.7.0 =
Adds optional WooCommerce payment and refund reporting. It is off until you enable it. Your settings are kept.

= 1.6.0 =
Output escaping hardened. The settings page is translated through translate.wordpress.org now; until your language is complete there, it is in English. Your settings are kept.

= 1.5.0 =
The counter now uses the official Stats4U script: your statistics get the page, the referrer and the time on page of every visit. Your settings are kept.

= 1.4.0 =
Paste the code from stats4u.net, choose where the counter goes and let your consent tool decide when it loads. Your settings are kept.
