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
	function holen() {
		var q = new URLSearchParams();
		q.append("lang", N.lang);
		k.disabled = true;
		k.classList.add("s4u-laedt");
		st.textContent = "";
		st.className = "s4u-status";
		fetch(N.ziel, { method: "POST", body: q, credentials: "omit" })
			.then(function (r) { return r.json(); })
			.then(function (a) {
				k.disabled = false;
				k.classList.remove("s4u-laedt");
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
			.catch(function () { k.disabled = false; k.classList.remove("s4u-laedt"); st.textContent = N.netz; st.className = "s4u-status s4u-fehler"; });
	}
	// With a counter number already in place, ask first: the new number
	// replaces it in the code field. Nothing is sent before the answer.
	k.addEventListener("click", function () {
		if (N.alt && window.stats4uFrage) {
			window.stats4uFrage(N.frage.replace("%s", N.alt), N.knopf, k, holen);
			return;
		}
		holen();
	});
})();

// Confirmation dialog of the plugin's own (no browser dialogs): role="dialog",
// focus stays inside, Esc cancels, the focus returns to the button that opened
// it. Used by forms with data-s4u-bestaetigen and by window.stats4uFrage().
(function () {
	var d = document, D = window.stats4uDialog || {}, box = null, weiter = null, von = null;
	function zu() {
		if (!box) { return; }
		box.hidden = true;
		weiter = null;
		if (von && von.focus) { von.focus(); }
	}
	function bauen() {
		box = d.createElement("div");
		box.className = "s4u-dialog";
		box.hidden = true;
		var grund = d.createElement("div");
		grund.className = "s4u-dialog-grund";
		var inner = d.createElement("div");
		inner.className = "s4u-dialog-box";
		inner.setAttribute("role", "dialog");
		inner.setAttribute("aria-modal", "true");
		inner.setAttribute("aria-labelledby", "s4u_dialog_text");
		var icon = d.createElement("span");
		icon.className = "dashicons dashicons-warning";
		icon.setAttribute("aria-hidden", "true");
		var p = d.createElement("p");
		p.className = "s4u-dialog-text";
		p.id = "s4u_dialog_text";
		var knoepfe = d.createElement("div");
		knoepfe.className = "s4u-dialog-knoepfe";
		var nein = d.createElement("button");
		nein.type = "button";
		nein.className = "button";
		nein.textContent = D.abbrechen || "Cancel";
		var ja = d.createElement("button");
		ja.type = "button";
		ja.className = "button button-primary s4u-dialog-ja";
		knoepfe.appendChild(nein);
		knoepfe.appendChild(ja);
		inner.appendChild(icon);
		inner.appendChild(p);
		inner.appendChild(knoepfe);
		box.appendChild(grund);
		box.appendChild(inner);
		d.body.appendChild(box);
		grund.addEventListener("click", zu);
		nein.addEventListener("click", zu);
		ja.addEventListener("click", function () {
			var f = weiter;
			box.hidden = true;
			weiter = null;
			if (von && von.focus) { von.focus(); }
			if (f) { f(); }
		});
		box.addEventListener("keydown", function (ev) {
			if (ev.key === "Escape") { ev.preventDefault(); zu(); return; }
			if (ev.key === "Tab") {
				var b = [nein, ja], i = b.indexOf(d.activeElement);
				if (ev.shiftKey && i <= 0) { ev.preventDefault(); ja.focus(); }
				else if (!ev.shiftKey && i === 1) { ev.preventDefault(); nein.focus(); }
			}
		});
	}
	window.stats4uFrage = function (text, knopf, ausloeser, ok) {
		if (!box) { bauen(); }
		von = ausloeser || d.activeElement;
		weiter = ok;
		box.querySelector(".s4u-dialog-text").textContent = text;
		box.querySelector(".s4u-dialog-ja").textContent = knopf || "OK";
		box.hidden = false;
		box.querySelector(".s4u-dialog-ja").focus();
	};
	d.addEventListener("submit", function (ev) {
		var f = ev.target;
		if (!f || !f.hasAttribute || !f.hasAttribute("data-s4u-bestaetigen")) { return; }
		if (f.getAttribute("data-s4u-ok") === "1") { f.removeAttribute("data-s4u-ok"); return; }
		ev.preventDefault();
		window.stats4uFrage(f.getAttribute("data-s4u-bestaetigen"), f.getAttribute("data-s4u-knopf"), ev.submitter || d.activeElement, function () {
			f.setAttribute("data-s4u-ok", "1");
			if (f.requestSubmit) { f.requestSubmit(); } else { f.submit(); }
		});
	});
})();

