<?php
        if(!defined("ABSPATH")) die;
        
        wp_enqueue_style('xs_documentation_style', plugins_url('template.css', __FILE__));
        
        $users = xs_framework::get_user_display_name();
        
        get_header(); 
        if (get_theme_mod('fullwidth_single')) { //Check if the post needs to be full width
                $fullwidth = 'fullwidth';
        } else {
                $fullwidth = '';
        }

        echo '<div id="primary" class="content-area col-md-9 '.$fullwidth.'">';

        echo '<main id="main" class="post-wrap" role="main">';
        while ( have_posts() ) {
                the_post();
                $id = get_the_ID();
                $post = get_post($id);
                $values['image'] = get_post_meta( $id, 'xs_products_image', true );
                $values['descr'] = get_post_meta( $id, 'xs_products_descr', true );
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

