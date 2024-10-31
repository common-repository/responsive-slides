<?php

/**
  Plugin Name: responsive-slider
  Description:  A simple and elegant wordpress responsive slider plugin of http://responsive-slides.viljamis.com/ 
  Version: 1.0
  Author: soroushatarod
  Author URI: https://twitter.com/SoroushAtarod
  License: GPL2


  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2,
  as published by the Free Software Foundation.

  You may NOT assume that you can use any other version of the GPL.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  The license for this software can likely be found here:
  http://www.gnu.org/licenses/gpl-2.0.html
 */
class Responsive_Slides {
   
    
    public function __construct() {

        $this->responsive_slides_init();
    }

    /**
     *   Calls the action hook initializer method 
     */
    public function responsive_slides_init() {
        $this->responsive_action_hook_init();
    }

    /**
     *  Registers the plugin as a custom post 
     */
    public function responsive_register_plugin() {
        $labels = array(
            'name' => 'Responsive Slideshow',
            'add_new' => 'Add A New Slideshow',
            'add_new_item' => 'Add a New Slideshow'
        );

        $args = array(
            'public' => true,
            'labels' => $labels,
            'supports' => array(
                'title',
                'custom_fields',
                'author'
            )
        );
        register_post_type('responsive_post', $args);
    }

    /**
     * Loads the assets files such as CSS and JS
     */
    public function responsive_load_javascript() {
        wp_enqueue_script('responsive_js', plugins_url('assets/js/responsiveslides.min.js', __FILE__), array('jquery'));
        wp_enqueue_style('responsive_style', plugins_url('assets/css/responsivecss.css', __FILE__));
        if ($this->responsive_is_page_responsive_post() == TRUE) {
            wp_enqueue_script('plupload-all');
            wp_enqueue_style('responsive_style_admin', plugins_url('assets/css/admin/admincss.css', __FILE__));
            wp_enqueue_style('responsive_style_guider', plugins_url('assets/css/admin/guiders-1.3.0.css', __FILE__));
            wp_enqueue_script('responsive_guider_js', plugins_url('assets/js/admin/guiders-1.3.0.js', __FILE__));
            wp_enqueue_script('responsive_admin_js', plugins_url('assets/js/admin/jquery.admin.js', __FILE__));
        }
    }

    /**
     *  Register the hook action  and filterof the plugin 
     */
    public function responsive_action_hook_init() {
        add_action('wp_print_scripts', array($this, 'responsive_load_javascript'));
        add_action('init', array($this, 'responsive_register_plugin'));
        add_action('add_meta_boxes', array($this, 'responsive_metabox_handler'));
        add_action("admin_head", array($this, 'responsive_admin_js_config'));
        add_action('wp_ajax_plupload_action', array($this, 'responsive_image_upload_handler'));
        add_action('admin_head', array($this, 'responsive_hide_minor_publishing'));
        add_action('manage_posts_custom_column', array($this, 'responsive_image_column_content'), 10, 2);
        add_action('wp_insert_post', array($this, 'responsive_save_post'));
        add_filter('manage_posts_columns', array($this, 'responsive_image_column_header'));
        add_filter('post_row_actions', array($this, 'responsive_remove_view_from_action'), 10, 1);
        add_filter('post_updated_messages', array($this, 'codex_book_updated_messages'));
        add_shortcode('responsive_slide', array($this, 'responsive_shortcode'));
    }

