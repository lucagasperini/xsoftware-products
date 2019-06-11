<?php
if(!defined("ABSPATH")) die;

if (!class_exists("xs_products_options")) :
/**
 * This class control plugin settings.
 */
class xs_products_options
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

                $this->options = get_option('xs_options_products', $this->default);
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

                if(isset($input['add_cat']) && !empty($input['id_cat'])){
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

                        $current['category'][$input['id_cat']] = $new_category;
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
                        if($this->download_template($input['template']['activate']))
                                $current['category'][$current_cat]['template']['active'] = $input['template']['activate'];
                }

                if(isset($input['template_archive']['activate'])){
                        if($this->download_template($input['template_archive']['activate']))
                                $current['template_archive']['active'] = $input['template_archive']['activate'];
                }

                return $current;
        }

        function download_template($id)
        {
                $repo = file_get_contents("http://wprepo.xsoftware.it/products/repo.xml");
                $xml = xs_framework::read_xml($repo);
                foreach($xml->template as $single) {
                        if($single->id == $id){
                                $url = "http://wprepo.xsoftware.it/products/pkg/".$single->id.".tar.gz";

                                if(is_dir(XS_CONTENT_DIR) === FALSE)
                                        mkdir(XS_CONTENT_DIR, 0775);
                                $products_dir = XS_CONTENT_DIR . 'products/';
                                if(is_dir($products_dir) === FALSE)
                                        mkdir($products_dir, 0775);
                                $template_dir = $products_dir . 'template/';
                                if(is_dir($template_dir) === FALSE)
                                        mkdir($template_dir, 0775);



                                if(is_dir($template_dir.basename($url,'.tar.gz')) !== FALSE)
                                        return TRUE;

                                $savefile = $template_dir.basename($url);

                                file_put_contents($savefile,file_get_contents($url));
                                $p = new PharData($savefile);
                                $p->decompress();
                                unlink($savefile);
                                $savefile = $template_dir.basename($url,'.gz');
                                $phar = new PharData($savefile);
                                $phar->extractTo($template_dir);
                                unlink($savefile);
                                return TRUE;
                        }
                }
                return FALSE;
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
                xs_framework::create_input([
                                'name' => 'xs_options_products[id_cat]',
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
                        $id = xs_framework::create_input([
                                'name' => 'xs_options_products[cat]['.$key.'][info][id]',
                                'value' => $key,
                                'readonly' => TRUE
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
                                'obj' => [$id, $name, $descr],
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

                $repo = file_get_contents("http://wprepo.xsoftware.it/products/repo.xml");

                $xml = xs_framework::read_xml($repo);

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
                        $tmp['author'] = $single->author;
                        $tmp['version'] = $single->version;
                        $tmp['url'] = $single->url;
                        $data[] = $tmp;
                }

                $headers = ['Actions', 'ID', 'Name', 'Description', 'Author', 'Version', 'URL'];

                xs_framework::create_table(array(
                        'class' => 'xs_admin_table xs_full_width',
                        'headers' => $headers,
                        'data' => $data
                ));
        }

}

endif;

$xs_products_options = new xs_products_options();

?>
