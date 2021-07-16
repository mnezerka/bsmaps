<?php
/*
vim: set expandtab sw=4 ts=4 sts=4 foldmethod=indent:
Plugin Name: BSMaps
Description: Wordpress plugin for rendering maps and gpx tracks in post content
Version: 1.2
Author: Michal Nezerka
Author URI: http://blue.pavoucek.cz
Text Domain: bsmaps
Domain Path: /languages
*/

// Implementation of BSMaps plugin
class BSMaps
{
    public function __construct()
    {
        add_filter( 'block_categories', array( $this, 'simple_block_gallery_category' ), 10, 2 ); 

        add_action('init', array($this, 'onInit'));
		add_action('init', array($this, 'block_init'));

        add_filter('upload_mimes', array($this, 'onUploadMimeTypes'));
        add_filter('wp_check_filetype_and_ext', array($this, 'onCheckFiletypeAndExt'), 10, 4);

        add_action('wp_enqueue_scripts', array($this, 'onEnqueueScripts'));
    }

    public function simple_block_gallery_category( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug' => 'simple-block-gallery',
					'title' => 'Simple Block Gallery',
					'icon' => 'images-alt',
				),
			)
		);
	}
    public function onEnqueueScripts() {
        // leaflet
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.js');

        // leaflet gpx extension
        wp_enqueue_script('leaflet-gpx-js', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.4.0/gpx.min.js');

        // leaflet full screen control - https://github.com/brunob/leaflet.fullscreen
        wp_enqueue_style('leaflet-fullscreen-css', plugins_url('/js/leaflet.fullscreen-1.6.0/Control.FullScreen.css', __FILE__));
        wp_enqueue_script('leaflet-fullscreen-js', plugins_url('/js/leaflet.fullscreen-1.6.0/Control.FullScreen.js', __FILE__), array('leaflet-js'));

        // needs to be inserted into the footer, it needs to see DOM element for rendering
        wp_enqueue_script('bsmaps-js', plugins_url('/js/bsmaps.js', __FILE__), array('leaflet-js', 'leaflet-gpx-js'), null, 1);
        wp_enqueue_style('bsmaps-css', plugins_url('/css/bsmaps.css',__FILE__ ));
    }

    public function onInit()
    {
        // add shortcode for maps
        add_shortcode('bsmap', array($this, 'bsmap_shortcode'));

        // if Gutenberg is active.
        /*
        if (function_exists( 'register_block_type')) {

            wp_register_script(
                'bsmaps',
                plugins_url('js/block.js', __FILE__),
                array('wp-blocks', 'wp-element'),
                filemtime(plugin_dir_path(__FILE__) . 'js/block.js')
            );

            register_block_type( 'bsmaps/bsmap', array(
                'editor_script' => 'bsmaps',
            ));
        }
         */
    }

	public function block_init() {

		$asset_file = include( plugin_dir_path( __FILE__ ) . 'block/dist/bsmaps/bsmaps-block.asset.php');

		wp_register_script(
			'bsmaps-block',
			plugins_url( 'block/dist/bsmaps/bsmaps-block.js',  __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_localize_script(
			'bsmaps-block',
			'bsmaps_text',
			array(
				'panelmenu' => __( 'Settings' ),
				'creategallery' => __( 'Create Gallery' ),
				'updategallery' => __( 'Update gallery' ),
				'space' => __( 'Space', 'simple-block-gallery' ),
				'r_images' => __( 'Rounded Images', 'simple-block-gallery' ),
				'link' => __( 'Link to Media File' ),
				'width' => __( 'Width' ),
			)
		);

		register_block_type(
			'simple-block-gallery/bsmaps-block',
			array(
				'editor_script'   => 'bsmaps-block',
				'attributes'      => array(
					'width'    => array(
						'type'    => 'number',
						'default' => 100,
					),
					'padding'  => array(
						'type'    => 'number',
						'default' => 1,
					),
					'r_images' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'link'     => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

	}

    // register gpx mime types to enable upload to media
    public function onUploadMimeTypes($mimeTypes = array())
    {
        // mime types that plugin allows to upload to posts (media)
        $mimeTypes['gpx'] = 'text/xml';

        // Return the array back to the function with our added mime type.
        return $mimeTypes;
    }

    // we needs this filter for some reason, not completely clear to me, it was introduced
    // in fix plugin, see this:
    // 1
    // Known issue that was introduced in 4.7.1:
    // https://core.trac.wordpress.org/ticket/39550
    // There is a plugin to workaround it for those having this problem. A fix
    // will likely be in the next release.
    // https://wordpress.org/plugins/disable-real-mime-check/
    public function onCheckFiletypeAndExt($data, $file, $filename, $mimes) {
        $wp_filetype = wp_check_filetype( $filename, $mimes );

        $ext = $wp_filetype['ext'];
        $type = $wp_filetype['type'];
        $proper_filename = $data['proper_filename'];

        return compact( 'ext', 'type', 'proper_filename' );
    }

    // Implementation of "bsmap" shortcode
    //
    // @param array $attr Attributes of the shortcode.
    // @return string HTML content to be sent to browser 
    public function bsmap_shortcode($atts)
    {
        global $post;

        // instance counter - could be useful in case of multiple maps per
        // single post
        static $instance = 0;
        $instance++;

        // get xml (gpx) files attached to current post
        $attachments = get_children(array(
            'post_parent' => $post->ID,
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'text/xml',
            'order' => 'ASC',
            'orderby' => 'menu_order ID'));

        // build list of attachment urls
        $gpxList = [];
        foreach ($attachments as $id => $attachment)
        {
            $gpxList[] = wp_get_attachment_url($id);
        }

        // element to which map is rendered
        $output = '<div id="bsmap" style="width: 100%; height: 400px;"></div>';

        // this is way how Wordpres supports passing php parameters to javascript
        $jsData = array(
            'gpxList' => $gpxList,
            'iconsUrl' => plugins_url('/icons', __FILE__)
        );
        wp_localize_script('bsmaps-js', 'params', $jsData);

        return $output;
    }
}

// create plugin instance
$bsMaps = new BSMaps();
?>
