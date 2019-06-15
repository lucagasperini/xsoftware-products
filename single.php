<?php
        if(!defined("ABSPATH")) die;

        get_header();

        $options = get_option('xs_options_products');

        $user_lang = xs_framework::get_user_language();

        $single = array();

        $layout = get_theme_mod('page_layout');

        echo '<div id="primary" class="content-area '.$layout.'">';

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

                apply_filters('xs_product_single_html', $id, $single);
                // If comments are open or we have at least one comment, load up the comment template
                if ( comments_open() || get_comments_number() )
                        comments_template();
        }

        echo '</main></div>';

        if ( $layout !== 'fullscreen' )
                get_sidebar();

        get_footer();
?>

