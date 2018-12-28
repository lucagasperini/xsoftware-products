<?php
/*
Plugin Name: XSoftware Products
Description: Products management on wordpress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.eu/
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
                add_shortcode( 'xsoftware_spc_products', array($this, 'spc') );
                
        }
        

        function admin_menu()
        {
                add_menu_page( 'XSoftware Products', 'XSoftware Products', 'manage_options', 'xsoftware_products', array($this, 'menu_page') );
                add_submenu_page( 'xsoftware_products', 'XSoftware Products', 'Edit Products', 'manage_options', 'xsoftware_products_edit', array($this, 'menu_page_edit'));
        }
        
        public function menu_page_edit()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }
                
                wp_enqueue_style('products_style', plugins_url('style/admin.css', __FILE__));
                
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
                if(!empty($input['new']['name']))
                        $this->db->products_add($input['new']);
                
                unset($input['new']);
                
                if(!empty($input['delete'])) 
                        $this->db->products_remove($input['delete']);
                
                unset($input['delete']);
                $this->db->products_update($input);
        }
        
        public function show_products_edit()
        {
                if(isset($_GET["id"]))
                        $products = $this->db->products_get(NULL, $_GET["id"]);
                else
                        $products = $this->db->products_get();
                
                $fields = $this->db->fields_get();
                
                
                        
                echo '<table class="product_admin_tbl">';
                
                if(isset($_GET["id"])){
                        $this->show_product_edit_single($products[0], $fields);
                } else {
                        $this->show_product_edit_all($products, $fields);
                }

                echo "</table>";
                        
                
        }
        
        public function show_product_edit_single($single, $fields)
        {
                $size_fields = count($fields);
                $id_product = $single['id'];
                
                echo '<tr><th>Field</th>';
                echo '<th>Value</th></tr>';
                
                
                for($i = 0; $i < $size_fields; $i++) {
                        echo '<tr>';
                        $current_field = $fields[$i]['Field'];
                        echo "<td>".$current_field."</td>";
                        if ($current_field == "lang") {
                                echo "<td style='width: 100%;'><select style='width: 100%;' name='product_value[0][lang]'>";
                                xs_language::languages_options($single['lang']);
                                echo "</select></td>";
                        }
                        else if($current_field == "id") {
                                echo "<td style='width: 100%;'><input style='width: 100%;' type='text' name='product_value[0][".$current_field."]' value='".$single[$current_field]."' readonly></td>";
                        }
                        else {
                                echo "<td style='width: 100%;'><textarea style='width: 100%;' name='product_value[0][".$current_field."]'>".$single[$current_field]."</textarea></td>";
                        }
                echo "</tr>";
                }
        }
        
        public function show_product_edit_all($array, $fields)
        {
                $size_fields = count($fields);
                $size_products = count($array);
        
                echo '<tr><th>Actions</th>';
                for($i = 0; $i < $size_fields; $i++)
                        echo '<th>'.$fields[$i]['Field'].'</th>';
                echo '</tr>';
        
                echo '<tr>';
                echo '<td></td>';
                for($i = 0; $i < $size_fields; $i++) {
                        $current_field = $fields[$i]['Field'];
                        if($current_field == "lang") {
                                echo '<td><select name="product_value[new][lang]">';
                                xs_language::languages_options();
                                echo "</select></td>";
                        }
                        else if($current_field == "id") {
                                echo "<td></td>"; //Skip ID
                        } else {
                                echo "<td><textarea name='product_value[new][".$current_field."]' placeholder='Add ".$current_field."..'></textarea></td>";
                        }
                }

                echo "</tr>";
                for($i = 0; $i < $size_products; $i++) {
                        $id_product = $array[$i]['id'];
                        echo '<tr>';
                        echo '<td><button name=product_value[delete] value="'.$id_product.'">Remove</button></td>';
                        for($k = 0; $k < $size_fields; $k++) {
                                $current_field = $fields[$k]['Field'];
                                if ($current_field == "lang") {
                                        echo '<td><select name="product_value['.$i.'][lang]">';
                                        xs_language::languages_options($array[$i]['lang']);
                                        echo "</select></td>";
                                }
                                else if($current_field == "id") {
                                        echo "<td><input type='text' name='product_value[".$i."][".$current_field."]' value='".$array[$i][$current_field]."' readonly></td>";
                                }
                                else {
                                        echo "<td><textarea name='product_value[".$i."][".$current_field."]'>".$array[$i][$current_field]."</textarea></td>";
                                }
                        }
                        echo "</tr>";
                }
        
        }

        public function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }
                
                $this->fields = $this->db->fields_get();
                $this->products = $this->db->products_get();
                
                wp_enqueue_style('products_style', plugins_url('style/admin.css', __FILE__));
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
                $size_fields = count($fields);
        ?>
                <table id='product_admin_tbl'>
                <tr>
                <th>Actions</th>
                <th>Name</th>
                <th>Type</th>
                </tr>
        <?php
                for($i = 0; $i < $size_fields; $i++) {
                echo '<tr>
                <td><button name="product_field[delete]" value="'.$fields[$i]['Field'].'">Remove</button></td>
                <td>'.$fields[$i]['Field'].'</td>
                <td>'.$fields[$i]['Type'].'</td>
                </tr>';
                }


                echo "<tr>
                <td></td>
                <td><input type='text' name='product_field[new][Field]' placeholder='Add Name..'></td>
                <td><input type='text' name='product_field[new][Type]' placeholder='Add Type..'></td>
                </tr>
                </table>";

        }
        
        function show_products()
        {
                echo '<table class="product_admin_tbl"><tr>';

                $fields = $this->db->fields_get();
                $size_fields = count($fields);
                $products = $this->db->products_get();
                $size_products = count($products);
                
                echo '<th>Actions</th>';
                for($i = 0; $i < $size_fields; $i++)
                        echo '<th>'.$fields[$i]['Field'].'</th>';
                echo '</tr>';

                for($i = 0; $i < $size_products; $i++) {
                        echo '<tr>';
                        echo '<td><a class="show_button" href="admin.php?page=xsoftware_products_edit&id='.$products[$i]['id'].'">Show</a></td>';
                        for($k = 0; $k < $size_fields; $k++) {
                                $current_field = $fields[$k]['Field'];
                                echo "<td>".$products[$i][$current_field]."</td>";
                        }
                        echo "</tr>";
                }

                echo "</table>";
        }

        /* Dynamic Page Content */
        function dpc ($attr)
        {
                include $this->globals["template_file"];
                $attr = shortcode_atts( array( 'lang' => '', 'product' => '' , 'field' => ''), $attr );
                $products = array();
                $lang = NULL;
                
                if(!empty($attr['lang']))
                        $lang =  $attr['lang'];
                        
                if(isset($_GET['product']))
                        $name = $_GET['product'];
                if(!empty($attr['product']))
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
