<?php
/*
Plugin Name: XSoftware Products
Description: Products management on wordpress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.it/
Text Domain: xsoftware_products
*/

if(!defined("ABSPATH")) die;

if (!class_exists("xs_products_plugin")) :

include 'xsoftware-products-options.php';

class xs_products_plugin
{

        private $options = array( );

        private $constant_types = ['img', 'bool', 'int', 'url'];

        public function __construct()
        {
                add_action('init', array($this, 'setup'));
                add_action('save_post', array($this,'metaboxes_save'), 10, 2 );
                add_filter('single_template', array($this,'single'));
                add_filter('archive_template', array($this,'archive'));
                add_action('add_meta_boxes', array($this, 'metaboxes'));
                add_filter( 'manage_xs_product_posts_columns', array($this,'add_columns') );
                add_shortcode('xs_product_archive', [$this,'shortcode_archive']);

                $this->options = get_option('xs_options_products');

        }

        function shortcode_archive($attr)
        {
                $a = shortcode_atts(
                        [
                                'cat' => ''
                        ],
                        $attr
                );

                $user_lang = xs_framework::get_user_language();
                /* FIXME: Add option where set numberposts */
                $archive = get_posts([
                        'numberposts' => 20,
                        'post_type' => 'xs_product',
                        'meta_key' => 'xs_products_category',
                        'meta_value' => $a['cat']
                ]);

                return apply_filters('xs_product_archive_html', $archive, $user_lang);
        }

        function setup()
        {
                xs_framework::register_plugin(
                        'xs_products',
                        'xs_options_products'
                );

                register_post_type(
                        'xs_product',
                        array(
                                'labels' => array(
                                        'name' => __( 'Products' ),
                                        'singular_name' => __( 'Product' )
                                ),
                                'public' => true,
                                'has_archive' => true,
                                'rewrite' => array('slug' => 'product'),
                                'hierarchical' => true
                        )
                );
                add_post_type_support('xs_product', array('title', 'thumbnail') );
                remove_post_type_support('xs_product', 'editor');
        }


        function metaboxes()
        {
                add_meta_box(
                        'xs_products_metaboxes_common',
                        'XSoftware Products',
                        array($this,'metaboxes_common_print'),
                        ['xs_product'],
                        'advanced',
                        'high'
                );

                $languages = xs_framework::get_available_language();
                foreach($languages as $code => $name) {
                        add_meta_box(
                                'xs_products_metaboxes_'.$code,
                                'XSoftware Products '. $name,
                                array($this,'metaboxes_lang_print'),
                                ['xs_product'],
                                'advanced',
                                'high',
                                $code
                        );
                }
        }
        function metaboxes_common_print($post)
        {
                $post_meta = get_post_meta( $post->ID );

                $category = isset($post_meta['xs_products_category'][0]) &&
                        $post_meta['xs_products_category'][0]  !== 'default' ?
                        $post_meta['xs_products_category'][0] : 'default';

                foreach($this->options['category'] as $key => $prop)
                        $cat_list[$key] = $prop['info']['name'];

                $data[0][0] = 'Select a Category:';
                $data[0][1] = xs_framework::create_select( array(
                        'name' => 'xs_products_category',
                        'selected' => $category,
                        'data' => $cat_list,
                        'default' => 'Select a Category'
                ));

                xs_framework::create_table(array('data' => $data ));

                $data = array();

                foreach($this->options['category'][$category]['field'] as $key => $single) {
                        if(in_array($single['type'],$this->constant_types)) {
                                $tmp['name'] = 'xs_products_const_'.$key;
                                $tmp['label'] = $single['name'].':';
                                $tmp['class'] = 'xs_full_width';
                                $tmp['type'] = $single['type'];
                                $tmp['value'] = isset($post_meta[$tmp['name']][0]) ?
                                        $post_meta[$tmp['name']][0] :
                                        '';

                                if($tmp['type'] === 'img')
                                        $tmp['id'] = $tmp['name'];

                                $data[] = $tmp;
                        }
                }

                xs_framework::html_input_array_to_table(
                        $data,
                        [ 'class' => 'xs_full_width' ]
                );
        }

        function metaboxes_lang_print($post, $lang_code)
        {
                $lang_code = $lang_code['args'];

                $post_meta = get_post_meta( $post->ID );

                $category = isset($post_meta['xs_products_category'][0]) ?
                        $post_meta['xs_products_category'][0] :
                        'default';

                $data = array();

                foreach($this->options['category'][$category]['field'] as $key => $single) {
                        if(!in_array($single['type'],$this->constant_types)) {
                                $tmp['name'] = 'xs_products_'.$key.'_'.$lang_code;
                                $tmp['label'] = $single['name'];
                                $tmp['class'] = 'xs_full_width';
                                $tmp['type'] = $single['type'];
                                $tmp['value'] = isset($post_meta[$tmp['name']][0]) ?
                                        $post_meta[$tmp['name']][0] :
                                        '';

                                if($tmp['type'] === 'img')
                                        $tmp['id'] = $tmp['name'];

                                $data[] = $tmp;
                        }
                }

                xs_framework::html_input_array_to_table(
                        $data,
                        [ 'class' => 'xs_full_width' ]
                );
        }

        function metaboxes_save($post_id, $post)
        {
                $post_status = get_post_status( $post_id );
                $post_type = get_post_type($post_id);

                if($post_status === 'auto-draft' || $post_status === 'trash')
                        return;
                if($post_type !== 'xs_product')
                        return;

                if(isset($_POST['xs_products_category'])) {
                        $current_category = $_POST['xs_products_category'];
                        update_post_meta( $post_id, 'xs_products_category', $current_category );
                } else {
                        $values = get_post_meta( $post->ID );
                        $current_category = isset($values['xs_products_category'][0]) ?
                                $values['xs_products_category'][0] :
                                'default';
                }

                $languages = xs_framework::get_available_language();

                foreach($this->options['category'][$current_category]['field'] as $key => $single) {
                        if(!in_array($single['type'],$this->constant_types)) {
                                foreach($languages as $code => $name) {
                                        if(isset($_POST['xs_products_'.$key.'_'.$code])) {
                                                update_post_meta(
                                                        $post_id,
                                                        'xs_products_'.$key.'_'.$code,
                                                        $_POST['xs_products_'.$key.'_'.$code]
                                                );
                                        }
                                }
                        } else {
                                if(isset($_POST['xs_products_const_'.$key])) {
                                        update_post_meta(
                                                $post_id,
                                                'xs_products_const_'.$key,
                                                $_POST['xs_products_const_'.$key]
                                        );
                                }
                        }
                }
        }

        function single($single)
        {
                global $post;

                if(empty($post)) return $single;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_product' ) {
                        if ( file_exists(  dirname( __FILE__ ) . '/single.php' ) ) {
                                return  dirname( __FILE__ ) . '/single.php';
                        }
                }

                return $single;
        }

        function archive($single)
        {
                global $post;

                if(empty($post)) return $single;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_product' ) {
                        wp_redirect($this->options['category']['default']['info']['archive']);
                        exit;
                }

                return $single;
        }

        function add_columns($columns)
        {
                if(isset($columns['language']))
                        unset($columns['language']);

                if(isset($columns['native']))
                        unset($columns['native']);

                return $columns;
        }
}

endif;

$xs_products_plugin = new xs_products_plugin();

?>
