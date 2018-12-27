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
                echo '<form action="options.php" method="post">';

                settings_fields('setting_product');
                do_settings_sections('products');

                submit_button( 'Update products', 'primary', 'product_update', true, NULL );
                echo '</form>';
                // </PRODUCTS>
                
                echo '</div>';
        }

        function section_menu()
        {
                $this->fields = $this->db->fields_get();
                $this->products = $this->db->products_get();
                
                register_setting( 'setting_globals', 'product_global', array($this, 'input_global') );
                add_settings_section( 'section_globals', 'Global settings', array($this, 'show_globals'), 'globals' );
                
                register_setting( 'setting_field', 'product_field', array($this, 'input_field') );
                add_settings_section( 'section_field', 'List of fields', array($this, 'show_fields'), 'fields' );

                register_setting( 'setting_product', 'product_value', array($this, 'input_products') );
                add_settings_section( 'section_products', 'List of products', array($this, 'show_products'), 'products' );
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

        function input_products($input)
        {
                if(!empty($input['new']['name']))
                        $this->db->products_add($input['new']);
                
                unset($input['new']);
                if(!empty($input['delete'])) 
                        $this->db->products_remove($input['delete']);
                        
                unset($input['delete']);
                
                $this->db->products_update($input);
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
                include 'languages.php';
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
                        echo '<td><button name=product_value[delete] value="'.$products[$i]['id'].'">Remove</button></td>';
                        for($k = 0; $k < $size_fields; $k++) {
                                $current_field = $fields[$k]['Field'];
                                if ($current_field == "lang") {
                                        echo '<td><select name="product_value['.$i.'][lang]">';
                                        xs_language::languages_options($products[$i]['lang']);
                                        echo "</select></td>";
                                }
                                else if($current_field == "id") {
                                        echo "<td><input type='text' name='product_value[".$i."][".$current_field."]' value='".$products[$i][$current_field]."' readonly></td>";
                                }
                                else
                                        echo "<td><textarea name='product_value[".$i."][".$current_field."]'>".$products[$i][$current_field]."</textarea></td>";
                        }
                        echo "</tr>";
                }


                echo '<tr>';
                echo '<td></td>';
                for($i = 0; $i < $size_fields; $i++) {
                        $current_field = $fields[$i]['Field'];
                        if($current_field == "lang") {
                                echo '<td><select name="product_value[new][lang]">';
                                xs_language::__languages_options();
                                echo "</select></td>";
                        }
                        else if($current_field == "id") {
                                echo "<td></td>"; //Skip ID
                        } else {
                                echo "<td><textarea name='product_value[new][".$current_field."]' placeholder='Add ".$current_field."..'></textarea></td>";
                        }
                }

                echo "</tr></table>";
        }

        /* Dynamic Page Content */
        function dpc ($attr)
        {
                include $this->globals["template_file"];
                $attr = shortcode_atts( array( 'lang' => ''), $attr );
                $products = array();
                
                if(!empty($attr['lang']))
                        $products = $this->db->products_get_lang($attr['lang']);
                else
                        $products = $this->db->products_get();

                if(!isset($_GET['product'])) {
                        products_main($products);
                        return;
                }
                for($i = 0; $i < count($products); $i++)
                        if($products[$i]['name'] == $_GET['product'])
                                $single = $products[$i];

                if(!isset($single))
                        products_main($products);
                else
                        products_single($single);
        }
        /* Shortcode Page Content */
        function spc( $attr )
        {
                $products = $this->db->products_get();
                
                $attr = shortcode_atts( array( 'product' => '' , 'field' => ''), $attr );
                
                for($i = 0; $i < count($products); $i++)
                        if($products[$i]['id'] == $attr['product'])
                                $product = $products[$i];
                echo $product[$attr['field']];
        }

}

$plugin_product = new xs_products_plugin();

?>
