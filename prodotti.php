<?php
/*
Plugin Name: XSoftware Prodotti
Description: Gestione dei prodotti su wordpress
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.eu/
Text Domain: prodotti
*/
if(!defined('ABSPATH')) exit;

class prodotti
{
    private $def_product = array ( array (
        'ID'    =>   'awesome_product',
        'name'  =>   'An Awesome Product' ,
        'img'   =>   'https://i.kym-cdn.com/entries/icons/mobile/000/000/107/smily.jpg' ,
        'desc'  =>   'This is an  very awesome product!'
        )
    );
    private $def_field = array ( 
        array (
        'ID' => 'ID',
        'name' => 'ID'
        ),
        array (
        'ID' => 'name',
        'name' => 'Name'
        ),
        array (
        'ID' => 'img',
        'name' => 'Image'
        ),
        array (
        'ID' => 'desc',
        'name' => 'Description'
        )
    );
    
    private $fields = array( array ( ) );
    
    private $options = array( array ( ) );
    
    public function __construct()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'section_menu'));
        $this->fields = get_option('product_field', $this->def_field);
        $this->options = get_option('product_value', $this->def_product);
        add_shortcode( 'xsoftware_dpc_products', array($this, 'dpc') ); 
    }
    
    function admin_menu()
    {
    add_menu_page( 'XSoftware Products', 'XSoftware Products', 'manage_options', 'xsoftware_products', array($this, 'menu_page') );
    }
    
    public function menu_page() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'Exit!' ) );
    }
    
    wp_enqueue_style('products_style', plugins_url('style.css', __FILE__));
    echo '<div class="wrap">';
    
    if(WP_DEBUG == true) {
        var_dump($this->options);
        var_dump($this->fields);
    }
        
    echo '<h2>Products configuration</h2>';
    
    echo '<form action="options.php" method="post">';
    
    settings_fields('setting_field');
    do_settings_sections('fields');
    
    /* https://codex.wordpress.org/Function_Reference/submit_button */
    submit_button( 'Update fields', 'primary', 'field_update', true, NULL );
    echo '</form>';
    
    
    echo '<form action="options.php" method="post">';
    
    settings_fields('setting_product');
    do_settings_sections('products');
    
    /* https://codex.wordpress.org/Function_Reference/submit_button */
    submit_button( 'Update products', 'primary', 'product_update', true, NULL );
    echo '</form>';
    echo '</div>';
}

    function section_menu()
    {
            /* https://developer.wordpress.org/reference/functions/register_setting/ */
        register_setting( 'setting_field', 'product_field', array($this, 'input_field') );
        /* https://codex.wordpress.org/Function_Reference/add_settings_section */
        add_settings_section( 'section_field', 'List of fields', array($this, 'show_fields'), 'fields' );
        
        /* https://developer.wordpress.org/reference/functions/register_setting/ */
        register_setting( 'setting_product', 'product_value', array($this, 'input_products') );
        /* https://codex.wordpress.org/Function_Reference/add_settings_section */
        add_settings_section( 'section_products', 'List of products', array($this, 'show_products'), 'products' );
    }
    
    function input_field($input)
    {
     $size_fields = count($this->fields);
        for($i = 0; $i < $size_fields; $i++)
        {
            $input[$i]['ID'] = sanitize_text_field($input[$i]['ID']);
            $input[$i]['name'] = sanitize_text_field($input[$i]['name']);
        }
        if(!empty($input['new']['ID']) && !empty($input['new']['name']))
        {
            $input[$size_fields]['ID'] = sanitize_text_field($input['new']['ID']);
            $input[$size_fields]['name'] = sanitize_text_field($input['new']['name']);
        }
        unset($input['new']);
        return $input;
    }
    
    function input_products($input)
    {
     $size_products = count($this->options);
        for($i = 0; $i < $size_products; $i++)
        {
                for($k = 0; $k < count($this->fields); $k++)
                {
                $field = $this->fields[$k]['ID'];
                $input[$i][$field] = sanitize_text_field($input[$i][$field]);
                }
        }
        if(!empty($input['new']['ID']))
        {
                for($i = 0; $i < count($this->fields); $i++)
                {
                $field = $this->fields[$i]['ID'];
                $input[$size_products][$field] = sanitize_text_field($input['new'][$field]);
                }
        }
        unset($input['new']);
        return $input;
    }
    function show_fields()
    {
        ?>
        <table class='product_admin_tbl'>
        <tr>
            <th>ID Fields</th>
            <th>Name Fields</th>
        </tr>
        <?php
        $size = count($this->fields);
        for($i = 0; $i < $size; $i++)
            {
            echo "<tr>
            <td><input readonly type='text' name='product_field[".$i."][ID]' value='".$this->fields[$i]['ID']."'></td>
            <td><input type='text' name='product_field[".$i."][name]' value='".$this->fields[$i]['name']."'></td>
            </tr>";
            }
            
            
            echo "<tr>
            <td><input type='text' name='product_field[new][ID]' placeholder='Add ID..'></td>
            <td><input type='text' name='product_field[new][name]' placeholder='Add Name..'></td>
            </tr>
            </table>";
            
    }
    function show_products()
    {
        echo "<table class='product_admin_tbl'><tr>";
        
        $size_field = count($this->fields);
        for($i = 0; $i < $size_field; $i++)
            echo "<th>".$this->fields[$i]['name']."</th>";
        echo "</tr>";
        
        $size_products = count($this->options);
        for($i = 0; $i < $size_products; $i++)
        {
            echo "<tr>";
            for($k = 0; $k < $size_field; $k++)
                echo "<td><textarea name='product_value[".$i."][".$this->fields[$k]['ID']."]'>".$this->options[$i][$this->fields[$k]['ID']]."</textarea></td>";
            echo "</tr>";
        }
            
            
        echo "<tr>";
            
        for($i = 0; $i < $size_field; $i++)
            echo "<td><textarea name='product_value[new][".$this->fields[$i]['ID']."]' placeholder='Add ".$this->fields[$i]['name']."..'></textarea></td>";
            
        echo "</tr></table>";
            
    }
   
    /* Dynamic Page Content */
    function dpc ()
    {
        include 'template.php';
        wp_enqueue_style('product-style', plugins_url('style.css', __FILE__));
        ob_start();
        
        if(!isset( $_GET['product'] )) 
        {
                products_main($this->options);
                return;
        }
        
        for($i = 0; $i < count($this->options); $i++)
                if($this->options[$i]['ID'] == $_GET['product'])
                         $product = $this->options[$i];
                         
        if(!isset($product)) 
                products_main($this->options);
        else
                products_single($product);

        return ob_get_clean();
    }

}

$plugin_product = new prodotti();

?>
