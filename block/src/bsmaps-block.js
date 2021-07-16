const { registerBlockType } = wp.blocks;
const { Fragment, RawHTML } = wp.element;
const { InspectorControls, InnerBlocks, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { RangeControl, Button, PanelBody, ToggleControl } = wp.components;

registerBlockType(
	'bsmaps-block/bsmaps-block',
	{
		title: 'BS Maps',
		icon: 'tagcloud',
		category: 'simple-block-gallery',
		attributes: {
	        list_images: {
				type: 'string',
				source: 'html'
			},
			image: {
				type: 'array'
			},
			images_ids: {
				type: 'array'
			},
			width: {
				type: 'number',
				default: 100
			},
			padding: {
				type: 'number',
				default: 1
			},
			r_images: {
				type: 'number',
				default: 0
			},
			link: {
				type: 'boolean',
				default: false
			},
		},

		edit ( props ) {

			const {
				attributes: {
					image,
				},
				setAttributes,
			} = props;

			const onUpdateImage = ( image ) => {
				setAttributes( {
					image: image,
					images_ids: List_Ids( image, props ),
					list_images: List_Images( image, props )
				} );
			};

			function List_Ids( image, props ) {
				let j = [];
				for( let i in image ) {
					j.push( image[i].id );
				}
				return j;
			}

			function List_Images( image, props ) {
				let j = '<div style="columns: auto ' + props.attributes.width + 'px; column-gap: 0; margin: 0 auto; padding: 0;">';
				for( let i in image ) {
					j += '<div style="display: inline-block; padding: ' + props.attributes.padding + 'px; width: 100%;">';
					if ( props.attributes.link ) {
						j += '<a href="' + image[i].url + '">';
					}
					j += '<img src="' + image[i].url +'" style="border-radius: ' + props.attributes.r_images + 'px; vertical-align: bottom;">';
					if ( props.attributes.link ) {
						j += '</a>';
					}
					j += '</div>';
				}
				j += '</div>';
				return j;
			}

			props.attributes.list_images = List_Images( props.attributes.image, props );

			const ALLOWED_MEDIA_TYPES = [ 'image' ];

			return [
			<Fragment>
				<RawHTML>{ props.attributes.list_images }</RawHTML>
				<MediaUploadCheck>
					<MediaUpload
						title={ 'PB Post Gallery Block' }
						onSelect={ onUpdateImage }
						allowedTypes={ ALLOWED_MEDIA_TYPES }
						gallery = { true }
						multiple = { true }
						value={ props.attributes.images_ids }
						render={ ( { open } ) => (
							<Button className={ 'button button-large' }
								onClick={ open }>
								{ ! props.attributes.images_ids ? bsmaps_text.creategallery : bsmaps_text.updategallery }
							</Button>
						) }
					/>
				</MediaUploadCheck>

				<InspectorControls>
				{}
					<PanelBody title = { bsmaps_text.panelmenu } initialOpen = { true }>
						<RangeControl
							label = { bsmaps_text.width }
							max = { 1000 }
							min = { 10 }
							value = { props.attributes.width }
							onChange = { ( value ) => props.setAttributes( { width: value } ) }
						/>
						<RangeControl
							label = { bsmaps_text.space }
							max = { 20 }
							min = { 0 }
							value = { props.attributes.padding }
							onChange = { ( value ) => props.setAttributes( { padding: value } ) }
						/>
						<RangeControl
							label = { bsmaps_text.r_images }
							max = { 20 }
							min = { 0 }
							value = { props.attributes.r_images }
							onChange = { ( value ) => props.setAttributes( { r_images: value } ) }
						/>
						<ToggleControl
							label = { bsmaps_text.link }
							checked = { props.attributes.link }
							onChange = { ( value ) => props.setAttributes( { link: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
			</Fragment>
			];
		},

		save ( props ) {
			return (
				<Fragment>
					<RawHTML>Hello</RawHTML>
                			{
						props.attributes.list_images && (
							<RawHTML>{ props.attributes.list_images }</RawHTML>
						)
					}
				</Fragment>
			);
		},
	}
);