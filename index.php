<?php
require_once "cfg.php";

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
if ($page == '') {
    $page = 'home';
}
?>

<!DOCTYPE html>
<html lang="en" class="font-sans leading-loose">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Igor Barcikowski</title>
    <link rel="stylesheet" href="175675/style.css" />
    <script src="175675/js/highlight_nav_active.js"></script>
    <script src="175675/js/color_background.js"></script>
    <script src="175675/js/timedate.js"></script>
</head>
<body>

<nav class="navbar">
    <ul>
        <li><a href="index.php?page=home" class="cv">CV</a></li>
        <li><a href="index.php?page=about">About me</a></li>
        <li><a href="index.php?page=stack">Stack</a></li>
        <li><a href="index.php?page=experience">Experience</a></li>
        <li><a href="index.php?page=projects">Projects</a></li>
        <li><a href="index.php?page=contact">Contact</a></li>
        <li><a href="admin/admin.php">Admin</a></li>
    </ul>
</nav>

<main>
    <?php include "showpage.php"; ?>
</main>

</body>
</html>
