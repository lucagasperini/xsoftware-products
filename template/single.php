<?php
        if(!defined("ABSPATH")) die;
        
        wp_enqueue_style('xsoftware_products_style', plugins_url('template.css', __FILE__));
        xs_framework::enqueue_fontawesome();
        
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
                $post = get_post($id);
                $image = get_the_post_thumbnail_url( $id, 'medium' );
                $title = get_the_title($id);
                foreach($options['fields'] as $key => $values) {
                        $single[$key] = get_post_meta( $id, 'xs_products_'.$key.'_'.$user_lang, true );
                }
                
                
                echo '<h1 class="xs_primary">'.$title.'</h1>';
                echo '<div class="product_content">';
                echo '<img class="product_img" src="'.$image.'"/>';
                echo '<p class="product_descr">'.$single['descr'].'</p>';
                
                echo '<a href="'.$single['git']['url'].'">'.$single['git']['text'].' <i class="fab fa-gitlab"></i></a>';
                
                echo '<table class="xs_product_table" >';
                
                
                echo '<tr>';
                echo '<td>'.$single['type']['a'].'</td>';
                echo '<td>'.$single['type']['b'].'</td>';
                echo '</tr>';
                
                
                echo '<tr>';
                echo '<td>'.$single['os']['a'].'</td>';
                echo '<td>'.$single['os']['b'].'</td>';
                echo '</tr>';
                
                
                echo '<tr>';
                echo '<td>'.$single['written']['a'].'</td>';
                echo '<td>'.$single['written']['b'].'</td>';
                echo '</tr>';
                                
                                
                echo '<tr>';
                echo '<td>'.$single['languague']['a'].'</td>';
                echo '<td>'.$single['languague']['b'].'</td>';
                echo '</tr>';
                                
                                
                echo '<tr>';
                echo '<td>'.$single['licence']['a'].'</td>';
                echo '<td>'.$single['licence']['b'].'</td>';
                echo '</tr>';
                
                echo "</table>";
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

