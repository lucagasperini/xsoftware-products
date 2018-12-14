<?php

        echo '<header class="entry-header"><h1 class="prodotti-titolo">'.$prodotto['titolo'].'</h1></header>';
        echo '<div class="prodotti-content">';
        echo '<div class="prodotti-nome">'.$prodotto['nome'].'</div>';
        echo '<img class="prodotti-img" src="'.$prodotto['img'].'"/>';
        echo '<p class="prodotti-desc">'.$prodotto['desc'].'</p>';
        echo '<p class="prodotti-testo">'.$prodotto['testo'].'</p></div>';
        if(!empty($prodotto['pdf']))
            echo '<a href="'.$prodotto['pdf'].'">Scarica il nostro PDF!</a>'
        
?>
