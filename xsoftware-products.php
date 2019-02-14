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

class xs_products_plugin
{

        private $def_global = array (
                'fields' => [
                        'descr' => [
                                'name' => 'Description',
                                'type' => 'text',
                        ],
                        'text' => [
                                'name' => 'Text',
                                'type' => 'text',
                        ]
                ]
        );
        
        private $options = array( );
        
        private $db = NULL;
        

        public function __construct()
        {
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                add_action('init', array($this, 'create_post_type'));
                add_action('save_post', array($this,'save'), 10, 2 );
                add_filter('single_template', array($this,'single'));
                add_filter('archive_template', array($this,'archive'));
                add_action('add_meta_boxes', array($this, 'metaboxes'));
                
                $this->options = get_option('xs_options_products', $this->def_global);
                $this->db = NULL;
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
                $languages = xs_framework::get_available_language();
                foreach($languages as $code => $name) {
                        add_meta_box(
                                'xs_products_metaboxes_'.$code, 
                                'XSoftware Products '. $name, 
                                array($this,'metaboxes_print'), 
                                ['xs_product'],
                                'advanced',
                                'high',
                                $code
                        );
                }
        }
        
        function metaboxes_print($post, $lang_code)
        {
                $lang_code = $lang_code['args'];
                $values = get_post_custom( $post->ID );
                
                foreach($this->options['fields'] as $key => $single) {
                        $selected[$key] = $single;
                        $selected[$key]['value'] = isset( $values['xs_products_'.$key.'_'.$lang_code][0] ) ?
                                $values['xs_products_'.$key.'_'.$lang_code][0] : '';
                }
                
                $data = array();
                
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
                                                'return' => true,
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
                $post_type = get_post_type($post_id);
                if ( $post_type != 'xs_product' ) return;
                
                $languages = xs_framework::get_available_language();
                
                foreach($this->options['fields'] as $key => $single) {
                        foreach($languages as $code => $name) {
                                if(isset($_POST['xs_products_'.$key.'_'.$code]))
                                        update_post_meta( $post_id, 'xs_products_'.$key.'_'.$code, $_POST['xs_products_'.$key.'_'.$code] );
                        }
                }
        }
        
        function single($single) 
        {
                global $post;
                
                if(empty($post)) return $single;

                /* Checks for single template by post type */
                if ( $post->post_type == 'xs_product' ) {
                        if ( file_exists(  dirname( __FILE__ ) . '/template/single.php' ) ) {
                                return  dirname( __FILE__ ) . '/template/single.php';
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
                        if ( file_exists(  dirname( __FILE__ ) . '/template/archive.php' ) ) {
                                return  dirname( __FILE__ ) . '/template/archive.php';
                        }
                }

                return $single;
        }
        
        
        
        function install_style_pack()
        {
                $not_empty = FALSE;
                $style = xs_framework::get_option('style');
                if(!isset($style['.product_list_item>a>span'])) {
                        $style['.product_list_item>a>span'] = array(
                                'default' => array( 'text' => 'text' , 'bg' => 'primary', 'bord' => ''), 
                                'hover' => array( 'text' => '' , 'bg' => '', 'bord' => ''), 
                                'focus' => array( 'text' => '' , 'bg' => '', 'bord' => ''),
                        );
                        $not_empty = TRUE;
                }
                if($not_empty === TRUE)
                        xs_framework::update_option('style', $style);
        }
        

        function admin_menu()
        {
                add_submenu_page( 'xsoftware', 'XSoftware Products','Products', 'manage_options', 'xsoftware_products', array($this, 'menu_page') );
        }
        
        
        public function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }
                
                xs_framework::init_admin_style();
                $this->install_style_pack();
                xs_framework::init_admin_script();
                
                echo '<div class="wrap">';
               
                echo "<h2>Product configuration</h2>";
                
                echo '<form action="options.php" method="post">';

                settings_fields('product_setting');
                do_settings_sections('product');

                submit_button( '', 'primary', 'submit', true, NULL );
                echo '</form>';
                
                echo '</div>';
               
        }

        function section_menu()
        {
                register_setting( 'product_setting', 'xs_options_products', array($this, 'input') );
                add_settings_section( 'section_setting', 'Settings', array($this, 'show'), 'product' );
        }

        function show()
        {
                $tab = xs_framework::create_tabs( array(
                        'href' => '?page=xsoftware_products',
                        'tabs' => array(
                                'home' => 'Homepage',
                                'field' => 'Fields'
                        ),
                        'home' => 'home',
                        'name' => 'main_tab'
                ));
                
                switch($tab) {
                        case 'home':
                                return;
                        case 'field':
                                $this->show_fields();
                                return;
                }
        }

        function input($input)
        {
                $current = $this->options;
                
                if(isset($input['fields'])) {
                        $f = $input['fields'];
                        if(isset($f['new']) && !empty($f['new']['code']) && !empty($f['new']['name']) && !empty($f['new']['type'])) {
                                $code = $f['new']['code'];
                                unset($f['new']['code']);
                                $current['fields'][$code] = $f['new'];
                        }
                        if(!empty($f['delete'])) {
                                unset($current['fields'][$f['delete']]);
                        }
                }

                return $current;
        }

        function show_fields()
        {
                $fields = $this->options['fields'];
                
                $headers = array('Actions', 'Code', 'Name', 'Type');
                $data = array();
                
                foreach($fields as $key => $single) {
                        $data[$key][0] = xs_framework::create_button(array( 
                                'name' => 'xs_options_products[fields][delete]', 
                                'class' => 'button-primary', 
                                'value' => $key, 
                                'text' => 'Remove'
                        ));
                        $data[$key][1] = $key;
                        $data[$key][2] = $single['name'];
                        $data[$key][3] = $single['type'];
                }
                
                $new[0] = '';
                $new[1] = xs_framework::create_input(array('name' => 'xs_options_products[fields][new][code]'));
                $new[2] = xs_framework::create_input(array('name' => 'xs_options_products[fields][new][name]'));
                $new[3] = xs_framework::create_input(array('name' => 'xs_options_products[fields][new][type]'));
                
                $data[] = $new;
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data));
        }

}

$plugin_product = new xs_products_plugin();

?>
