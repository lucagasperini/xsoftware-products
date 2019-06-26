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


        public function __construct()
        {;
                add_action('init', array($this, 'setup'));
                add_action('save_post', array($this,'save'), 10, 2 );
                add_filter('single_template', array($this,'single'));
                add_filter('archive_template', array($this,'archive'));
                add_action('add_meta_boxes', array($this, 'metaboxes'));
                add_filter( 'manage_xs_product_posts_columns', array($this,'add_columns') );
                add_shortcode('xs_product_archive', [$this,'shortcode_archive']);
                add_filter('xs_product_archive_html', [ $this, 'archive_html' ], 0, 2);
                add_filter('xs_product_single_html', [ $this, 'single_html' ], 0, 2);

                $this->options = get_option('xs_options_products');

        }

        function single_html($id, $single)
        {
                wp_enqueue_style(
                        'xs_product_template',
                        plugins_url('style/template.min.css', __FILE__)
                );
                $image = get_the_post_thumbnail_url( $id, 'medium' );
                $title = get_the_title($id);
                echo '<div class="product_item">';
                echo '<div class="product_content">';
                echo '<h1 class="product_title">'.$title.'</h1>';
                echo '<p class="product_descr">'.$single['descr'].'</p>';
                echo '<p>'.$single['text'].'</p>';
                echo '</div>';
                echo '<img class="product_img" src="'.$image.'"/>';
                echo '</div>';
        }

        function archive_html($archive, $user_lang)
        {
                $output = '';
                wp_enqueue_style(
                        'xs_product_template',
                        plugins_url('style/template.min.css', __FILE__)
                );
                foreach($archive as $single) {
                        $image = get_the_post_thumbnail_url( $single, 'medium' );
                        $title = get_the_title($single);
                        $link = get_the_permalink($single);
                        $descr = get_post_meta(
                                $single->ID,
                                'xs_products_descr_'.$user_lang,
                                true
                        );

                        $output .= '<a href="'.$link.'">';
                        $output .= '<div class="product_list_item">';
                        $output .= '<div class="product_list_item_text">';
                        $output .= '<h2>'.$title.'</h2>';
                        $output .= '<span>'.$descr.'</span>';
                        $output .= '</div>';
                        $output .= '<img src="'.$image.'" /></div></a>';
                }

                return $output;
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

                $this->install_template();
                $this->create_post_type();
        }

        function install_template()
        {

                if(is_dir(XS_CONTENT_DIR) === FALSE)
                        mkdir(XS_CONTENT_DIR, 0755);
                $products_dir = XS_CONTENT_DIR . 'products/';
                if(is_dir($products_dir) === FALSE)
                        mkdir($products_dir, 0755);

                $template_dir = $products_dir . 'template/';
                if(is_dir($template_dir) === FALSE)
                        mkdir($template_dir, 0755);

                $default_dir = $template_dir . 'default/';
                if(is_dir($default_dir) === FALSE)
                        mkdir($default_dir, 0755);

        }

        function create_post_type()
        {
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
                $values = get_post_meta( $post->ID );

                $category = isset($values['xs_products_category'][0]) &&
                        $values['xs_products_category'][0]  !== 'default' ?
                        $values['xs_products_category'][0] : 'default';

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
        }

        function metaboxes_lang_print($post, $lang_code)
        {

                $lang_code = $lang_code['args'];
                $values = get_post_meta( $post->ID );

                $category = isset($values['xs_products_category'][0]) ? $values['xs_products_category'][0] : 'default';

                foreach($this->options['category'][$category]['field'] as $key => $single) {
                        $selected[$key] = $single;
                        $selected[$key]['value'] = isset( $values['xs_products_'.$key.'_'.$lang_code][0] ) ?
                                $values['xs_products_'.$key.'_'.$lang_code][0] : array();
                }

                $data = array();

                /* TODO: Use xs_framework::html_input_array_to_table to print those fields? */
                foreach($selected as $key => $single) {
                        switch($single['type']) {
                                case 'img':
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_select_media_gallery([
                                                'src' => $single['value'],
                                                'width' => 150,
                                                'height' => 150,
                                                'alt' => $single['name'],
                                                'id' => 'xs_products_'.$key.'_'.$lang_code,
                                        ]);
                                        break;
                                case 'lang':
                                        $languages = xs_framework::get_available_language();

                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_select( array(
                                                'name' => 'xs_products_'.$key.'_'.$lang_code,
                                                'selected' => $single['value'],
                                                'data' => $languages,
                                                'default' => 'Select a Language'
                                        ));
                                        break;
                                case 'text':
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_textarea( array(
                                                'class' => 'xs_full_width',
                                                'name' => 'xs_products_'.$key.'_'.$lang_code,
                                                'text' => $single['value']
                                        ));
                                        break;
                                case 'field':
                                        $value = unserialize($single['value']);

                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_textarea( array(
                                                'class' => 'xs_full_width',
                                                'name' => 'xs_products_'.$key.'_'.$lang_code.'[a]',
                                                'text' => $value['a']
                                        ));
                                        $data[$key][2] = xs_framework::create_textarea( array(
                                                'class' => 'xs_full_width',
                                                'name' => 'xs_products_'.$key.'_'.$lang_code.'[b]',
                                                'text' => $value['b']
                                        ));
                                        break;
                                case 'link':
                                        $value = unserialize($single['value']);

                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_input( array(
                                                'class' => 'xs_full_width',
                                                'name' => 'xs_products_'.$key.'_'.$lang_code.'[url]',
                                                'value' => $value['url']
                                        ));
                                        $data[$key][2] = xs_framework::create_input( array(
                                                'class' => 'xs_full_width',
                                                'name' => 'xs_products_'.$key.'_'.$lang_code.'[text]',
                                                'value' => $value['text']
                                        ));
                                        break;
                                default:
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_input( array(
                                                'class' => 'xs_full_width',
                                                'name' => 'xs_products_'.$key.'_'.$lang_code,
                                                'value' => $single['value']
                                        ));
                        }

                }

                xs_framework::create_table(array('class' => 'xs_full_width', 'data' => $data ));
        }

        function save($post_id, $post)
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
                        foreach($languages as $code => $name) {
                                if(isset($_POST['xs_products_'.$key.'_'.$code])) {
                                        update_post_meta(
                                                $post_id,
                                                'xs_products_'.$key.'_'.$code,
                                                $_POST['xs_products_'.$key.'_'.$code]
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
