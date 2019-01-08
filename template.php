<?php

wp_enqueue_style('product_template_style', plugins_url('style/template.css', __FILE__));

function products_main($products)
{
        echo '<div class="product_list">';
        for($i = 0; $i < count($products); $i++)
                echo '<div class="product_list_item"><a href="?product='.$products[$i]['name'].'"><img src="'.$products[$i]['img'].'" /><span>'.$products[$i]['title'].'</span></a></div>';
        echo '</div>';
}

function products_single($product)
{
        echo '<header class="entry-header"><h1 class="product_title">'.$product['title'].'</h1></header>';
        echo '<div class="product_content">';
        echo '<img class="product_img" src="'.$product['img'].'"/>';
        echo '<p class="product_descr">'.$product['descr'].'</p>';
        echo '</div>';
}
?>
