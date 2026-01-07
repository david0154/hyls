if (file_exists('../config.php')) die('Already installed');

if ($_POST) {
    $db = new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name']);
    $sql = file_get_contents('install.sql');
    $db->multi_query($sql);

    $config = "<?php
    define('DB_HOST','{$_POST['db_host']}');
    define('DB_USER','{$_POST['db_user']}');
    define('DB_PASS','{$_POST['db_pass']}');
    define('DB_NAME','{$_POST['db_name']}');
    define('SITE_URL','{$_POST['site_url']}');
    define('ADS_URL','https://hypechats.com');
    ?>";
    file_put_contents('../config.php',$config);
    echo "Installed Successfully";
}
