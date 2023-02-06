<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
set_error_handler(function ($errno, $error) {
    if (!str_starts_with($error, 'Undefined array key')) {
        return false;  //default error handler.
    } else {
        trigger_error($error, E_USER_NOTICE);
        return true;
    }
}, E_WARNING);
ini_set("error_reporting",  E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
if (!isset($_SESSION["usernameLogin"])) {
    header("Location: Login.php");
    exit();
}
include("./common/header.php");
?>


<?php session_destroy();
header("Location: Index.php");
exit();
?>

<?php
include('./common/footer.php');
