<?php
/*
Plugin Name: XSoftware Products
Description: Products management on wordpress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.it/
Text Domain: xsoftware_products
*/
if(!defined('ABSPATH')) exit;

include "database.php";
include 'languages.php';

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
                $this->globals = get_option('product_global', $this->def_global);
                $this->db = new xs_products_database();
                add_shortcode( 'xsoftware_dpc_products', array($this, 'dpc') );
                
        }
        

        function admin_menu()
        {
                global $menu;
                $menuExist = false;
                foreach($menu as $item) {
                        if(strtolower($item[0]) == strtolower('XSoftware')) {
                                $menuExist = true;
                        }
                }
                
                if(!$menuExist)
                        add_menu_page( 'XSoftware', 'XSoftware', 'manage_options', 'xsoftware', array($this, 'menu_page') );
                        
                add_submenu_page( 'xsoftware', 'XSoftware Products','Products', 'manage_options', 'xsoftware_products', array($this, 'menu_page') );
                
                add_submenu_page( 'xsoftware', 'XSoftware Products', 'Edit products', 'manage_options', 'xsoftware_products_edit', array($this, 'menu_page_edit'));
        }
        
        public function menu_page_edit()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }
                
                
                xs_framework::init_admin_style();
                
                echo '<div class="wrap">';
                echo '<h2>Products configuration</h2>';
                
                echo "<form action=\"options.php\" method=\"post\">";
                
                settings_fields('setting_products_edit');
                do_settings_sections('products_edit');
                
                submit_button( 'Update products', 'primary', 'product_update', true, NULL );
                
                echo "</form>";
                echo '</div>';
        }
        
        
        public function input_products_edit($input)
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
        
        public function show_products_edit()
        {
                if(isset($_GET["id"])) {
                        if($_GET["id"] == "new") {
                                $this->show_product_edit_add();
                        } else {
                                $products = $this->db->products_get(NULL, $_GET["id"]);
                                if(isset($products[0]))
                                        $this->show_product_edit_single($products[0]);
                        }
                } else {
                        $this->show_product_edit_all();
                }
        }
        
        public function show_product_edit_single($single)
        {
                $fields = $this->db->fields_get_name();
                $size_fields = count($fields);
                
                $headers = array('Field', 'Value');
                $data = array();
                
                for($i = 0; $i < $size_fields; $i++ )
                {
                        $data[$i][0] = $current_field = $fields[$i];
                        
                        if($current_field == 'id')
                                $data[$i][1] = xs_framework::create_input(array('class' => 'xs_full_width', 'value' => $single['id'], 'name' => 'product_value[0][id]', 'readonly' => true, 'type' => 'text', 'return' => true));
                        else if($current_field == 'lang')
                                $data[$i][1] = xs_framework::create_select(array('class' => 'xs_full_width', 'name' => 'product_value[0][lang]', 'data' => xs_language::$language_codes, 'selected' => $single['lang'], 'reverse' => true, 'return' => true));
                        else
                                $data[$i][1] = xs_framework::create_textarea(array('class' => 'xs_full_width', 'text' => $single[$current_field], 'name' => 'product_value[0]['.$current_field.']', 'return' => true));
                }
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data ));
        }
        
        public function show_product_edit_all()
        {
                $products = $this->db->products_get();
                $fields = $this->db->fields_get_name();
                
                for($i = 0; $i < count($products); $i++)
                {
                        $actions = xs_framework::create_button(array( 'name' => 'product_value[delete]', 'class' => 'button-primary', 'value' => $products[$i]['id'], 'text' => 'Remove', 'return' => true));
                        array_unshift($products[$i], $actions);
                        foreach($fields as $current_field) {
                                if($current_field == 'id')
                                        $products[$i]['id'] = xs_framework::create_input(array('value' => $products[$i]['id'], 'name' => 'product_value['.$i.'][id]', 'readonly' => true, 'type' => 'text', 'return' => true));
                                else if($current_field == 'lang')
                                        $products[$i]['lang'] = xs_framework::create_select(array( 'name' => 'product_value['.$i.'][lang]', 'data' => xs_language::$language_codes, 'selected' => $products[$i]['lang'], 'reverse' => true, 'return' => true));
                                else
                                        $products[$i][$current_field] = xs_framework::create_textarea(array('text' => $products[$i][$current_field], 'name' => 'product_value['.$i.']['.$current_field.']', 'return' => true));
                        }
                        
                }
                
                array_unshift($fields, "Actions");
                
                xs_framework::create_table(array('headers' => $fields, 'data' => $products ));
        
        }
        
        public function show_product_edit_add()
        {
                $fields = $this->db->fields_get_name_skip(array('id'));
                $size_fields = count($fields);
                
                $headers = array('Field', 'Value');
                $data = array();
                
                for($i = 0; $i < $size_fields; $i++ )
                {
                        $data[$i][0] = $current_field = $fields[$i];
                        
                        if($current_field == 'lang')
                                $data[$i][1] = xs_framework::create_select(array('class' => 'xs_full_width', 'name' => 'product_value[new][lang]', 'data' => xs_language::$language_codes, 'reverse' => true, 'return' => true));
                        else
                                $data[$i][1] = xs_framework::create_textarea(array('class' => 'xs_full_width', 'name' => 'product_value[new]['.$current_field.']', 'return' => true));
                }
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data ));
        }

        public function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }
                
                $this->fields = $this->db->fields_get();
                $this->products = $this->db->products_get();
                
                xs_framework::init_admin_style();
                
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
                
                // <PRODUCTS>
                settings_fields('setting_product');
                do_settings_sections('products');
                // </PRODUCTS>
                
                echo '</div>';
        }

        function section_menu()
        {
                register_setting( 'setting_globals', 'product_global', array($this, 'input_global') );
                add_settings_section( 'section_globals', 'Global settings', array($this, 'show_globals'), 'globals' );
                
                register_setting( 'setting_field', 'product_field', array($this, 'input_field') );
                add_settings_section( 'section_field', 'List of fields', array($this, 'show_fields'), 'fields' );

                add_settings_section( 'section_products', 'List of products', array($this, 'show_products'), 'products' );
                
                register_setting( 'setting_products_edit', 'product_value', array($this, 'input_products_edit') );
                add_settings_section( 'section_products_edit', 'List of products', array($this, 'show_products_edit'), 'products_edit' );
        }


        function input_global($input)
        {
                $input['template_file'] = sanitize_text_field( $input['template_file'] );
                return $input;
        }
        
        function input_field($input)
        {
                if(!empty($input['new']['Field']) && !empty($input['new']['Type'])) {
                        $this->db->field_add(sanitize_text_field($input['new']['Field']), sanitize_text_field($input['new']['Type']));
                }
                if(!empty($input['delete'])) {
                        $this->db->field_remove(sanitize_text_field($input['delete']));
                }
                
                unset($input);
        }
        
        function show_globals()
        {
                echo "Template file path: <input type='text' name='product_global[template_file]' value='".$this->globals["template_file"]."'>";
        }
        
        function show_fields()
        {
                $fields = $this->db->fields_get_skip(array('id', 'name', 'lang'));
                
                $headers = array('Actions', 'Name', 'Type');
                $data = array();
                
                foreach($fields as $current_field) {
                        $row[0] = xs_framework::create_button(array( 'name' => 'product_field[delete]', 'class' => 'button-primary', 'value' => $current_field['Field'], 'text' => 'Remove', 'return' => true));
                        $row[1] = $current_field['Field'];
                        $row[2] = $current_field['Type'];
                        $data[] = $row;
                }
                
                $new[0] = '';
                $new[1] = xs_framework::create_input(array('name' => 'product_field[new][Field]', 'return' => true));
                $new[2] = xs_framework::create_input(array('name' => 'product_field[new][Type]', 'return' => true));
                
                $data[] = $new;
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'headers' => $headers, 'data' => $data));

        }
        
        function show_products()
        {
                xs_framework::create_link(array('href' => 'admin.php?page=xsoftware_products_edit&id=new', 'class' => 'button-primary', 'text' => 'Add a product'));

                $fields = $this->db->fields_get_name();
                $products = $this->db->products_get();
                
                $fields_name[] = "Actions";
                foreach($fields as $single)
                        $fields_name[] = $single;
                
                for($i = 0; $i < count($products); $i++) {
                        $actions = xs_framework::create_link(array('href' => 'admin.php?page=xsoftware_products_edit&id='.$products[$i]['id'], 'class' => 'button-primary', 'text' => 'Show', 'return' => true));
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
