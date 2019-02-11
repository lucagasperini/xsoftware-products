<?php
        if(!defined("ABSPATH")) die;
        
        wp_enqueue_style('xs_products_style', plugins_url('template.css', __FILE__));
        
        get_header(); 
        if (get_theme_mod('fullwidth_single')) { //Check if the post needs to be full width
                $fullwidth = 'fullwidth';
        } else {
                $fullwidth = '';
        }
        
        $user_lang = xs_framework::get_user_language();

        echo '<div id="primary" class="content-area col-md-9 '.$fullwidth.'">';

        echo '<main id="main" class="post-wrap" role="main">';
        while ( have_posts() ) {
                the_post();
                $id = get_the_ID();
                $post = get_post($id);
                $values['image'] = get_the_post_thumbnail_url( $id, 'medium' );
                $values['descr'] = get_post_meta( $id, 'xs_products_descr_'.$user_lang, true );
                $values['title'] = get_the_title($id);
                        
                echo '<header class="entry-header"><h1 class="xs_primary">'.$values['title'].'</h1></header>';
                echo '<div class="product_content">';
                echo '<img class="product_img" src="'.$values['image'].'"/>';
                echo '<p class="product_descr">'.$values['descr'].'</p>';
                echo '<p class="xs_text">'.$post->post_content.'</p>';
                echo '</div>';
                
                // If comments are open or we have at least one comment, load up the comment template
                if ( comments_open() || get_comments_number() )
                        comments_template();
        }

        echo '</main></div>';

        if ( get_theme_mod('fullwidth_single', 0) != 1 )
                get_sidebar();
                
        get_footer(); 
?>

