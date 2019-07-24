<?php
        if(!defined('ABSPATH')) die;

        get_header();

        $options = get_option('xs_options_products');

        $user_lang = xs_framework::get_user_language();

        $single = array();

        $layout = get_theme_mod('page_layout');

        /* Print primary and main elements */
        echo '<div class="wrap">
        <div id="primary" class="content-area '.$layout.'">
        <main id="main" class="post-wrap" role="main">';

        while ( have_posts() ) {
                the_post();

                $id = get_the_ID();
                $category = get_post_meta( $id, 'xs_products_category', true );
                if(empty($category))
                        $category = 'default';

                foreach($options['category'][$category]['field'] as $key => $values) {
                        if(!isset($values['const']) || $values['const'] == FALSE) {
                                $single[$key] = get_post_meta(
                                        $id,
                                        'xs_products_'.$key.'_'.$user_lang,
                                        true
                                );
                        } else {
                                $single[$key] = get_post_meta(
                                        $id,
                                        'xs_products_const_'.$key,
                                        true
                                );
                        }
                }

                if($category === 'default')
                        echo apply_filters('xs_product_single_html', $id, $single);
                else
                        echo apply_filters('xs_product_single_html_'.$category, $id, $single);
        }

        echo '</main></div></div>';

        if ( $layout !== 'fullscreen' )
                get_sidebar();

        get_footer();
?>

