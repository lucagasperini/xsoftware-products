<?php
        if(!defined("ABSPATH")) die;
        
        get_header(); 
        if (get_theme_mod('fullwidth_single')) { //Check if the post needs to be full width
                $fullwidth = 'fullwidth';
        } else {
                $fullwidth = '';
        }
        
        $options = get_option('xs_options_products');
        
        $user_lang = xs_framework::get_user_language();
        
        $single = array();
       
        echo '<div id="primary" class="content-area col-md-9 '.$fullwidth.'">';

        echo '<main id="main" class="post-wrap" role="main">';
        while ( have_posts() ) {
                the_post();
                
                $id = get_the_ID();
                $category = get_post_meta( $id, 'xs_products_category', true );
                if(empty($category))
                        $category = 'default';
                        
                foreach($options['category'][$category]['field'] as $key => $values) {
                        $single[$key] = get_post_meta( $id, 'xs_products_'.$key.'_'.$user_lang, true );
                }
                
                $template = $options['category'][$category]['template']['active'];
                
                $template_file  = XS_CONTENT_DIR.'products/template/'.$template.'/main.php';

                if(file_exists($template_file)) {
                        include $template_file;
                        xs_products_template_single($id, $single);
                }
                
                // If comments are open or we have at least one comment, load up the comment template
                if ( comments_open() || get_comments_number() )
                        comments_template();
        }

        echo '</main></div>';

        if ( get_theme_mod('fullwidth_single', 0) != 1 )
                get_sidebar();
                
        get_footer(); 
?>

