<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN'))
{
        die;
}

$options = get_option('product_global');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if (mysqli_connect_error()) {
        die("Connection to database failed: " . mysqli_connect_error());
}
$conn->query("DROP TABLE xs_products;");
delete_option('product_global');
?>
