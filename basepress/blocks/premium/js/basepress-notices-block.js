(function(){
	var createElement = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var InnerBlocks = wp.blockEditor.InnerBlocks;
	var apiFetch = wp.apiFetch;
	var i18n = wp.i18n;
	var settings = '';


	function setBlockCustomClassName( className, blockName ) {
		return blockName === 'basepress-kb/notices-block' ?
			'bpress-notice' :
			className;
	}

	// Adding the filter
	wp.hooks.addFilter(
		'blocks.getBlockDefaultClassName',
		'basepress-kb/set-notice-block-custom-class-name',
		setBlockCustomClassName
	);

	function loadBlockSettings( path ) {
		var similar = 0;

		jQuery("link").each(function(e){
			if( jQuery(this).attr("href") == path ) similar++;
		});

		if( similar == 0 ){
			jQuery('<link>').attr('rel', 'stylesheet')
				.attr('type', 'text/css')
				.attr('href', path)
				.appendTo('head');
		}
	}


	registerBlockType( 'basepress-kb/notices-block', {
		title: i18n.__( 'KB Notices', 'basepress' ),

		description: i18n.__( 'Adds a Notice to your knowledge base article', 'basepress' ),

		icon: 'megaphone',

		category: 'basepress-kb-block-cat',

		supports: {
			multiple: true
		},

		edit: function( {attributes, setAttributes, isSelected, className} ) {

			if( ! settings ){
				apiFetch( {
					path: `/basepress_kb/v1/notice_settings`,
				} ).then(
					( data ) => {
						settings = data;
						loadBlockSettings( data.styleCss );
					}
				)
			}

			return (
				createElement( 'section', { className: className },
					createElement( InnerBlocks )
				)
			)
		},

		save: function( props ) {
			return createElement( 'section', {className: props.className },
				createElement( InnerBlocks.Content )
			);
		},
	} );
}());