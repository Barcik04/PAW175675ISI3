<?php
/** @var mysqli $conn */
/** @var string $page */

// showpage.php

if (!isset($page) || $page === '') {
    $page = 'home';
}

$page_safe = mysqli_real_escape_string($conn, $page);

$sql = "SELECT page_content 
        FROM page_list 
        WHERE alias = '$page_safe'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo $row['page_content'];
} else {
    echo "<h1>404 - Strona nie istnieje</h1>";
    echo "<p>Przepraszamy, taka strona nie została znaleziona.</p>";
}
?>
