<?php
if (file_exists('../config.php')) die('Already Installed');

if ($_POST) {
    $conn = new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$_POST['db_name']}`");
    $conn->select_db($_POST['db_name']);

    $sql = file_get_contents('install.sql');
    $conn->multi_query($sql);

    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);

    $conn->query("INSERT INTO admins (username,password) 
                  VALUES ('{$_POST['admin_user']}','$admin_pass')");

    $config = "<?php
define('DB_HOST','{$_POST['db_host']}');
define('DB_USER','{$_POST['db_user']}');
define('DB_PASS','{$_POST['db_pass']}');
define('DB_NAME','{$_POST['db_name']}');
define('SITE_URL','{$_POST['site_url']}');
define('ADS_URL','https://hypechats.com');
?>";
    file_put_contents('../config.php',$config);
    echo "Installed Successfully. Delete /install folder.";
    exit;
}
?>

<form method="post">
<h2>HYLS Install</h2>
<input name="db_host" placeholder="DB Host" required><br>
<input name="db_user" placeholder="DB User" required><br>
<input name="db_pass" placeholder="DB Pass"><br>
<input name="db_name" placeholder="DB Name" required><br>
<input name="site_url" placeholder="Site URL" required><br>
<input name="admin_user" placeholder="Admin User" required><br>
<input name="admin_pass" placeholder="Admin Password" required><br>
<button>Install</button>
</form>
