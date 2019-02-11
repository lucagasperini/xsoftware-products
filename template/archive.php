<?php
        if(!defined("ABSPATH")) die;
        
        wp_enqueue_style('xsoftware_bugtracking_style', plugins_url('template.css', __FILE__));
        
        get_header();
        
        $option = get_option('xs_options_bugs');
        
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
                        $values['image'] = get_the_post_thumbnail_url( $id, 'medium' );
                        $values['permalink'] = get_permalink($id);
                        $values['title'] = get_the_title($id);
                        
                        echo '<div class="product_list_item"><a href="'.$values['permalink'].'">';
                        echo '<img src="'.$values['image'].'" /><span>'.$values['title'].'</span></a></div>';
                }

                echo '</div>';
        } else {
                get_template_part( 'content', 'none' );
        }

        echo '</main></div>';

        get_sidebar();

        
        get_footer();
?>
