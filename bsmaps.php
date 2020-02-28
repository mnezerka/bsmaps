<?php
/*
vim: set expandtab sw=4 ts=4 sts=4 foldmethod=indent:
Plugin Name: BSMaps
Description: Wordpress plugin for rendering maps and gpx tracks in post content
Version: 1.0
Author: Michal Nezerka
Author URI: http://blue.pavoucek.cz
Text Domain: bsmaps
Domain Path: /languages
*/

/*
 * Implementation of BSMaps plugin
 */
class BSMaps
{
    public function __construct()
    {
        add_action('init', array($this, 'onInit'));
        add_filter('upload_mimes', array($this, 'onUploadMimeTypes'), 1, 1);
        add_action('wp_enqueue_scripts', array($this, 'onEnqueueScripts'));
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
        // Add shortcode for maps
        add_shortcode('bsmap', array($this, 'bsmap_shortcode'));
    }

    // register gpx mime types to enable upload to media
    public function onUploadMimeTypes($mimeTypes = array())
    {
        // mime types that plugin allows to upload to posts (media)
        $mimeTypes['gpx'] = 'text/xml';

        return $mimeTypes;
    }

    /**
    * Implementation of "bsmap" shortcode
    *
    * @param array $attr Attributes of the shortcode.
    * @return string HTML content to be sent to browser 
    */
    public function bsmap_shortcode($atts)
    {
        global $post;

        // instance counter - could be useful in case of multiple maps per
        // single post
        static $instance = 0;
        $instance++;

        //These are all the 'options' you can pass in through the
        // shortcode definition, eg: [gallery itemtag='p']
        extract(shortcode_atts(array(
            'order'      => 'ASC',
            'orderby'    => 'menu_order ID',
            'id'         => $post->ID,
            'size'       => 'thumbnail'
        ), $attr));

        // get xml (gpx) files attached to current post
        $attachments = get_children(array(
            'post_parent' => $id,
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'text/xml',
            'order' => $order,
            'orderby' => $orderby));

        // build list of attachment urls
        $gpxList = [];
        foreach ($attachments as $id => $attachment)
        {
            $gpxList[] = wp_get_attachment_url($id);
        }

        // element to which map is rendered
        $output .= '<div id="bsmap" style="width: 100%; height: 400px;"></div>';

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
