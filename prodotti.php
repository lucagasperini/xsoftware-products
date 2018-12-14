<?php
/*
Plugin Name: XSoftware Prodotti
Description: Gestione dei prodotti su wordpress
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.eu/
Text Domain: prodotti
*/
if(!defined('ABSPATH')) exit;

class prodotti
{
    private $defaults = array ( array (
        'ID'    =>   0,
        'nome'  =>   '' ,
        'img'   =>   '' ,
        'titolo'=>   '' ,
        'desc'  =>   '' ,
        'testo' =>  ''
        )
    );
    
    private $options = array( array ( ) );
    
    public function __construct()
    {
        add_action('admin_menu', array($this, 'prodotti_menu'));
        add_action('admin_init', array($this, 'sezione_valori_menu'));
        $this->options = get_option('prodotti_test', $this->defaults);
        add_shortcode( 'dpc_prodotti', array($this, 'dpc') ); 
        wp_enqueue_style('prodotti-style', plugins_url('style.css', __FILE__));
    }
    
    public function prodotti_menu()
    {
    // NOME DELLA PAGINA, NOME DEL MENU, PRIVILEGI NECESSARI, URL DEL MODULO PLUGIN, FUNZIONE DA CHIAMARE DENTRO LA CLASSE QUANDO VIENE ATTIVATO IL MENU
    add_menu_page( 'XSoftware Prodotti', 'XSoftware Prodotti', 'manage_options', 'prodotti', array($this, 'pro_main') );
    }
    
    public function pro_main() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'Non hai i permessi per entrare qui!' ) );
    }
    echo '<div class="wrap">';
    
    if(WP_DEBUG == true)
        var_dump($this->options);
        
    echo '<h2>Configurazione dei prodotti</h2>';
    echo '<form action="options.php" method="post">';
    
    settings_fields('campo-prodotti');
    do_settings_sections('prodotti');
    
    /* https://codex.wordpress.org/Function_Reference/submit_button */
    submit_button( 'Salva le modifiche', 'primary', 'prodotti_salva', true, NULL );
    echo '</form>';
    echo '</div>';
}

    public function sezione_valori_menu()
    {
        /* https://developer.wordpress.org/reference/functions/register_setting/ */
        register_setting( 'campo-prodotti', 'prodotti_test', array($this, 'pulisci_input') );
        /* https://codex.wordpress.org/Function_Reference/add_settings_section */
        add_settings_section( 'sezione_principale', 'Lista dei prodotti', array($this, 'mostra_sezione'), 'prodotti' );
    }
    
    public function pulisci_input($input)
    {
     $size = count($this->options) - 1;
        for($i = 0; $i < $size; $i++)
        {
            $input[$i]['ID'] = sanitize_text_field($input[$i]['ID']);
            $input[$i]['nome'] = sanitize_text_field($input[$i]['nome']);
            $input[$i]['img'] = sanitize_text_field($input[$i]['img']);
            $input[$i]['titolo'] = sanitize_text_field($input[$i]['titolo']);
            $input[$i]['desc'] = sanitize_text_field($input[$i]['desc']);
            $input[$i]['testo'] = sanitize_text_field($input[$i]['testo']);
        }
        if(!empty($input['new']['ID']) && !empty($input['new']['nome']) && !empty($input['new']['img']) && !empty($input['new']['titolo']))
        {
            $input[$size]['ID'] = sanitize_text_field($input['new']['ID']);
            $input[$size]['nome'] = sanitize_text_field($input['new']['nome']);
            $input[$size]['img'] = sanitize_text_field($input['new']['img']);
            $input[$size]['titolo'] = sanitize_text_field($input['new']['titolo']);
            $input[$size]['desc'] = sanitize_text_field($input['new']['desc']);
            $input[$size]['testo'] = sanitize_text_field($input['new']['testo']);
        }
        return $input;
    }
    public function mostra_sezione()
    {
        ?>
        <table class='xs-table'>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Immagine</th>
            <th>Titolo</th>
            <th>Descrizione</th>
            <th>Testo</th>
        </tr>
        <?php
        $size = count($this->options) - 1;
        for($i = 0; $i < $size; $i++)
            {
            echo "<tr>
            <td><input type='text' name='prodotti_test[".$i."][ID]' value='".$this->options[$i]['ID']."'></td>
            <td><input type='text' name='prodotti_test[".$i."][nome]' value='".$this->options[$i]['nome']."'></td>
            <td><input type='text' name='prodotti_test[".$i."][img]' value='".$this->options[$i]['img']."'></td>
            <td><input type='text' name='prodotti_test[".$i."][titolo]' value='".$this->options[$i]['titolo']."'></td>
            <td><textarea name='prodotti_test[".$i."][desc]'>".$this->options[$i]['desc']."</textarea></td>
            <td><textarea name='prodotti_test[".$i."][testo]'>".$this->options[$i]['testo']."</textarea></td>
            </tr>";
            }
            
            
            echo "<tr>
<td><input type='text' name='prodotti_test[new][ID]' placeholder='ID..'></td>
<td><input type='text' name='prodotti_test[new][nome]' placeholder='Nome..'></td>
<td><input type='text' name='prodotti_test[new][img]' placeholder='Immagine..'></td>
<td><input type='text' name='prodotti_test[new][titolo]' placeholder='Titolo..'></td>
<td><input type='text' name='prodotti_test[new][desc]' placeholder='Descrizione..'></td>
<td><textarea name='prodotti_test[new][testo]' placeholder='Testo..'></textarea></td>
</tr>
</table>";
            
    }
   
    public function load_table()
    {
    echo '<div class="tbl-prodotti">';
    for($i = 0; $i < count($this->options) - 1; $i++)
        echo '<div class="prodotti"><a href="?prodotto='.$this->options[$i]['ID'].'"><img src="'.$this->options[$i]['img'].'" /><span>'.$this->options[$i]['nome'].'</span></a></div>';
    echo '</div>';
    
    }
    /* Dynamic Page Content */
    public function dpc ()
    {
        ob_start();
        
        if(isset( $_GET['prodotto'] )) 
            $this->print_page($_GET['prodotto']);
        else
            $this->load_table();
            
        return ob_get_clean();
    }
    
    public function print_page($id)
    {
        for($i = 0; $i < count($this->options) - 1; $i++)
            if($this->options[$i]['ID'] == $id)
                $prodotto = $this->options[$i];
        
        if(!isset($prodotto)) 
        {
            $this->load_table();
            return;
        }
        
        echo '<header class="entry-header"><h1 class="prodotti-titolo">'.$prodotto['titolo'].'</h1></header>';
        echo '<div class="prodotti-content">';
        echo '<div class="prodotti-nome">'.$prodotto['nome'].'</div>';
        echo '<img class="prodotti-img" src="'.$prodotto['img'].'"/>';
        echo '<p class="prodotti-desc">'.$prodotto['desc'].'</p>';
        echo '<p class="prodotti-testo">'.$prodotto['testo'].'</p></div>';
        
    }

}

$prodotti = new prodotti();

?>
