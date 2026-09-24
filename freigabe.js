/**
 * Stats4U - gibt die Zaehlerbilder frei.
 *
 * Fuer Consent-Werkzeuge, die nur Skripte mit eigener Adresse sperren
 * (CookieYes, Osano): das Plugin bindet diese Datei mit dem Sperr-Attribut des
 * Werkzeugs ein, und das Werkzeug fuehrt sie erst nach der Einwilligung aus.
 * Dann setzt sie die Adresse, die bis dahin in data-stats4u-src wartet.
 */
(function () {
	function an() {
		var l = document.querySelectorAll('img[data-stats4u-src]');
		for (var i = 0; i < l.length; i++) {
			l[i].src = l[i].getAttribute('data-stats4u-src');
			l[i].removeAttribute('data-stats4u-src');
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', an);
	} else {
		an();
	}
})();
