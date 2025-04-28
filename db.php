<?php
$db = new mysqli('localhost', 'root', '', 'ujin_project');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
?>