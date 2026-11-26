/**
 * Stats4U - der Block "Stats4U counter" im Editor.
 *
 * Ohne Build-Schritt, deshalb wp.element.createElement statt JSX. Im Editor
 * steht nur das Vorschaubild (rl=1 - zaehlt nicht); auf der Seite rendert
 * PHP den Block, mit derselben Consent-Behandlung wie ueberall.
 * Die Texte kommen uebersetzt aus PHP (window.stats4uEditor).
 * Ohne Zaehler: ein Platzhalter mit dem Weg zu den Einstellungen. Laedt das
 * Vorschaubild nicht, steht dort ein Satz statt eines kaputten Bildes.
 */
(function (blocks, element, blockEditor, components) {
	if (!blocks || !element || !blockEditor) { return; }
	var el = element.createElement;
	var daten = window.stats4uEditor || {};
	var Ausrichtung = blockEditor.AlignmentControl || blockEditor.AlignmentToolbar;

	function Vorschau() {
		var zustand = element.useState(false), kaputt = zustand[0], setKaputt = zustand[1];
		if (kaputt) {
			return el('p', { className: 'stats4u-editor-hinweis', style: { margin: 0, fontSize: '13px', opacity: 0.8 } }, daten.keineVorschau || '');
		}
		return el('figure', { className: 'stats4u-editor-vorschau', style: { margin: 0 } },
			el('img', { src: daten.vorschau, alt: daten.alt || '', onError: function () { setKaputt(true); } }),
			daten.nichtGezaehlt ? el('figcaption', { style: { fontSize: '12px', opacity: 0.7, marginTop: '4px' } }, daten.nichtGezaehlt) : null);
	}

	function Leer() {
		if (components && components.Placeholder) {
			return el(components.Placeholder, {
				icon: 'chart-bar',
				label: daten.titel || 'Stats4U counter',
				instructions: daten.leer || ''
			}, daten.einstellungen && components.Button
				? el(components.Button, { variant: 'primary', href: daten.einstellungen }, daten.einrichten || '')
				: null);
		}
		return el('p', { style: { fontStyle: 'italic', margin: 0 } }, daten.leer || '');
	}

	blocks.registerBlockType('stats4u/counter', {
		apiVersion: 3,
		title: daten.titel || 'Stats4U counter',
		description: daten.beschreibung || '',
		icon: 'chart-bar',
		category: 'widgets',
		keywords: ['counter', 'visitors', 'stats4u'],
		attributes: { ausrichtung: { type: 'string', 'default': 'center' } },
		supports: { html: false },
		edit: function (props) {
			var a = props.attributes.ausrichtung || 'center';
			return el('div', blockEditor.useBlockProps({ style: { textAlign: a } }),
				Ausrichtung ? el(blockEditor.BlockControls, null,
					el(Ausrichtung, {
						value: a,
						onChange: function (neu) { props.setAttributes({ ausrichtung: neu || 'center' }); }
					})) : null,
				daten.vorschau ? el(Vorschau) : el(Leer));
		},
		save: function () { return null; }
	});
})(window.wp && window.wp.blocks, window.wp && window.wp.element, window.wp && window.wp.blockEditor, window.wp && window.wp.components);
