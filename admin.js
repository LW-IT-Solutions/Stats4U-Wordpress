/**
 * Stats4U - script for the settings page (Settings > Stats4U).
 *
 * Loaded only on that page (stats4u_admin_skript() in stats4u.php). The data
 * it needs comes from wp_add_inline_script() before this file:
 * window.stats4uCmp, window.stats4uFb, window.stats4uAssi, window.stats4uNeu.
 * Without this script the page works the same, it only shows all parts at
 * once and the preview does not follow the settings.
 *
 * Network requests made here, both only after a click by the administrator:
 * - "Send feedback" posts the message to stats4u.net (action=wpfeedback).
 * - "Request a new counter number" asks stats4u.net for a free number
 *   (action=wpneu), sending only the language of the admin area.
 */

// Consent tool: placeholder and hint for "Category or service", show or hide
// the matching rows. Placement: alignment is pointless for the corners and
// for "nowhere automatically".
(function () {
	var d = document, c = d.getElementById('s4u_consent');
	if (!c) { return; }
	var w = d.getElementById('s4u_consent_wert'),
		h = d.getElementById('s4u_wert_hinweis'),
		wz = d.getElementById('s4u_wert_zeile'),
		ez = d.getElementById('s4u_eigen_zeile');
	function neu() {
		var k = c.value, x = window.stats4uCmp[k];
		if (w) { w.placeholder = x ? x.wert : ''; }
		if (h && x) { h.textContent = x.hinweis; }
		if (wz) { wz.hidden = !x; }
		if (ez) { ez.hidden = (k !== 'eigen'); }
	}
	c.addEventListener('change', neu);
	neu();
	var p = d.querySelectorAll('input[name$="[platz]"]'),
		az = d.getElementById('s4u_ausrichtung_zeile');
	function platz() {
		var x = d.querySelector('input[name$="[platz]"]:checked');
		if (az && x) { az.hidden = /^(ecke_r|ecke_l|aus)$/.test(x.value); }
	}
	for (var i = 0; i < p.length; i++) { p[i].addEventListener('change', platz); }
	platz();
})();

// The preview follows size, language, light/dark and what to count - in the
// same order as stats4u_bildadresse(), always with rl=1 (not counted). The
// parts of the address are stored on the preview image itself (data-*).
(function () {
	var d = document, b = d.getElementById('s4u_vorschau'), bu = d.getElementById('s4u_buehne');
	if (!b) { return; }
	function wert(s) {
		var x = d.querySelector(s);
		return x ? x.value : '';
	}
	function neu() {
		var p = {}, q = [], k;
		try { p = JSON.parse(b.getAttribute('data-params')) || {}; } catch (x) {}
		for (k in p) {
			if (Object.prototype.hasOwnProperty.call(p, k)) {
				q.push(encodeURIComponent(k) + '=' + encodeURIComponent(p[k]));
			}
		}
		var dk = wert('input[name$="[dark]"]:checked') || '0';
		if (dk !== '0') { q.push('dark=' + encodeURIComponent(dk)); }
		var g = parseInt(wert('#s4u_gr'), 10);
		if (g && g !== 100) { q.push('gr=' + g); }
		if (wert('input[name$="[metrik]"]:checked') === 'uniq') { q.push('m=uniq'); }
		var s = wert('#s4u_sprache');
		if (s === 'site') { s = b.getAttribute('data-seite') || ''; }
		if (s) { q.push('cl=' + encodeURIComponent(s)); }
		q.push('rl=1');
		var u = b.getAttribute('data-basis') + '?' + q.join('&');
		if (b.getAttribute('src') !== u) { b.setAttribute('src', u); }
		if (bu) { bu.classList.toggle('s4u-dunkel', dk === '1'); }
	}
	window.stats4uVorschau = neu;
	var f = d.querySelectorAll('#s4u_gr,#s4u_sprache,input[name$="[dark]"],input[name$="[metrik]"]');
	for (var i = 0; i < f.length; i++) { f[i].addEventListener('change', neu); }
})();

