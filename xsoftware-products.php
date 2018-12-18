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

class xsproducts
{
        private $def_product = array ( array (
                                               'id'    =>   'awesome_product',
                                               'name'  =>   'An Awesome Product',
                                               'img'   =>   'https://i.kym-cdn.com/entries/icons/mobile/000/000/107/smily.jpg',
                                               'descr'  =>   'This is an  very awesome product!'
                                       )
                                     );
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
                        $this->conn->query("CREATE TABLE xs_products ( `id` VARCHAR(64) NOT NULL PRIMARY KEY, `name` VARCHAR(256), `img` VARCHAR(256), `descr` VARCHAR(1024));");
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
                <table class='product_admin_tbl'>
                <tr>
                <th>Name</th>
                <th>Type</th>
                </tr>
        <?php
                $size = count($this->fields);
                for($i = 0; $i < $size; $i++) {
                echo "<tr>
                <td>".$this->fields[$i]['Field']."</td>
                <td>".$this->fields[$i]['Type']."</td>
                </tr>";
                }


                echo "<tr>
                <td><input type='text' name='product_field[new][Field]' placeholder='Add Name..'></td>
                <td><input type='text' name='product_field[new][Type]' placeholder='Add Type..'></td>
                </tr>
                </table>";
                
                echo 'Delete field: <select name="product_field[delete]">';
                echo '<option value=0 selected></option>'; //DEFAULT OPTION
                for($i = 0; $i < $size; $i++)
                echo '<option value="'. $this->fields[$i]['Field'] .'">'.$this->fields[$i]['Field'].'</option>';
                echo '</select>';

        }
        
        function show_products()
        {
                echo "<table class='product_admin_tbl'><tr>";

                $size_field = count($this->fields);
                for($i = 0; $i < $size_field; $i++)
                        echo "<th>".$this->fields[$i]['Field']."</th>";
                echo "</tr>";

                $size_products = count($this->options);
                for($i = 0; $i < $size_products; $i++) {
                        echo "<tr>";
                        for($k = 0; $k < $size_field; $k++) {
                                $current_field = $this->fields[$k]['Field'];
                                if($current_field == "Field")
                                echo "<td><textarea readonly name='product_value[".$i."][".$current_field."]'>".$this->options[$i][$current_field]."</textarea></td>";
                                else
                                echo "<td><textarea name='product_value[".$i."][".$current_field."]'>".$this->options[$i][$current_field]."</textarea></td>";
                        }
                        echo "</tr>";
                }


                echo "<tr>";

                for($i = 0; $i < $size_field; $i++)
                        echo "<td><textarea name='product_value[new][".$this->fields[$i]['Field']."]' placeholder='Add ".$this->fields[$i]['Field']."..'></textarea></td>";

                echo "</tr></table>";
                
                echo 'Delete record: <select name="product_value[delete]">';
                echo '<option value=0 selected></option>'; //DEFAULT OPTION
                for($i = 0; $i < $size_products; $i++)
                echo '<option value="'.$this->options[$i]['id'].'">'.$this->options[$i]['id'].'</option>';
                echo '</select>';
        }

        /* Dynamic Page Content */
        function dpc ()
        {
                include $this->globals["template_file"];
                ob_start();

                for($i = 0; $i < count($this->options); $i++)
                        if($this->options[$i]['id'] == $_GET['product'])
                                $product = $this->options[$i];

                if(!isset($product))
                        products_main($this->options);
                else
                        products_single($product);

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

$plugin_product = new xsproducts();

?>
