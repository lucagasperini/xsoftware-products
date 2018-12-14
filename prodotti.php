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
    private $def_field = array ( 
        array (
        'ID' => 'ID',
        'nome' => 'ID'
        ),
        array (
        'ID' => 'nome',
        'nome' => 'Nome'
        ),
        array (
        'ID' => 'img',
        'nome' => 'Immagine'
        ),
        array (
        'ID' => 'titolo',
        'nome' => 'Titolo'
        ),
        array (
        'ID' => 'desc',
        'nome' => 'Descrizione'
        ),
        array (
        'ID' => 'testo',
        'nome' => 'Testo'
        )
    );
    
    private $fields = array( array ( ) );
    
    private $options = array( array ( ) );
    
    public function __construct()
    {
        add_action('admin_menu', array($this, 'prodotti_menu'));
        add_action('admin_init', array($this, 'sezione_valori_menu'));
        $this->fields = get_option('prodotti_field', $this->def_field);
        $this->options = get_option('prodotti_test', $this->defaults);
        add_shortcode( 'dpc_prodotti', array($this, 'dpc') ); 
    }
    
    function prodotti_menu()
    {
    // NOME DELLA PAGINA, NOME DEL MENU, PRIVILEGI NECESSARI, URL DEL MODULO PLUGIN, FUNZIONE DA CHIAMARE DENTRO LA CLASSE QUANDO VIENE ATTIVATO IL MENU
    add_menu_page( 'XSoftware Prodotti', 'XSoftware Prodotti', 'manage_options', 'prodotti', array($this, 'pro_main') );
    }
    
    public function pro_main() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'Non hai i permessi per entrare qui!' ) );
    }
    
    wp_enqueue_style('prodotti-style', plugins_url('style.css', __FILE__));
    echo '<div class="wrap">';
    
    if(WP_DEBUG == true) {
        var_dump($this->options);
        var_dump($this->fields);
    }
        
    echo '<h2>Configurazione dei prodotti</h2>';
    
    echo '<form action="options.php" method="post">';
    
    settings_fields('field-add');
    do_settings_sections('fields');
    
    /* https://codex.wordpress.org/Function_Reference/submit_button */
    submit_button( 'Aggiungi i campi', 'primary', 'field_update', true, NULL );
    echo '</form>';
    
    
    echo '<form action="options.php" method="post">';
    
    settings_fields('campo-prodotti');
    do_settings_sections('prodotti');
    
    /* https://codex.wordpress.org/Function_Reference/submit_button */
    submit_button( 'Salva le modifiche', 'primary', 'prodotti_salva', true, NULL );
    echo '</form>';
    echo '</div>';
}

    function sezione_valori_menu()
    {
            /* https://developer.wordpress.org/reference/functions/register_setting/ */
        register_setting( 'field-add', 'prodotti_field', array($this, 'input_field') );
        /* https://codex.wordpress.org/Function_Reference/add_settings_section */
        add_settings_section( 'sezione_field', 'Lista dei campi', array($this, 'mostra_campi'), 'fields' );
        
        /* https://developer.wordpress.org/reference/functions/register_setting/ */
        register_setting( 'campo-prodotti', 'prodotti_test', array($this, 'input_prodotti') );
        /* https://codex.wordpress.org/Function_Reference/add_settings_section */
        add_settings_section( 'sezione_principale', 'Lista dei prodotti', array($this, 'mostra_sezione'), 'prodotti' );
    }
    
    function input_field($input)
    {
     $size = count($this->fields) - 1;
        for($i = 0; $i < $size; $i++)
        {
            $input[$i]['ID'] = sanitize_text_field($input[$i]['ID']);
            $input[$i]['nome'] = sanitize_text_field($input[$i]['nome']);
        }
        if(!empty($input['new']['ID']) && !empty($input['new']['nome']))
        {
            $input[$size]['ID'] = sanitize_text_field($input['new']['ID']);
            $input[$size]['nome'] = sanitize_text_field($input['new']['nome']);
        }
        return $input;
    }
    
    function input_prodotti($input)
    {
     $size = count($this->options) - 1;
        for($i = 0; $i < $size; $i++)
        {
            for($k = 0; $k < $size_field; $k++)
                $input[$i][$this->fields[$k]['ID']] = sanitize_text_field($input[$i][$this->fields[$k]['ID']]);
        }
        if(!empty($input['new']['ID']))
        {
            for($k = 0; $k < $size_field; $k++)
                $input[$size][$this->fields[$k]['ID']] = sanitize_text_field($input[$size][$this->fields[$k]['ID']]);
        }
        return $input;
    }
    function mostra_campi()
    {
        ?>
        <table class='xs-table'>
        <tr>
            <th>ID Campi</th>
            <th>Nome Campi</th>
        </tr>
        <?php
        $size = count($this->fields) - 1;
        for($i = 0; $i < $size; $i++)
            {
            echo "<tr>
            <td><input type='text' name='prodotti_field[".$i."][ID]' value='".$this->fields[$i]['ID']."'></td>
            <td><input type='text' name='prodotti_field[".$i."][nome]' value='".$this->fields[$i]['nome']."'></td>
            </tr>";
            }
            
            
            echo "<tr>
            <td><input type='text' name='prodotti_field[new][ID]' placeholder='ID campo..'></td>
            <td><input type='text' name='prodotti_field[new][nome]' placeholder='Nome campo..'></td>
            </tr>
            </table>";
            
    }
    function mostra_sezione()
    {
        echo "<table class='xs-table'><tr>";
        
        $size_field = count($this->fields) - 1;
        for($i = 0; $i < $size_field; $i++)
            echo "<th>".$this->fields[$i]['nome']."</th>";
        echo "</tr>";
        
        $size_prodotti = count($this->options) - 1;
        for($i = 0; $i < $size_prodotti; $i++)
        {
            echo "<tr>";
            for($k = 0; $k < $size_field; $k++)
                echo "<td><textarea name='prodotti_test[".$i."][".$this->fields[$k]['ID']."]'>".$this->options[$i][$this->fields[$k]['ID']]."</textarea></td>";
            echo "</tr>";
        }
            
            
        echo "<tr>";
            
        for($i = 0; $i < $size_field; $i++)
            echo "<td><textarea name='prodotti_test[new][".$this->fields[$i]['ID']."]' placeholder='".$this->fields[$i]['nome']."..'></textarea></td>";
            
        echo "</tr></table>";
            
    }
   
    function load_table()
    {
    echo '<div class="tbl-prodotti">';
    for($i = 0; $i < count($this->options) - 1; $i++)
        echo '<div class="prodotti"><a href="?prodotto='.$this->options[$i]['ID'].'"><img src="'.$this->options[$i]['img'].'" /><span>'.$this->options[$i]['nome'].'</span></a></div>';
    echo '</div>';
    
    }
    /* Dynamic Page Content */
    function dpc ()
    {
        wp_enqueue_style('prodotti-style', plugins_url('style.css', __FILE__));
        ob_start();
        
        if(isset( $_GET['prodotto'] )) 
            $this->print_page($_GET['prodotto']);
        else
            $this->load_table();
            
        return ob_get_clean();
    }
    
    function print_page($id)
    {
        for($i = 0; $i < count($this->options) - 1; $i++)
            if($this->options[$i]['ID'] == $id)
                $prodotto = $this->options[$i];
        
        if(!isset($prodotto)) 
        {
            $this->load_table();
            return;
        }
        include 'template.php';
        
    }

}

$prodotti = new prodotti();

?>