// Feedback form: on "Send feedback" the browser posts the message to
// stats4u.net as a simple form (no preflight, no credentials).
(function () {
	var d = document, f = d.getElementById("s4u_fb_form"), T = window.stats4uFb;
	if (!f || !window.fetch || !window.URLSearchParams) { return; }
	var t = d.getElementById("s4u_fb_text"),
		m = d.getElementById("s4u_fb_mail"),
		h = d.getElementById("s4u_fb_seite"),
		k = d.getElementById("s4u_fb_knopf"),
		st = d.getElementById("s4u_fb_status");
	function zeig(x, ok) {
		st.textContent = x;
		st.className = ok ? "s4u-ok" : "s4u-fehler";
	}
	f.addEventListener("submit", function (ev) {
		ev.preventDefault();
		var x = t.value.trim(), y = m.value.trim();
		if (x.length < 10) { zeig(T.kurz, false); t.focus(); return; }
		if (y !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(y)) { zeig(T.mail, false); m.focus(); return; }
		var q = new URLSearchParams();
		q.append("message", x);
		q.append("email", y);
		q.append("version", T.version);
		q.append("lang", T.lang);
		// Site address and counter number only if the box is ticked.
		if (h && h.checked) {
			q.append("siteurl", T.seite);
			if (T.zaehler) { q.append("counterid", T.zaehler); }
		}
		k.disabled = true;
		st.textContent = "";
		st.className = "";
		fetch(T.ziel, { method: "POST", body: q, credentials: "omit" })
			.then(function (r) { return r.json(); })
			.then(function (a) {
				k.disabled = false;
				if (a && a.ok) { zeig(T.danke, true); t.value = ""; return; }
				zeig((a && T[a.code]) || T.netz, false);
			})
			.catch(function () { k.disabled = false; zeig(T.netz, false); });
	});
})();

// The code field grows with the code - fully visible, also on a phone.
(function () {
	var c = document.getElementById("s4u_code");
	if (!c) { return; }
	function hoch() {
		if (!c.offsetParent) { return; }
		c.style.height = "auto";
		c.style.height = (c.scrollHeight + 2) + "px";
	}
	c.addEventListener("input", hoch);
	window.stats4uCodeHoch = hoch;
	hoch();
	window.addEventListener("resize", hoch);
})();

