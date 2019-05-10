<?php
        if(!defined("ABSPATH")) die;
       
        get_header();
        
        echo '<div id="primary" class="content-area col-md-9">';

        echo '<main id="main" class="post-wrap" role="main">';
        
        $options = get_option('xs_options_products');

        if ( have_posts() ) {
                echo '<header class="page-header">';
                the_archive_title( '<h3 class="archive-title">', '</h3>' );
                the_archive_description( '<div class="taxonomy-description">', '</div>' );
                echo '</header>';
                
                echo '<div class="product_list">';
                
                while ( have_posts() ) { 
                        the_post();
                        $archive[] = get_the_ID();
                }
                
                $template = $options['template_archive']['active'];
                        
                $template_file  = XS_CONTENT_DIR.'products/template/'.$template.'/main.php';
                        
                if(file_exists($template_file)) {
                        include $template_file;
                        xs_products_template_archive($archive);
                }

                echo '</div>';
        } else {
                get_template_part( 'content', 'none' );
        }

        echo '</main></div>';

        get_sidebar();

        
        get_footer();
?>