    public function codex_book_updated_messages($messages) {
        global $post, $post_ID;
        $messages['responsive_post'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => sprintf(__('Responsive Slideshow Updated Shortcode is: [responsive_slide id=' . $post_ID . ']')),
            2 => __('Custom field updated.', 'your_text_domain'),
            3 => __('Custom field deleted.', 'your_text_domain'),
            4 => __('Slideshow updated.', 'your_text_domain'),
            /* translators: %s: date and time of the revision */
            5 => isset($_GET['revision']) ? sprintf(__('Slideshow restored to revision from %s', 'your_text_domain'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => sprintf(__('Responsive Slideshow Added, Shortcode is: [responsive_slide id=' . $post_ID . '] ')),
            7 => __('Book saved.', 'your_text_domain'),
            8 => sprintf(__('SlideShow submitted. ', 'your_text_domain'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
            9 => sprintf(__('SlideShow scheduled for: <strong>%1$s</strong>.', 'your_text_domain'),
                    // translators: Publish box date format, see http://php.net/date
                    date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
            10 => sprintf(__('SlideShow draft updated.', 'your_text_domain'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
        );

        return $messages;
    }

    /**
     * The shortcode, handler method
     * 
     * @param type $atts
     * @return type 
     */
    public function responsive_shortcode($atts) {

        extract(shortcode_atts(array(
                    'id' => 1,
                        ), $atts));
        $postKey = $id;
        if (get_post_status($id) != 'trash') {
            $image_src = get_post_meta($postKey, 'responsive_slide_' . $id);
            $slideshow_config = get_post_meta($postKey, 'responsive_slide_config_' . $id);
            $image_src = explode(',', $image_src[0]);
            $result = $this->responsive_render_slideshow($image_src);
            ?>   
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.rslides').responsiveSlides(<?php echo $slideshow_config[0]; ?>);
                });
            </script>
            <?php
            echo $result;
        } else {
            $wp_error_obj = new WP_Error();
            $wp_error_obj->add($id, 'Slide ID: ' . $id . ' is in TRASH');
            echo $wp_error_obj->get_error_message($id);
        }
    }

    /**
     * Removes view from the action links
     * @param  array  $action
     * @return array  
     */
    public function responsive_remove_view_from_action($action) {

        if ($this->responsive_is_page_responsive_post() == TRUE) {
            unset($action['view']);
            return $action;
        } else {
            return $action;
        }
    }

    /**
     * CHecks if the current page, is Responsive Post 
     * @global object $post
     * @return boolean  TRUE, if current page is responsive post
     */
    private function responsive_is_page_responsive_post() {
        global $post;
        if (get_post_type($post) == 'responsive_post') {
            return TRUE;
        }
    }

    /**
     *  Hides the publishing and minor actions, from the Post side widget
     * @global object $post Wordpress post object
     */
    public function responsive_hide_minor_publishing() {

        if ($this->responsive_is_page_responsive_post() == TRUE) {
            ?><style>
                #misc-publishing-actions, #minor-publishing-actions {
                    display:none;
                }
            </style>
            <?php
        }
    }

    /**
     * Handles all meta boxes  
     */
    public function responsive_metabox_handler() {
        add_meta_box('slideshow_config', 'Slideshow Settings', array($this, 'responsive_slideshow_config'), 'responsive_post', 'normal', 'high');
        add_meta_box('picture_upload', 'Upload Images', array($this, 'responsive_image_upload_metabox'), 'responsive_post', 'normal', 'high');
        add_meta_box('tour_guide', 'Help Guide', array($this, 'responsive_side_tour_guide'), 'responsive_post', 'side', 'low');
        add_meta_box('unique_metabox', 'Slide ID', array($this, 'responsive_side_shortcode_metabox'), 'responsive_post', 'side', 'high');
    }

    private function responsive_get_config_data($config) {

        global $post;
        $data = get_post_meta($post->ID, 'responsive_slide_config_' . $post->ID);
        if (isset($data[0])) {
            $json_decoded = json_decode($data[0]);
            if (isset($json_decoded->$config)) {
                return $json_decoded->$config;
            }
        }
    }

    public function responsive_slideshow_config() {
        global $post;
        $data = get_post_meta($post->ID, 'responsive_slide_config_' . $post->ID);
        if (isset($data[0])) {
            $json_decoded = json_decode($data[0]);
        }
        ?>     
        <table>
            <tr valign="top"><td><label>Auto: </label></td>
                <td><select name="auto">
                        <option value="true"  <?php if (isSet($json_decoded) && $json_decoded->auto == TRUE) { ?> selected   <?php } ?> >true</option>
                        <option value="false" <?php if (isSet($json_decoded) && $json_decoded->auto == FALSE) { ?> selected <?php } ?>>false</option>
                    </select>
                    <br><span class="description"> Boolean: Show pager, true or false</span></td>
            </tr>
            <tr valign="top">
                <td><label>Speed: </label></td>
                <td><input type="text" name="speed" value="<?php
        if (isSet($json_decoded)) {
            echo esc_html($json_decoded->speed);
        }
        ?>" />
                    <br><span class="description">Integer: Speed of the transition, in milliseconds </span></td>
            </tr>
            <tr valign="top">
                <td><label>Timeout: </label></td>
                <td><input type="text" name="timeout" value="<?php
                   if (isSet($json_decoded)) {
                       echo esc_html($json_decoded->timeout);
                   }
        ?>" />
                    <br><span class="description">Integer:Time between slide transitions, in milliseconds</span></td>
            </tr>
            <tr valign="top"><td><label>Pager: </label></td>
                <td><select name="pager">
                        <option value="false" <?php if (isSet($json_decoded->pager) && $json_decoded->pager == FALSE) { ?>   selected <?php } ?>>false</option>
                        <option value="true"  <?php if (isSet($json_decoded) && $json_decoded->pager == TRUE) { ?> selected  <?php } ?>>true</option>
                    </select>
                    <br><span class="description"> Boolean: Show pager, true or false</span></td>
            </tr>
            <tr valign="top"><td><label>Nav: </label></td>
                <td><select name="nav">
                        <option value="false" <?php if (isSet($json_decoded) && $json_decoded->nav == FALSE) { ?>     selected   <?php } ?>>false</option>
                        <option value="true"  <?php if (isSet($json_decoded) && $json_decoded->nav == TRUE) { ?>      selected       <?php } ?>>true</option>
                    </select>
                    <br><span class="description"> Boolean: Show Navigation, true or false</span></td>
            </tr>
            <tr valign="top"><td><label>Random: </label></td>
                <td><select name="random">
                        <option value="false" <?php if (isSet($json_decoded) && $json_decoded->random == FALSE) { ?>     selected        <?php } ?>>false</option>
                        <option value="true"  <?php if (isSet($json_decoded) && $json_decoded->random == TRUE) { ?>    selected      <?php } ?>>true</option>
                    </select>
                    <br><span class="description">  Randomize the order of the slides, true or false</span></td>
            </tr>
            <tr valign="top"><td><label>Pause </label></td>
                <td><select name="pause">
                        <option value="false"  <?php
                   if (isSet($json_decoded) && $json_decoded->pause == FALSE) {
                       echo esc_html('selected ="selected"');
                   }
        ?>>false</option>
                        <option value="true"   <?php
                        if (isSet($json_decoded) && $json_decoded->pause == TRUE) {
                            echo esc_html('selected ="selected"');
                        }
        ?>>true</option>
                    </select>
                    <br><span class="description">  Pause on hover, true or false</span></td>
            </tr>
            <tr valign="top">
                <td><label>Namespace: </label></td>
                <td><input type="text" name="namespace" value="<?php
                        if (isSet($json_decoded)) {
                            echo esc_html($json_decoded->namespace);
                        }
        ?>" />
                    <br><span class="description">Change the default namespace used </span></td>
            </tr>
        </table>
        <?php
    }

    /**
     * The save method, which gets called when post is being saved or updated
     * 
     * Updates the postmeta table with the key being the post key.
     * 
     * @global  object $post  Wordpress Post Object
     * @param   object $post  Wordpress Post Object
     */
    public function responsive_save_post($post) {
        global $post;
        if (isset($_POST['images'])) {
            $img = $_POST['images'];
        }
        if (isset($post)) {
            if (!wp_is_post_revision($post->ID)) {
                if (isset($img)) {
                    update_post_meta($post->ID, 'responsive_slide_' . $post->ID, $img);
                    $json_config = $this->responsive_convert_config_to_json();
                    update_post_meta($post->ID, 'responsive_slide_config_' . $post->ID, $json_config);
                }
            }
        }
    }

    /**
     * Converts the received configuration into json format 
     * 
     * @return json  
     */
    private function responsive_convert_config_to_json() {

        $auto = $this->responsive_is_config_set('auto');
        $speed = $this->responsive_is_config_set('speed');
        $timeout = $this->responsive_is_config_set('timeout');
        $pager = $this->responsive_is_config_set('pager');
        $nav = $this->responsive_is_config_set('nav');
        $pause = $this->responsive_is_config_set('pause');
        $random = $this->responsive_is_config_set('random');
        $callback = $this->responsive_is_config_set('namespace');

        $config = array(
            'auto' => $auto,
            'speed' => (int) esc_html($speed),
            'timeout' => (int) esc_html($timeout),
            'pager' => $pager,
            'nav' => $nav,
            'pause' => $pause,
            'random' => $random,
            'namespace' => (string) $callback
        );
        return json_encode($config);
    }

    private function responsive_is_config_set($param) {

        $config = array(
            'auto' => FALSE,
            'speed' => 500,
            'timeout' => 4000,
            'pager' => FALSE,
            'pause' => FALSE,
            'random' => FALSE,
            'namespace' => 'callbacks'
        );

        if (isSet($_POST[$param]) && !empty($_POST[$param])) {
            if ($_POST[$param] === "true") {
                return TRUE;
            } elseif ($_POST[$param] === "false") {
                return FALSE;
            } else {
                return $_POST[$param];
            }
        } else {
            return $config[$param];
        }
    }

    /**
     * The images upload meta box 
     * 
     * @global  object $post  Wordpress object
     * @param   object $post  Wordpress object
     */
    public function responsive_image_upload_metabox($post) {
        global $post;
        $data = get_post_meta($post->ID, 'responsive_slide_' . $post->ID);
        if (empty($data)) {
            $data = '';
        } else {
            $data = $data[0];
        }
        $svalue = $data; // this will be initial value of the above form field. Image urls.
        ?><input type="hidden" name="images" id="images" value="<?php echo $svalue; ?>" />
        <div class="plupload-upload-uic hide-if-no-js plupload-upload-uic-multiple " id="imagesplupload-upload-ui"> 
            <input id="imagesplupload-browse-button" type="button" value="Select Image" class="button" />
            <span class="ajaxnonceplu" id="ajaxnonceplu<?php echo wp_create_nonce("images" . 'pluploadan'); ?>"></span>
            <div class = "filelist"></div>
        </div>
        <ul class = "plupload-thumbs plupload-thumbs-multiple" id = "imagesplupload-thumbs">
        </ul>
        <div class = "clear"></div>
        <?php
    }

    /**
     *  Handles the image upload process
     *  
     */
    public function responsive_image_upload_handler() {
        $imgid = $_POST["imgid"];
        check_ajax_referer($imgid . 'pluploadan');
        $status = wp_handle_upload($_FILES[$imgid . 'async-upload'], array('test_form' => true, 'action' => 'plupload_action'));
        echo $status['url'];
        exit;
    }

    /**
     *  Creates the JS config of plUploads
     */
    public function responsive_admin_js_config() {
        $plupload_init = array(
            'runtimes' => 'html5,silverlight,flash,html4',
            'browse_button' => 'plupload-browse-button',
            'container' => 'plupload-upload-ui',
            'drop_element' => 'drag-drop-area',
            'file_data_name' => 'async-upload',
            'multiple_queues' => true,
            'max_file_size' => wp_max_upload_size() . 'b',
            'url' => admin_url('admin-ajax.php'),
            'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
            'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
            'filters' => array(array('title' => __('Allowed Files'), 'extensions' => 'jpg,jpeg,gif,png')),
            'multipart' => true,
            'urlstream_upload' => true,
            'multi_selection' => false,
            'multipart_params' => array(
                '_ajax_nonce' => "",
                'action' => 'plupload_action',
                'imgid' => 0
            )
        );
        $output = ' <script type="text/javascript">';
        $output .= 'var base_plupload_config=' . json_encode($plupload_init) . '';
        $output .= '</script>';
        if ($this->responsive_is_page_responsive_post() == TRUE) {
            echo $output;
        }
    }

    /**
     * Creates the side meta box, which displays the shortcode of the post
     * @param type $post_id
     * @return type 
     */
    public function responsive_side_shortcode_metabox($post) {
        global $post;
        $messsage = "<label>Your Shortcode is:<br> [responsive_slide id=$post->ID]</label>";
        echo $messsage;
    }

    /**
     * Creates the side meta box, which displays the shortcode of the post
     * @param type $post_id
     * @return type 
     */
    public function responsive_side_tour_guide($post) {
        $messsage = "<a id='tour-guide-btn' class='button-secondary' >Run Help Tour</a>";
        echo $messsage;
    }

    /**
     * Creates a responsive slideshow html based upon the images received
     * @param  array  $images  Containing the image path
     * @return string  
     */
    public function responsive_render_slideshow($images) {

        $output = '<div class="callbacks_container">';
        $output .= '<ul class="rslides">';
        foreach ($images as $img) {
            $output .= '<li><img src="' . $img . '" /></li>';
        }
        $output .= '</ul>';
        $output .= '</div>';
        return $output;
    }

    public function responsive_image_column_header($defaults) {
        $custom_column_order = array();
        if ($this->responsive_is_page_responsive_post() == TRUE) {
            $defaults['shortcode_key'] = 'Shortcode';
            $defaults['slideshow_images'] = 'Images';
            foreach ($defaults as $key => $value) {
                if ($key == 'author') {
                    $custom_column_order['shortcode_key'] = $value;
                }
                $custom_column_order[$key] = $value;
            }
            return $custom_column_order;
        } else {
            return $defaults;
        }
    }

    public function responsive_image_column_content($column_name, $postID) {

        if ($this->responsive_is_page_responsive_post() == TRUE) {

            if ($column_name == 'slideshow_images' &&
                    get_post_status($postID) != 'draft') {
                $data = get_post_meta($postID, 'responsive_slide_' . $postID);
                if (isset($data[0])) {
                    $data = explode(',', $data[0]);
                    if (!empty($data[0])) {
                        $output = '<ul class="responsive-image-column">';
                        foreach ($data as $img) {
                            $output .= '<li><img src=' . $img . '></li>';
                        }
                        $output .= '</ul>';
                        echo $output;
                    }
                }
            }

            if ($column_name == 'shortcode_key' &&
                    get_post_status($postID) != 'draft') {
                $output = '[responsive_slide id=' . $postID . ']';
                echo $output;
            }
        }
    }

}

$responsiveSlideShow = new Responsive_Slides();



