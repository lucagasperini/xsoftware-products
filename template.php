<?php

function products_main($products)
{
        echo '<div class="product_list">';
        for($i = 0; $i < count($products); $i++)
                echo '<div class="product_list_item"><a href="?product='.$products[$i]['ID'].'"><img src="'.$products[$i]['img'].'" /><span>'.$products[$i]['name'].'</span></a></div>';
        echo '</div>';
}

function products_single($product)
{
        echo '<header class="entry-header"><h1 class="product_title">'.$product['name'].'</h1></header>';
        echo '<div class="product_content">';
        echo '<img class="product_img" src="'.$product['img'].'"/>';
        echo '<p class="product_desc">'.$product['desc'].'</p>';
}
?>
