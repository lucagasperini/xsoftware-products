<?php
        if(!defined("ABSPATH")) die;
        
        wp_enqueue_style('xsoftware_products_style', plugins_url('template.css', __FILE__));
        
        get_header();
        
        echo '<div id="primary" class="content-area col-md-9">';

        echo '<main id="main" class="post-wrap" role="main">';

        if ( have_posts() ) {
                echo '<header class="page-header">';
                the_archive_title( '<h3 class="archive-title">', '</h3>' );
                the_archive_description( '<div class="taxonomy-description">', '</div>' );
                echo '</header>';
                
                echo '<div class="product_list">';
                
                while ( have_posts() ) { 
                        the_post();
                        $id = get_the_ID();
                        $image = get_the_post_thumbnail_url( $id, 'medium' );
                        $link = get_permalink($id);
                        $title = get_the_title($id);
                        
                        echo '<div class="product_list_item"><a href="'.$link.'">';
                        echo '<img src="'.$image.'" /><span>'.$title.'</span></a></div>';
                }

                echo '</div>';
        } else {
                get_template_part( 'content', 'none' );
        }

        echo '</main></div>';

        get_sidebar();

        
        get_footer();
?>
