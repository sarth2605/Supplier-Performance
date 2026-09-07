<?php
$ini_path = 'C:/xampp/mysql/bin/my.ini';
$content = file_get_contents($ini_path);
if (strpos($content, 'innodb_force_recovery') === false) {
    $content = preg_replace('/(\[mysqld\]\r?\n)/', "$1innodb_force_recovery = 1\r\n", $content, 1);
    file_put_contents($ini_path, $content);
    echo "Added innodb_force_recovery = 1\n";
} else {
    echo "Already present\n";
}
