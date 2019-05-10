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

        private $default = array (
                'category' => [
                        'default' => [
                                'info' => [
                                        'name' => 'Default',
                                        'descr' => 'This is the default category for products.',
                                        'img' => ''
                                ],
                                'template' => [
                                        'active' => 'default',
                                ],
                                'field' => [
                                        'descr' => [
                                                'name' => 'Description',
                                                'type' => 'text',
                                        ],
                                        'text' => [
                                                'name' => 'Text',
                                                'type' => 'text',
                                        ]
                                ]
                        ]
                ],
                'template_archive' => [
                        'active' => 'default'
                ]
        );
        
        private $types = array(
                'text' => 'Text', 
                'ima' => 'Image', 
                'lang' => 'Language', 
                'field' => 'Field', 
                'link' => 'Link'
        );
        
        private $options = array( );
        

        public function __construct()
        {
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                add_action('init', array($this, 'create_post_type'));
                add_action('save_post', array($this,'save'), 10, 2 );
                add_filter('single_template', array($this,'single'));
                add_filter('archive_template', array($this,'archive'));
                add_action('add_meta_boxes', array($this, 'metaboxes'));
                add_filter( 'manage_xs_product_posts_columns', array($this,'add_columns') );
               
                $this->options = get_option('xs_options_products', $this->default);
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
                
                $category = isset($values['xs_products_category'][0]) ? intval($values['xs_products_category'][0]) : 'default';
                
                foreach($this->options['category'] as $key => $prop)
                        $cat_list[$key] = $prop['info']['name'];
                
                xs_framework::create_select( array(
                        'name' => 'xs_products_category', 
                        'selected' => $category, 
                        'data' => $cat_list,
                        'default' => 'Select a Category',
                        'echo' => TRUE
                ));
        }
        
        function metaboxes_lang_print($post, $lang_code)
        {
                xs_framework::init_admin_style();
                $lang_code = $lang_code['args'];
                $values = get_post_meta( $post->ID );
                
                $category = isset($values['xs_products_category'][0]) ? $values['xs_products_category'][0] : 'default';
                
                foreach($this->options['category'][$category]['field'] as $key => $single) {
                        $selected[$key] = $single;
                        $selected[$key]['value'] = isset( $values['xs_products_'.$key.'_'.$lang_code][0] ) ?
                                $values['xs_products_'.$key.'_'.$lang_code][0] : array();
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
                $post_type = get_post_type($post_id);
                if ( $post_type != 'xs_product' ) return;
                
                if(isset($_POST['xs_products_category'])) {
                        $current_category = $_POST['xs_products_category'];
                        update_post_meta( $post_id, 'xs_products_category', $current_category ); 
                } else {
                        $values = get_post_meta( $post->ID );
                        $current_category = isset($values['xs_products_category'][0]) ? $values['xs_products_category'][0] : 'default';
                }
                
                $languages = xs_framework::get_available_language();
                
                foreach($this->options['category'][$current_category]['field'] as $key => $single) {
                        foreach($languages as $code => $name) {
                                $tmp = $_POST['xs_products_'.$key.'_'.$code];
                                if(isset($tmp)) {
                                        update_post_meta( $post_id, 'xs_products_'.$key.'_'.$code, $tmp );   
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
                        if ( file_exists(  dirname( __FILE__ ) . '/archive.php' ) ) {
                                return  dirname( __FILE__ ) . '/archive.php';
                        }
                }

                return $single;
        }
        
        
        
        function install_style_pack()
        {
                $style['.product_list_item>a>span'] = [
                        0 => array( 'color' => 'text' , 'background-color' => 'primary', 'border-color' => ''), 
                        'hover' => array( 'color' => '' , 'background-color' => '', 'border-color' => ''), 
                        'focus' => array( 'color' => '' , 'background-color' => '', 'border-color' => ''),
                ];
               
                xs_framework::install_style_pack($style);
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
                                'category' => 'Category',
                                'field' => 'Fields',
                                'template' => 'Templates'
                        ),
                        'home' => 'category',
                        'name' => 'main_tab'
                ));
                
                switch($tab) {
                        case 'category':
                                $this->show_category();
                                return;
                        case 'field':
                                $this->show_fields();
                                return;
                        case 'template':
                                $this->show_template();
                                return;
                }
        }

        function input($input)
        {
                $current = $this->options;
                if(isset($input['cat']) && !empty($input['cat'])){
                        foreach($input['cat'] as $key => $prop)
                                $current['category'][$key]['info'] = $input['cat'][$key]['info'];
                }
                        
                if(isset($input['add_cat'])){
                        $new_category['info'] = [
                                'name' => 'New Category', 
                                'descr' => 'This is a description.', 
                                'img' => ''
                        ];
                        
                        $new_category['field'] = [
                                'descr' => [
                                        'name' => 'Description',
                                        'type' => 'text',
                                ],
                                'text' => [
                                        'name' => 'Text',
                                        'type' => 'text',
                                ]
                        ];
                        
                        $new_category['template'] = [
                                'active' => 'default'
                        ];
                        
                        $current['category'][] = $new_category;
                }
                
                if(isset($input['remove_cat']) && !empty($input['remove_cat']))
                        unset($current['category'][$input['remove_cat']]);

                
                if(isset($input['field'])) {
                        $current_cat = empty($input['field']['cat']) ? 0 : $input['field']['cat'];
                        $f = $input['field'];
                        if(isset($f['new']) && !empty($f['new']['code']) && !empty($f['new']['name']) && !empty($f['new']['type'])) {
                                $code = $f['new']['code'];
                                unset($f['new']['code']);
                                $current['category'][$current_cat]['field'][$code] = $f['new'];
                        }
                        if(!empty($f['delete'])) {
                                unset($current['category'][$current_cat]['field'][$f['delete']]);
                        }
                }
                
                if(isset($input['template']['activate']) && isset($input['template']['cat'])){
                        $current_cat = empty($input['template']['cat']) ? 0 : $input['template']['cat'];
                        $current['category'][$current_cat]['template']['active'] = $input['template']['activate'];
                }
                
                if(isset($input['template_archive']['activate'])){
                        $current['template_archive']['active'] = $input['template_archive']['activate'];
                }

                return $current;
        }
        
        function show_category()
        {
                $cats = $this->options['category'];
                
                xs_framework::create_button([
                                'class' => 'button-primary xs_margin',
                                'text' => 'Add new category', 
                                'name' => 'xs_options_products[add_cat]',
                                'echo' => TRUE
                        ]);
                
                foreach($cats as $key => $prop) {
                        $prop = $prop['info'];
                        $img_input = xs_framework::create_input([
                                'id' => 'cat['.$key.'][input]',
                                'style' => 'display:none;',
                                'name' => 'xs_options_products[cat]['.$key.'][info][img]',
                                'onclick' => 'wp_media_gallery_url(\'' . 'cat['.$key.'][input]' . '\',\'' . 'cat['.$key.'][image]' . '\')',
                                'value' => $prop['img']
                        ]);
                        if(empty($prop['img']))
                                $url_img = xs_framework::url_image('select.png');
                        else
                                $url_img = $prop['img'];
                                
                        $img = xs_framework::create_image([
                                'src' => $url_img,
                                'alt' => $prop['name'],
                                'id' => 'cat['.$key.'][image]',
                                'width' => 150,
                                'height' => 150,
                        ]);
                        
                        $name = xs_framework::create_input([
                                'name' => 'xs_options_products[cat]['.$key.'][info][name]',
                                'value' => $prop['name']
                        ]);
                        $descr = xs_framework::create_textarea([
                                'name' => 'xs_options_products[cat]['.$key.'][info][descr]',
                                'text' => $prop['descr']
                        ]);
                        
                        $data[$key]['img'] = xs_framework::create_label([
                                'for' => 'cat['.$key.'][input]',
                                'obj' => [$img_input, $img]
                        ]);
                        
                        $data[$key]['text'] = xs_framework::create_container([
                                'class' => 'xs_docs_container',
                                'obj' => [$name, $descr],
                        ]);
                        if($key !== 'default') //SKIP DELETE BUTTON IF IS DEFAULT CATEGORY!
                                $data[$key]['delete'] = xs_framework::create_button([
                                        'class' => 'button-primary',
                                        'text' => 'Remove',
                                        'onclick' => 'return confirm_box()',
                                        'value' => $key, 
                                        'name' => 'xs_options_products[remove_cat]',
                                        'return' => TRUE
                                ]);
                }
                
                xs_framework::create_table([
                        'class' => 'xs_docs_table',
                        'data' => $data
                ]);
        }

        function show_fields()
        {
                $cats = $this->options['category'];
                
                $tabs = array();
                
                foreach($cats as $key => $prop){
                        $tabs[$key] = $prop['info']['name'];
                }
                
                $tab = xs_framework::create_tabs( array(
                        'href' => '?page=xsoftware_products&main_tab=field',
                        'tabs' => $tabs,
                        'home' => 'default',
                        'name' => 'field_tab'
                ));
               
                
                xs_framework::create_input([
                        'name' => 'xs_options_products[field][cat]',
                        'style' => 'display:none;',
                        'value' => $tab,
                        'echo' => TRUE
                ]);
                
                $fields = $this->options['category'][$tab]['field'];

                $headers = array('Actions', 'Code', 'Name', 'Type');
                $data = array();
                
                foreach($fields as $key => $single) {
                        $data[$key][0] = xs_framework::create_button(array( 
                                'name' => 'xs_options_products[field][delete]', 
                                'class' => 'button-primary', 
                                'value' => $key, 
                                'text' => 'Remove'
                        ));
                        $data[$key][1] = $key;
                        $data[$key][2] = $single['name'];
                        $data[$key][3] = $this->types[$single['type']];
                }
                
                $new[0] = '';
                $new[1] = xs_framework::create_input(array('name' => 'xs_options_products[field][new][code]'));
                $new[2] = xs_framework::create_input(array('name' => 'xs_options_products[field][new][name]'));
                $new[3] = xs_framework::create_select(array(
                        'name' => 'xs_options_products[field][new][type]',
                        'data' => $this->types
                ));
                
                $data[] = $new;
                
                xs_framework::create_table(array(
                        'class' => 'xs_admin_table xs_full_width',
                        'headers' => $headers, 
                        'data' => $data
                ));
        }
        
        function show_template()
        {
                $cats = $this->options['category'];
                
                $tabs = ['archive' => 'Archive'];
                
                foreach($cats as $key => $prop){
                        $tabs[$key] = $prop['info']['name'];
                }
                
                $tab = xs_framework::create_tabs( array(
                        'href' => '?page=xsoftware_products&main_tab=template',
                        'tabs' => $tabs,
                        'home' => 'default',
                        'name' => 'field_tab'
                ));
                
                xs_framework::create_input([
                        'name' => 'xs_options_products[template][cat]',
                        'style' => 'display:none;',
                        'value' => $tab,
                        'echo' => TRUE
                ]);
                
                if($tab === 'archive') {
                        $template = $this->options['template_archive'];
                        $input_name = 'xs_options_products[template_archive][activate]';
                } else {
                        $template = $this->options['category'][$tab]['template'];
                        $input_name = 'xs_options_products[template][activate]';
                }
                $template_dir  = XS_CONTENT_DIR.'products/template/';
                
                $xml = xs_framework::read_xml($template_dir.'/info.xml');
                
                $data = array();
                
                foreach($xml->template as $single) {
                        if($template['active'] != $single->id) {
                                $tmp[0] = xs_framework::create_button(array( 
                                        'name' => $input_name, 
                                        'class' => 'button-primary', 
                                        'value' => $single->id, 
                                        'text' => 'Activate'
                                ));
                        } else {
                                $tmp[0] = 'Active';
                        }
                                
                        $tmp['id'] = $single->id;
                        $tmp['name'] = $single->name;
                        $tmp['descr'] = $single->descr;
                        $tmp['img'] = $single->img;
                        $tmp['author'] = $single->author;
                        $tmp['version'] = $single->version;
                        $tmp['url'] = $single->url;
                        $data[] = $tmp;
                }
                
                $headers = ['Actions', 'ID', 'Name', 'Description', 'Image', 'Author', 'Version', 'URL'];

                xs_framework::create_table(array(
                        'class' => 'xs_admin_table xs_full_width',
                        'headers' => $headers, 
                        'data' => $data
                ));
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

$plugin_product = new xs_products_plugin();

?>
