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

include 'database.php';

class xs_products_plugin
{

        private $def_global = array ( 
                                        'template_file' => 'template.php'
        );
        
        private $globals = array( );
        
        private $db = NULL;
        

        public function __construct()
        {
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                $this->globals = get_option('xs_products_globals', $this->def_global);
                $this->db = new xs_products_database();
                add_shortcode( 'xsoftware_dpc_products', array($this, 'dpc') );
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
                
                $this->fields = $this->db->fields_get();
                $this->products = $this->db->products_get();
                
                xs_framework::init_admin_style();
                $this->install_style_pack();
                xs_framework::init_admin_script();
                
                echo '<div class="wrap">';

                echo '<h2>Products configuration</h2>';
                
                // <GLOBAL>
                echo '<form action="options.php" method="post">';

                settings_fields('setting_globals');
                do_settings_sections('globals');

                submit_button( '', 'primary', 'field_update', true, NULL );
                echo '</form>';
                // </GLOBAL>
                
                // <FIELDS>
                echo '<form action="options.php" method="post">';

                settings_fields('setting_field');
                do_settings_sections('fields');

                submit_button( 'Update fields', 'primary', 'field_update', true, NULL );
                echo '</form>';
                // </FIELDS>
                
                echo "<form action=\"options.php\" method=\"post\">";
                
                // <PRODUCTS>
                settings_fields('setting_product');
                do_settings_sections('products');
                // </PRODUCTS>
                
                submit_button( 'Update products', 'primary', 'product_update', true, NULL );
                
                echo "</form>";
                
                echo '</div>';
        }

        function section_menu()
        {
                register_setting( 'setting_globals', 'xs_products_globals', array($this, 'input_globals') );
                add_settings_section( 'section_globals', 'Global settings', array($this, 'show_globals'), 'globals' );
                
                register_setting( 'setting_field', 'fields', array($this, 'input_fields') );
                add_settings_section( 'section_field', 'List of fields', array($this, 'show_fields'), 'fields' );

                register_setting( 'setting_product', 'products', array($this, 'input_products') );
                add_settings_section( 'section_products', 'List of products', array($this, 'show_products'), 'products' );
        }


        function input_globals($input)
        {
                $input['template_file'] = sanitize_text_field( $input['template_file'] );
                return $input;
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
                $fields = $this->db->fields_get_skip(array('id', 'name', 'lang', 'title'));
                
                $headers = array('Actions', 'Name', 'Type');
                $data = array();
                
                foreach($fields as $current_field) {
                        $row[0] = xs_framework::create_button(array( 'name' => 'fields[delete]', 'class' => 'button-primary', 'value' => $current_field['Field'], 'text' => 'Remove', 'return' => true));
                        $row[1] = $current_field['Field'];
                        $row[2] = $current_field['Type'];
                        $data[] = $row;
                }
                
                $new[0] = '';
                $new[1] = xs_framework::create_input(array('name' => 'fields[new][Field]', 'return' => true));
                $new[2] = xs_framework::create_input(array('name' => 'fields[new][Type]', 'return' => true));
                
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
