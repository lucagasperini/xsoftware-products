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
                        'image' => [
                                'name' => 'Image',
                                'type' => 'img',
                        ],
                        'language' => [
                                'name' => 'Language',
                                'type' => 'lang',
                        ],
                        'descr' => [
                                'name' => 'Description',
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
                add_post_type_support('xs_product', array('title') );
        }
        
                
        function metaboxes()
        {
                add_meta_box( 'xs_products_metaboxes', 'XSoftware Products', array($this,'metaboxes_print'), ['xs_product'],'advanced','high');
        }
        
        function metaboxes_print()
        {
                xs_framework::init_admin_script();
                xs_framework::init_admin_style();
                wp_enqueue_media();
                
                global $post;
                $values = get_post_custom( $post->ID );
                
                foreach($this->options['fields'] as $key => $single) {
                        $selected[$key] = $single;
                        $selected[$key]['value'] = isset( $values['xs_products_'.$key][0] ) ? $values['xs_products_'.$key][0] : '';
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
                                                'id' => 'xs_products_'.$key,
                                        ]);
                                        break;
                                case 'lang':
                                        $languages = xs_framework::get_available_language();
                
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_select( array(
                                                'name' => 'xs_products_'.$key, 
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
                                                'name' => 'xs_products_'.$key,
                                                'text' => $single['value'],
                                                'return' => true
                                        ));
                                        break;
                                default:
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_input( array(
                                                'class' => 'xs_full_width', 
                                                'name' => 'xs_products_'.$key,
                                                'value' => $single['value'],
                                                'return' => true
                                        ));
                        }
                        
                }
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'data' => $data ));
        }
        
        function save($post_id, $post)
        {
                $post_type = get_post_type($post_id);
                if ( $post_type != 'xs_product' ) return;
                
                foreach($this->options['fields'] as $key => $single) {
                        if(isset($_POST['xs_products_'.$key]))
                                update_post_meta( $post_id, 'xs_products_'.$key, $_POST['xs_products_'.$key] );
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
        
        function input_fields($input)
        {
                if(!empty($input['new']['Field']) && !empty($input['new']['Type'])) {
                        $this->db->field_add(sanitize_text_field($input['new']['Field']), sanitize_text_field($input['new']['Type']));
                }
                if(!empty($input['delete'])) {
                        $this->db->field_remove(sanitize_text_field($input['delete']));
                }
                
                unset($input);
        }
        
        public function input_products($input)
        {
                if(isset($input['new'])) {
                        $add_product = $input['new']; // copy variable
                        unset($input['new']); //unset new from input list
                }
                
                if(!empty($input['delete'])) 
                        $this->db->products_remove($input['delete']); //remove product from database first
                        
                unset($input['delete']);//unset new from input list
                
                $this->db->products_update($input); //update database with current input list
                
                if(!empty($add_product['name']))
                        $this->db->products_add($add_product); //add new product ro database at end
                
                unset($input); // clear memory
        }
        
        function show_globals()
        {
                $settings_field = array('value' => $this->globals['template_file'], 'name' => 'xs_products_globals[template_file]');
                add_settings_field($settings_field['name'], 
                'Template file path:',
                'xs_framework::create_input',
                'globals',
                'section_globals',
                $settings_field);
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
                                'text' => 'Remove', 
                                'return' => true
                        ));
                        $data[$key][1] = $key;
                        $data[$key][2] = $single['name'];
                        $data[$key][3] = $single['type'];
                }
                
                $new[0] = '';
                $new[1] = xs_framework::create_input(array('name' => 'xs_options_products[fields][new][code]', 'return' => true));
                $new[2] = xs_framework::create_input(array('name' => 'xs_options_products[fields][new][name]', 'return' => true));
                $new[3] = xs_framework::create_input(array('name' => 'xs_options_products[fields][new][type]', 'return' => true));
                
                $data[] = $new;
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data));
        }
        
        function show_products()
        {
                if(!isset($_GET["edit"])) {
                        $this->show_products_all();
                        return;
                }
                
                $get = $_GET["edit"];
                
                if($get == "new") {
                        $this->show_product_edit_add();
                        return;
                }
                
                if ($get == "all") {
                        $this->show_product_edit_all();
                        return;
                }
                
                $products = $this->db->products_get(NULL, $get);
                if(isset($products[0])) {
                        $this->show_product_edit_single($products[0]);
                        return;
                }
        }
        
        public function show_product_edit_single($single)
        {
                xs_framework::create_link(array('href' => 'admin.php?page=xsoftware_products', 'class' => 'button-primary', 'text' => 'Back'));
                
                $fields = $this->db->fields_get_name();
                $size_fields = count($fields);
                
                $langs = xs_framework::get_available_language();
                
                $headers = array('Field', 'Value');
                $data = array();
                
                for($i = 0; $i < $size_fields; $i++ )
                {
                        $data[$i][0] = $current_field = $fields[$i];
                        
                        if($current_field == 'id')
                                $data[$i][1] = xs_framework::create_input( array(
                                        'class' => 'xs_full_width', 
                                        'value' => $single['id'], 
                                        'name' => 'products[0][id]', 
                                        'readonly' => true, 
                                        'type' => 'text', 
                                        'return' => true
                                ));
                        else if($current_field == 'lang')
                                $data[$i][1] = xs_framework::create_select( array(
                                        'class' => 'xs_full_width', 
                                        'name' => 'products[0][lang]', 
                                        'data' => $langs, 
                                        'selected' => $single['lang'],
                                        'return' => true
                                ));
                        else
                                $data[$i][1] = xs_framework::create_textarea( array(
                                        'class' => 'xs_full_width', 
                                        'text' => $single[$current_field], 
                                        'name' => 'products[0]['.$current_field.']', 
                                        'return' => true
                                ));
                }
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data ));
        }
        
        public function show_product_edit_all()
        {
                xs_framework::create_link( array(
                        'href' => 'admin.php?page=xsoftware_products', 
                        'class' => 'button-primary', 
                        'text' => 'Back'
                ));
                
                $products = $this->db->products_get();
                $fields = $this->db->fields_get_name();
                
                $langs = xs_framework::get_available_language();
                
                for($i = 0; $i < count($products); $i++)
                {
                        $actions = xs_framework::create_link( array(
                                'href' => 'admin.php?page=xsoftware_products&edit='.$products[$i]['id'], 
                                'class' => 'button-primary xs_full_width xs_text_center', 
                                'text' => 'Show', 
                                'return' => true
                        ));
                        $actions .= xs_framework::create_button( array( 
                                'name' => 'products[delete]', 
                                'class' => 'button-primary xs_full_width', 
                                'value' => $products[$i]['id'], 
                                'text' => 'Remove', 
                                'onclick'=>'return confirm_box();', 
                                'return' => true
                        ));
                        array_unshift($products[$i], $actions);
                        foreach($fields as $current_field) {
                                if($current_field == 'id')
                                        $products[$i]['id'] = xs_framework::create_input( array(
                                                'value' => $products[$i]['id'], 
                                                'name' => 'products['.$i.'][id]', 
                                                'readonly' => true, 
                                                'type' => 'text', 
                                                'return' => true
                                        ));
                                else if($current_field == 'lang')
                                        $products[$i]['lang'] = xs_framework::create_select( array( 
                                                'name' => 'products['.$i.'][lang]', 
                                                'data' => $langs, 
                                                'selected' => $products[$i]['lang'],
                                                'return' => true
                                        ));
                                else
                                        $products[$i][$current_field] = xs_framework::create_textarea( array(
                                                'text' => $products[$i][$current_field], 
                                                'name' => 'products['.$i.']['.$current_field.']', 
                                                'return' => true
                                        ));
                        }
                        
                }
                
                array_unshift($fields, "Actions");
                
                xs_framework::create_table(array('headers' => $fields, 'data' => $products ));
        
        }
        
        public function show_product_edit_add()
        {
                xs_framework::create_link(array('href' => 'admin.php?page=xsoftware_products', 'class' => 'button-primary', 'text' => 'Back'));
                
                $fields = $this->db->fields_get_name_skip(array('id'));
                $size_fields = count($fields);
                
                $langs = xs_framework::get_available_language();
                
                $headers = array('Field', 'Value');
                $data = array();
                
                for($i = 0; $i < $size_fields; $i++ )
                {
                        $data[$i][0] = $current_field = $fields[$i];
                        
                        if($current_field == 'lang')
                                $data[$i][1] = xs_framework::create_select( array(
                                        'class' => 'xs_full_width', 
                                        'name' => 'products[new][lang]', 
                                        'data' => $langs,
                                        'return' => true
                                ));
                        else
                                $data[$i][1] = xs_framework::create_textarea( array(
                                        'class' => 'xs_full_width', 
                                        'name' => 'products[new]['.$current_field.']', 
                                        'return' => true
                                ));
                }
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data ));
        }

        
        function show_products_all()
        {
                xs_framework::create_link(array('href' => 'admin.php?page=xsoftware_products&edit=new', 'class' => 'button-primary', 'text' => 'Add a product'));
                xs_framework::create_link(array('href' => 'admin.php?page=xsoftware_products&edit=all', 'class' => 'button-primary', 'text' => 'Edit all products'));
                
                $fields = $this->db->fields_get_name();
                $products = $this->db->products_get();
                
                $fields_name[] = "Actions";
                foreach($fields as $single)
                        $fields_name[] = $single;
                
                for($i = 0; $i < count($products); $i++) {
                        $actions = xs_framework::create_link( array(
                                'href' => 'admin.php?page=xsoftware_products&edit='.$products[$i]['id'], 
                                'class' => 'button-primary xs_full_width xs_text_center', 
                                'text' => 'Show', 
                                'return' => true
                        ));
                        $actions .= xs_framework::create_button( array( 
                                'name' => 'products[delete]', 
                                'class' => 'button-primary xs_full_width', 
                                'value' => $products[$i]['id'], 
                                'text' => 'Remove', 
                                'onclick'=>'return confirm_box();', 
                                'return' => true
                        ));
                        
                        array_unshift($products[$i], $actions);
                }
                
                xs_framework::create_table(array('headers' => $fields_name, 'data' => $products));
        }

        /* Dynamic Page Content */
        function dpc ($attr)
        {
                include $this->globals["template_file"];
                extract( shortcode_atts( array( 'lang' => '', 'product' => '' , 'field' => ''), $attr ) );
                $lang = NULL;
                
                if(!empty($attr['lang']))
                        $lang =  $attr['lang'];
                        
                if(isset($_GET['product']))
                        $name = $_GET['product'];
                if(isset($attr['product']) && !empty($attr['product']))
                        $name = $attr['product'];
                        
                if(isset($name)) {
                        $single = $this->db->products_get_by_name($name,$lang);
                        if(count($single) != 1)
                                unset($single);
                        else
                                $single = $single[0];
                }
                if(!isset($single))
                        $products = $this->db->products_get($lang);
                
                
                if(isset($single) && empty($attr['field']))
                        products_single($single);
                if(isset($single) && !empty($attr['field']))
                        echo $single[$attr['field']];
                if(isset($products))
                        products_main($products);
        }

}

$plugin_product = new xs_products_plugin();

?>