// Custom configuration: one part at a time, chosen in the menu (anchor in the
// address, so that a reload stays there). Wizard: steps with Back/Next,
// step 1 only with a counter, a summary at the end.
(function () {
	var d = document, R = d.getElementById("s4u_rahmen");
	if (!R) { return; }
	R.classList.add("s4u-js");
	var teile = [].slice.call(R.querySelectorAll(".s4u-teil")),
		A = R.getAttribute("data-ansicht"),
		T = window.stats4uAssi;
	function zeige(el) {
		teile.forEach(function (t) { t.classList.toggle("s4u-aktiv", t === el); });
		if (window.stats4uCodeHoch) { window.stats4uCodeHoch(); }
	}
	function fokus(el) {
		var h = el && el.querySelector("h2");
		if (h) { h.setAttribute("tabindex", "-1"); h.focus({ preventScroll: true }); }
		var r = R.getBoundingClientRect();
		if (r.top < 0) { window.scrollBy(0, r.top - 50); }
	}

	if (A === "eigen") {
		var links = [].slice.call(R.querySelectorAll(".s4u-menue a")),
			leiste = d.getElementById("s4u_leiste");
		var waehle = function (id, f) {
			var el = d.getElementById("s4u-teil-" + id) || teile[0];
			zeige(el);
			var t = el.id.replace("s4u-teil-", "");
			links.forEach(function (a) {
				if (a.getAttribute("data-teil") === t) { a.setAttribute("aria-current", "page"); } else { a.removeAttribute("aria-current"); }
			});
			if (leiste) { leiste.hidden = (t === "feedback"); }
			if (f) { fokus(el); }
		};
		links.forEach(function (a) {
			a.addEventListener("click", function (ev) {
				ev.preventDefault();
				var t = a.getAttribute("data-teil");
				if (history.replaceState) { history.replaceState(null, "", "#s4u-teil-" + t); }
				waehle(t, true);
			});
		});
		waehle((location.hash || "").replace("#s4u-teil-", ""), false);
		return;
	}

	var nr = 1, max = teile.length,
		z = d.getElementById("s4u_zurueck"),
		w = d.getElementById("s4u_weiter"),
		fe = d.getElementById("s4u_fertig"),
		st = d.getElementById("s4u_schritt_text"),
		hin = d.getElementById("s4u_assi_fehler"),
		li = [].slice.call(R.querySelectorAll(".s4u-schritte li"));
	function text(sel) {
		var x = d.querySelector(sel);
		return x ? x.textContent.replace(/\s+/g, " ").trim() : "";
	}
	// Summary on the last step: counter and design, place, consent tool, preview.
	function zusammen() {
		var c = (d.getElementById("s4u_code") || {}).value || "",
			i = c.match(/data-id="(\d+)"/) || c.match(/\/c\/(\d+)-/) || c.match(/^\s*#?(\d{1,12})\s*$/),
			s = c.match(/data-style="([0-9a-z_]+)"/i) || c.match(/\/c\/\d+-([0-9a-z_]+)\./i);
		d.getElementById("s4u_z_zaehler").textContent = i ? T.zaehler.replace("%1$s", i[1]).replace("%2$s", s ? s[1] : "-") : text("#s4u_nr");
		var p = d.querySelector("input[name$=\"[platz]\"]:checked");
		d.getElementById("s4u_z_platz").textContent = p ? text("input[value=\"" + p.value + "\"][name$=\"[platz]\"] + span") : "";
		var o = d.getElementById("s4u_consent");
		d.getElementById("s4u_z_consent").textContent = o && o.selectedIndex >= 0 ? o.options[o.selectedIndex].text : "";
		var v = d.getElementById("s4u_vorschau"), v2 = d.getElementById("s4u_vorschau_2"), b2 = d.getElementById("s4u_buehne_2");
		if (v && v2 && b2) {
			v2.src = v.getAttribute("src");
			b2.hidden = false;
			b2.classList.toggle("s4u-dunkel", d.getElementById("s4u_buehne").classList.contains("s4u-dunkel"));
		}
	}
	function schritt(n, f) {
		nr = n;
		var el = teile[n - 1];
		zeige(el);
		hin.hidden = true;
		li.forEach(function (x, j) {
			x.classList.toggle("s4u-jetzt", j === n - 1);
			x.classList.toggle("s4u-erledigt", j < n - 1);
			if (j === n - 1) { x.setAttribute("aria-current", "step"); } else { x.removeAttribute("aria-current"); }
		});
		z.hidden = (n === 1);
		w.hidden = (n === max);
		fe.hidden = (n !== max);
		st.textContent = T.schritt.replace("%1$d", n).replace("%2$d", max);
		if (n === max) { zusammen(); }
		if (f) { fokus(el); }
	}
	w.addEventListener("click", function () {
		var c = d.getElementById("s4u_code");
		if (nr === 1 && !(c && c.value.trim() !== "")) {
			hin.textContent = T.erst;
			hin.hidden = false;
			if (c) { c.focus(); }
			return;
		}
		schritt(nr + 1, true);
	});
	z.addEventListener("click", function () { schritt(nr - 1, true); });
	li.forEach(function (x, j) {
		x.addEventListener("click", function () { if (j < nr - 1) { schritt(j + 1, true); } });
	});
	schritt(1, false);
})();

// "Request a new counter number": on click, a free number from stats4u.net
// (action=wpneu). It replaces data-id in the code field - the design stays -
// and takes effect when the settings are saved.
(function () {
	var d = document, k = d.getElementById("s4u_neu_nr"), N = window.stats4uNeu;
	if (!k || !window.fetch || !window.URLSearchParams) {
		if (k) { k.hidden = true; }
		return;
	}
	var st = d.getElementById("s4u_neu_status"), c = d.getElementById("s4u_code");
	k.addEventListener("click", function () {
		var q = new URLSearchParams();
		q.append("lang", N.lang);
		k.disabled = true;
		st.textContent = "";
		st.className = "s4u-status";
		fetch(N.ziel, { method: "POST", body: q, credentials: "omit" })
			.then(function (r) { return r.json(); })
			.then(function (a) {
				k.disabled = false;
				if (!a || !a.ok || !/^\d{1,12}$/.test(a.id || "")) {
					st.textContent = (a && N[a.code]) || N.netz;
					st.className = "s4u-status s4u-fehler";
					return;
				}
				c.value = /data-id="\d+"/.test(c.value)
					? c.value.replace(/data-id="\d+"/, "data-id=\"" + a.id + "\"")
					: N.vorlage.replace("data-id=\"0\"", "data-id=\"" + a.id + "\"");
				var b = d.getElementById("s4u_vorschau");
				if (b) {
					b.setAttribute("data-basis", b.getAttribute("data-basis").replace(/\/c\/\d+-/, "/c/" + a.id + "-"));
					if (window.stats4uVorschau) { window.stats4uVorschau(); }
				}
				var z = d.getElementById("s4u_nr");
				if (z && N.alt) { z.textContent = z.textContent.replace(N.alt, a.id); }
				N.alt = a.id;
				var h = d.getElementById("s4u_assi_fehler");
				if (h) { h.hidden = true; }
				if (window.stats4uCodeHoch) { window.stats4uCodeHoch(); }
				st.textContent = N.ok.replace("%s", a.id);
				st.className = "s4u-status s4u-ok";
			})
			.catch(function () { k.disabled = false; st.textContent = N.netz; st.className = "s4u-status s4u-fehler"; });
	});
})();
