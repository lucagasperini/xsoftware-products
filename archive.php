<?php
        if(!defined("ABSPATH")) die;
       
        get_header();
        
        echo '<div id="primary" class="content-area">';

        echo '<main id="main" class="post-wrap" role="main">';
        
        $options = get_option('xs_options_products');
        
        $user_lang = xs_framework::get_user_language();
        
        if(isset($_GET['xs_cat'])) { //TODO: check if xs_cat exists!
                $category = $_GET['xs_cat'];
                $template = $options['category'][$category]['template']['active'];
        } else {
                $category = 'default';
                $template = $options['template_archive']['active'];
        }

        if ( have_posts() ) {
                
                echo '<div class="product_list">';
                
                $archive = NULL;
                
                while ( have_posts() ) { 
                        the_post();
                        $id = get_the_ID();
                        if($category === get_post_meta( $id, 'xs_products_category', true ))
                                $archive[] = $id;
                }
                        
                $template_file  = XS_CONTENT_DIR.'products/template/'.$template.'/main.php';
                        
                if(file_exists($template_file)) {
                        include $template_file;
                        xs_products_template_archive($archive, $user_lang);
                }

                echo '</div>';
        } else {
                get_template_part( 'content', 'none' );
        }

        echo '</main></div>';
        
        get_footer();
?>
