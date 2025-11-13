<?php
/** @var mysqli $conn */
/** @var string $page */

// showpage.php
// tutaj używamy: $conn (z cfg.php) i $page (z index.php)

// zabezpieczenie na wypadek braku $page
if (!isset($page) || $page === '') {
    $page = 'home';
}

// zabezpieczenie przed wstrzyknięciem
$page_safe = mysqli_real_escape_string($conn, $page);

// jeśli zrobiłeś pole alias (polecane):
$sql = "SELECT page_title, page_content 
        FROM page_list 
        WHERE alias = '$page_safe' AND status = 1
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo "<h1>" . htmlspecialchars($row['page_title']) . "</h1>";
    // zakładamy, że page_content zawiera już poprawny HTML
    echo $row['page_content'];
} else {
    // odpowiednik Twojej 404
    echo "<h1>404 - Strona nie istnieje</h1>";
    echo "<p>Przepraszamy, taka strona nie została znaleziona.</p>";
}
?>

