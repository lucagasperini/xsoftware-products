<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN'))
{
        die;
}

$options = get_option('product_global');

$conn = new mysqli($options['db_host'], $options['db_user'], $options['db_pass'], $options['db_name']);

if (mysqli_connect_error()) {
        die("Connection to database failed: " . mysqli_connect_error());
}
$conn->query("DROP TABLE xs_products;");
delete_option('product_global');
?>
