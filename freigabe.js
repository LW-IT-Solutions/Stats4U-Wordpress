/**
 * Stats4U - setzt nach der Einwilligung das Zaehlerskript ein.
 *
 * Fuer Consent-Werkzeuge, die nur Skripte mit eigener Adresse freigeben
 * (CookieYes, Osano, Cookie Information, Civic): das Plugin bindet diese Datei
 * mit dem Sperr-Attribut des Werkzeugs ein, und das Werkzeug fuehrt sie erst
 * nach der Einwilligung aus. Dann kommt in jeden Platzhalter
 * (span[data-stats4u]) das offizielle s4u.js mit den Angaben, die dort warten.
 * Wie der Einzeiler in stats4u_aktivierer() - danach steht es auch hier als
 * window.stats4uAn bereit.
 */
(function () {
	var SKRIPT = 'https://www.stats4u.net/s4u.js';
	function an() {
		window.stats4uAn = an;
		var l = document.querySelectorAll('span[data-stats4u]');
		for (var i = 0; i < l.length; i++) {
			var p = l[i], d = {};
			try { d = JSON.parse(p.getAttribute('data-stats4u')) || {}; } catch (x) { }
			p.removeAttribute('data-stats4u');
			var s = document.createElement('script');
			for (var k in d) {
				if (/^data-[a-z]+$/.test(k)) { s.setAttribute(k, String(d[k])); }
			}
			s.src = SKRIPT;
			s.async = true;
			p.appendChild(s);
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', an);
	} else {
		an();
	}
})();
