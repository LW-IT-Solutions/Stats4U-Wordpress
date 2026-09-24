/**
 * Stats4U - der Block "Stats4U counter" im Editor.
 *
 * Ohne Build-Schritt, deshalb wp.element.createElement statt JSX. Im Editor
 * steht nur das Vorschaubild (rl=1 - zaehlt nicht); auf der Seite rendert
 * PHP den Block, mit derselben Consent-Behandlung wie ueberall.
 * Die Texte kommen uebersetzt aus PHP (window.stats4uEditor).
 */
(function (blocks, element, blockEditor) {
	if (!blocks || !element || !blockEditor) { return; }
	var el = element.createElement;
	var daten = window.stats4uEditor || {};
	var Ausrichtung = blockEditor.AlignmentControl || blockEditor.AlignmentToolbar;

	blocks.registerBlockType('stats4u/counter', {
		apiVersion: 2,
		title: daten.titel || 'Stats4U counter',
		description: daten.beschreibung || '',
		icon: 'chart-bar',
		category: 'widgets',
		keywords: ['counter', 'visitors', 'stats4u'],
		attributes: { ausrichtung: { type: 'string', 'default': 'center' } },
		supports: { html: false },
		edit: function (props) {
			var a = props.attributes.ausrichtung || 'center';
			var inhalt = daten.vorschau
				? el('img', { src: daten.vorschau, alt: '' })
				: el('p', { style: { fontStyle: 'italic', margin: 0 } }, daten.leer || '');
			return el('div', blockEditor.useBlockProps({ style: { textAlign: a } }),
				Ausrichtung ? el(blockEditor.BlockControls, null,
					el(Ausrichtung, {
						value: a,
						onChange: function (neu) { props.setAttributes({ ausrichtung: neu || 'center' }); }
					})) : null,
				inhalt);
		},
		save: function () { return null; }
	});
})(window.wp && window.wp.blocks, window.wp && window.wp.element, window.wp && window.wp.blockEditor);
