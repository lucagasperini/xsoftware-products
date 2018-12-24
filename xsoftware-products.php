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

class xs_products_plugin
{
        private $def_field = array (
                                     array (
                                             'Field' => 'ID',
                                             'Type' => 'ID'
                                     ),
                                     array (
                                             'Field' => 'name',
                                             'type' => 'Name'
                                     ),
                                     array (
                                             'Field' => 'img',
                                             'type' => 'Image'
                                     ),
                                     array (
                                             'Field' => 'descr',
                                             'type' => 'Description'
                                     )
                             );

        private $def_global = array ( 
                                        'template_file' => 'template.php'
        );
        
        private $globals = array( );
        
        private $fields = array( array ( ) );

        private $options = array( array ( ) );
        
        private $conn = NULL;

        public function __construct()
        {
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                $this->globals = get_option('product_global', $this->def_global);
                $this->init_db();
                $this->fields = $this->get_fields();
                $this->options = $this->get_products();
                add_shortcode( 'xsoftware_dpc_products', array($this, 'dpc') );
                add_shortcode( 'xsoftware_spc_products', array($this, 'spc') );
                
        }
        
        function init_db()
        {
                if(isset($this->conn))
                        return;
                
                $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

                if (mysqli_connect_error()) {
                        die("Connection to database failed: " . mysqli_connect_error());
                }
                if(is_resource($this->conn)) 
                { 
                        $this->conn->query($this->conn, "SET NAMES 'utf8'"); 
                        $this->conn->query($this->conn, "SET CHARACTER SET 'utf8'"); 
                } 
                $result = $this->conn->query("SELECT 1 FROM `xs_products` LIMIT 1");
                if($result === FALSE)
                        $this->conn->query("CREATE TABLE xs_products ( `id` INT(11) NOT NULL PRIMARY KEY, `name` VARCHAR(64) NOT NULL, `lang` VARCHAR(16) NOT NULL, title VARCHAR(64) NOT NULL, `img` VARCHAR(256), `descr` VARCHAR(1024));");
        }
        