// Preview: if the image cannot be loaded (stats4u.net not reachable from this
// browser, or a blocker), the stage says so instead of a broken image.
(function () {
	var d = document, A = window.stats4uAssi || {};
	["s4u_vorschau", "s4u_vorschau_2"].forEach(function (id) {
		var b = d.getElementById(id);
		if (!b || !A.keineVorschau) { return; }
		var hinweis = d.createElement("p");
		hinweis.className = "s4u-ohne-vorschau";
		hinweis.textContent = A.keineVorschau;
		hinweis.hidden = true;
		b.parentNode.appendChild(hinweis);
		function fehlt() { b.hidden = true; hinweis.hidden = false; }
		b.addEventListener("error", fehlt);
		b.addEventListener("load", function () { b.hidden = false; hinweis.hidden = true; });
		if (b.complete && b.getAttribute("src") && b.naturalWidth === 0) { fehlt(); }
	});
})();

// Copy buttons (data-s4u-kopieren = id of the element whose text is copied).
// Without the clipboard API the text is selected, so that Ctrl+C copies it.
(function () {
	var d = document, A = window.stats4uAssi || {};
	var ansage = d.createElement("div");
	ansage.className = "screen-reader-text";
	ansage.setAttribute("aria-live", "polite");
	d.body.appendChild(ansage);
	function sag(t) { ansage.textContent = ""; setTimeout(function () { ansage.textContent = t; }, 50); }
	d.addEventListener("click", function (ev) {
		var k = ev.target.closest ? ev.target.closest("[data-s4u-kopieren]") : null;
		if (!k) { return; }
		var z = d.getElementById(k.getAttribute("data-s4u-kopieren"));
		if (!z) { return; }
		var t = k.querySelector(".s4u-kopier-text") || k, vorher = t.getAttribute("data-vorher") || t.textContent;
		t.setAttribute("data-vorher", vorher);
		function markieren() {
			var r = d.createRange();
			r.selectNodeContents(z);
			var s = window.getSelection();
			s.removeAllRanges();
			s.addRange(r);
			sag(A.markiert || "");
		}
		if (!navigator.clipboard || !window.isSecureContext) { markieren(); return; }
		navigator.clipboard.writeText(z.textContent).then(function () {
			k.classList.add("s4u-kopiert");
			t.textContent = A.kopiert || vorher;
			sag(A.kopiert || "");
			setTimeout(function () { k.classList.remove("s4u-kopiert"); t.textContent = vorher; }, 2000);
		}, markieren);
	});
})();

// Feedback: characters left and a sending indicator on the button.
(function () {
	var d = document, t = d.getElementById("s4u_fb_text"), z = d.getElementById("s4u_fb_zaehler"), A = window.stats4uAssi || {};
	if (!t || !z || !A.zeichen) { return; }
	var max = parseInt(t.getAttribute("maxlength"), 10) || 5000;
	function neu() {
		z.textContent = A.zeichen.replace("%1$s", t.value.length).replace("%2$s", max);
		z.classList.toggle("s4u-knapp", t.value.length > max * 0.9);
	}
	t.addEventListener("input", neu);
	neu();
	var k = d.getElementById("s4u_fb_knopf");
	if (k && window.MutationObserver) {
		new MutationObserver(function () { k.classList.toggle("s4u-laedt", k.disabled); neu(); })
			.observe(k, { attributes: true, attributeFilter: ["disabled"] });
	}
})();
