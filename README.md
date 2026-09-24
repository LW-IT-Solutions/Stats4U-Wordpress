# Stats4U Visitor Counter for WordPress

A visitor counter from stats4u.net. Paste the code, pick a place, and let your
consent banner decide when it loads.

The plugin puts the official [Stats4U](https://www.stats4u.net/) script
(`s4u.js`) on your WordPress site – the same code the creator on stats4u.net
gives you, with every setting from it – right where the counter should appear.
Paste the code you got on stats4u.net – HTML, BBCode, Markdown, the script
code, an image address or just the counter number – under
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
  attributes. The counter loads only after consent: a placeholder waits where
  the counter goes and is filled with the script once the tool allows it.
* **Statistics worth reading:** every count carries the page and the referrer;
  on top come time on page, scroll depth and "online now". The script is
  12.6 kB over the wire (Brotli) and cached for a week; no cookie.
* **19 interface languages** – the same as stats4u.net. Regional variants
  (de_AT, es_MX, pt_BR …) get the nearest one.

What the script sends, and when, is listed in [readme.txt](readme.txt),
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