        function execute_query($sql_query)
        {
                $offset = $this->conn->query($sql_query);
                if (!$offset) {
                        echo "Could not run query: SQL_ERROR -> " . $this->conn->error . " SQL_QUERY -> " . $sql_query;
                        exit;
                }
                return $offset;
        }
        function get_fields()
        {
                $offset = array();
                $result = $this->execute_query("SHOW COLUMNS FROM xs_products");
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[] = $row;
                        }
                }
                return $offset;
        }
        
        function get_products()
        {
                $offset = array();
                $result = $this->execute_query("SELECT * FROM xs_products");
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[] = $row;
                        }
                }
                return $offset;
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

                wp_enqueue_style('products_style', plugins_url('style/admin.css', __FILE__));
                echo '<div class="wrap">';

                if(WP_DEBUG == true) {
                        var_dump($this->options);
                        var_dump($this->fields);
                        var_dump($this->globals);
                }

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
                register_setting( 'setting_globals', 'product_global', array($this, 'input_global') );
                add_settings_section( 'section_globals', 'Global settings', array($this, 'show_globals'), 'globals' );
                
                register_setting( 'setting_field', 'product_field', array($this, 'input_field') );
                add_settings_section( 'section_field', 'List of fields', array($this, 'show_fields'), 'fields' );

                register_setting( 'setting_product', 'product_value', array($this, 'input_products') );
                add_settings_section( 'section_products', 'List of products', array($this, 'show_products'), 'products' );
        }

        function check_duplicate($key, $array, $field)
        {
                $size = count($array);
                for($i = 0; $i < $size; $i++)
                        if($array[$i][$field] == $key)
                                return true;
                
                return false;
        }
        
        function input_global($input)
        {
                $input['template_file'] = sanitize_text_field( $input['template_file'] );
                return $input;
        }
        
        function input_field($input)
        {
                if(!empty($input['new']['Field']) && !empty($input['new']['Type']) && !$this->check_duplicate($input['new']['Field'], $this->fields, 'Field')) {
                        $sql_query = 'ALTER TABLE xs_products ADD `' . sanitize_text_field($input['new']['Field']) . '` '. $input['new']['Type'];
                        $this->execute_query($sql_query);
                }
                if(!empty($input['delete'])) {
                        $sql_query = 'ALTER TABLE xs_products DROP `' . sanitize_text_field($input['delete']) . '`';
                        $this->execute_query($sql_query);
                }
                
                unset($input);
        }

        function input_products($input)
        {
                if(!empty($input['new']['id']) && !$this->check_duplicate($input['new']['id'], $this->options, 'id'))
                        $this->insert_products($input['new']);
                
                unset($input['new']);
                if(!empty($input['delete'])) 
                        $this->remove_products($input['delete']);
                        
                unset($input['delete']);
                
                $this->update_products($input);
                unset($input);
        }
        
        function update_products($input)
        {
                $size_products = count($this->options);
                $size_fields = count($this->fields);
                
                $sql_update = 'UPDATE xs_products SET ';
                for($i = 0; $i < $size_products; $i++) {
                        for($k = 0; $k < $size_fields; $k++) {
                                $current_field = $this->fields[$k]['Field'];
                                $sql_update .= '`' . $current_field . '` = "'. sanitize_text_field($input[$i][$current_field]) . '"';
                                if($k < $size_fields - 1) {
                                        $sql_update .= ', ';
                                } else {
                                        $sql_update .= ' WHERE id = "' . $input[$i]['id'] . '";';
                                        $this->execute_query($sql_update);
                                        $sql_update = 'UPDATE xs_products SET '; 
                                }
                                
                        }
                        
                }
        
        }
        
        function insert_products($input)
        {
                $size_fields = count($this->fields);
                
                $sql_insert = 'INSERT INTO xs_products (';
                for($i = 0; $i < $size_fields; $i++) {
                        $current_field = $this->fields[$i]['Field'];
                        $sql_insert .= '`' . $current_field . '`';
                        if($i < $size_fields - 1)
                                $sql_insert .= ', ';
                        else
                                $sql_insert .= ' ) VALUES ( ';
                                
                }
                for($i = 0; $i < $size_fields; $i++) {
                        $current_field = $this->fields[$i]['Field'];
                        $sql_insert .= '"' . $input[$current_field] . '"';
                        if($i < $size_fields - 1)
                                $sql_insert .= ', ';
                        else
                                $sql_insert .= ' )';
                }
                $this->execute_query($sql_insert);
        }
        
        function remove_products($input)
        {
                $this->execute_query('DELETE FROM xs_products WHERE `id`= "'. $input . '"');
        }
        
        function show_globals()
        {
                echo "Template file path: <input type='text' name='product_global[template_file]' value='".$this->globals["template_file"]."'>";
        }
        
        function show_fields()
        {
        ?>
                <table id='product_admin_tbl'>
                <tr>
                <th>Actions</th>
                <th>Name</th>
                <th>Type</th>
                </tr>
        <?php
                $size = count($this->fields);
                for($i = 0; $i < $size; $i++) {
                echo '<tr>
                <td><button name="product_field[delete]" value="'.$this->fields[$i]['Field'].'">Remove</button></td>
                <td>'.$this->fields[$i]['Field'].'</td>
                <td>'.$this->fields[$i]['Type'].'</td>
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

                $size_field = count($this->fields);
                echo '<th>Actions</th>';
                for($i = 0; $i < $size_field; $i++)
                        echo '<th>'.$this->fields[$i]['Field'].'</th>';
                echo '</tr>';

                $size_products = count($this->options);
                for($i = 0; $i < $size_products; $i++) {
                        echo '<tr>';
                        echo '<td><button name=product_value[delete] value="'.$this->options[$i]['id'].'">Remove</button></td>';
                        for($k = 0; $k < $size_field; $k++) {
                                $current_field = $this->fields[$k]['Field'];
                                if ($current_field == "lang") {
                                        echo '<td><select name="product_value['.$i.'][lang]">';
                                        xs_language::languages_options($this->options[$i]['lang']);
                                        echo "</select></td>";
                                }
                                else
                                        echo "<td><textarea name='product_value[".$i."][".$current_field."]'>".$this->options[$i][$current_field]."</textarea></td>";
                        }
                        echo "</tr>";
                }


                echo '<tr>';
                echo '<td></td>';
                for($i = 0; $i < $size_field; $i++) {
                        $current_field = $this->fields[$i]['Field'];
                        if($current_field == "lang") {
                                echo '<td><select name="product_value[new][lang]">';
                                xs_language::__languages_options();
                                echo "</select></td>";
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
                {
                        
                        $result = $this->execute_query('SELECT * FROM xs_products WHERE lang="' . $attr['lang'] . '"');
                        if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                        $products[] = $row;
                                }
                        }
                }
                else {
                        $products = $this->options;
                }
                ob_start();

                for($i = 0; $i < count($products); $i++)
                        if($products[$i]['name'] == $_GET['product'])
                                $single = $products[$i];

                if(!isset($single))
                        products_main($products);
                else
                        products_single($single);

                return ob_get_clean();
        }
        /* Shortcode Page Content */
        function spc( $attr )
        {
                $attr = shortcode_atts( array( 'product' => '' , 'field' => ''), $attr );
                
                for($i = 0; $i < count($this->options); $i++)
                        if($this->options[$i]['id'] == $attr['product'])
                                $product = $this->options[$i];
                echo $product[$attr['field']];
        }

}

$plugin_product = new xs_products_plugin();

?>
