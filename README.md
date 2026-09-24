# Stats4U Visitor Counter for WordPress

A visitor counter as an image. Paste the code from stats4u.net, pick a place,
and let your consent banner decide when it loads.

The plugin puts a [Stats4U](https://www.stats4u.net/) counter on your WordPress
site. Paste the code you got on stats4u.net – HTML, BBCode, Markdown, the script
version, an image address or just the counter number – under
*Settings → Stats4U*. No counter yet? The button "Create a free counter on
stats4u.net" opens the creator, and its last step brings the code back.

![Settings page](.wordpress-org/screenshot-1.png)

## What it does

* **Everything from the creator comes along:** design, colors, size, dark mode,
  sparkline, what to count – no fields to copy by hand.
* **Placed right, with any theme:** below or inside the theme's footer, at the
  end of the page, below posts and pages, fixed in a corner – or with the block
  "Stats4U counter" / the shortcode `[stats4u]`. The footer is found as the
  theme's outermost footer element; tested with Twenty Twenty-Four/-Five,
  Astra, GeneratePress, Hello Elementor, Kadence, Neve, OceanWP and themes
  whose body is a flex or grid container.
* **Consent built in:** 27 consent tools and the WP Consent API, or your own
  attributes. The counter loads only after consent. Tools that can release an
  image themselves (consented.eu, Cookiebot, Klaro!, Termly, iubenda,
  Cookie-Script, Cookie Information, Civic) get no script at all.
* **Without a consent tool:** one `<img>`, no script, no cookie. 797 bytes
  median over 580 designs, measured at [stats4u.net/weight](https://www.stats4u.net/weight).
* **19 interface languages** – the same as stats4u.net. Regional variants
  (de_AT, es_MX, pt_BR …) get the nearest one.

Details on the data the image request carries are in [readme.txt](readme.txt),
section *External services*.

## Install

* **From wordpress.org** (once listed): *Plugins → Add New*, search for "Stats4U".
* **By hand:** download `stats4u.zip` from the latest release, then
  *Plugins → Add New → Upload Plugin*.

Requires WordPress 5.8+ and PHP 7.4+. Tested up to WordPress 7.1.

## Repository layout

| Path | What |
|---|---|
| `stats4u.php`, `uninstall.php`, `editor.js`, `freigabe.js`, `languages/` | the plugin |
| `readme.txt` | the wordpress.org readme (canonical description, changelog) |
| `.wordpress-org/` | banner, icon and screenshots for the wordpress.org page (SVN `assets/`), not part of the plugin zip |

## License

GPL-2.0-or-later, see [LICENSE](LICENSE).
© LW IT Solutions Company Lukas Wójcik, Łódź.
