<?php
require_once 'cfg.php';   // tu korzystamy z Twojego połączenia $conn

// === 1. Obsługa dodawania kategorii / podkategorii ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa = trim($_POST['nazwa'] ?? '');
    $matka = isset($_POST['matka']) ? (int)$_POST['matka'] : 0;

    if ($nazwa !== '') {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO categories (matka, nazwa) VALUES (?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "is", $matka, $nazwa);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // refresh, żeby po F5 nie dodało drugi raz
        header("Location: categories.php");
        exit;
    }
}

// === 2. Pobranie wszystkich kategorii ===
$categories = [];

$sql = "SELECT id, matka, nazwa FROM categories ORDER BY nazwa";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Kategorie produktów</title>
</head>
<body>

<h2>Dodaj kategorię / podkategorię</h2>

<form method="post">
    <label>
        Nazwa kategorii:
        <input type="text" name="nazwa" required>
    </label>
    <br><br>

    <label>
        Kategoria matka:
        <select name="matka">
            <option value="0">(kategoria główna)</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>">
                    <?= htmlspecialchars($cat['nazwa']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <br><br>

    <button type="submit">Zapisz</button>
</form>

<hr>

<h2>Drzewo kategorii</h2>

<ul>
    <?php foreach ($categories as $parent): ?>
        <?php if ($parent['matka'] == 0): ?>
            <li>
                <strong><?= htmlspecialchars($parent['nazwa']) ?></strong>
                <ul>
                    <?php foreach ($categories as $child): ?>
                        <?php if ($child['matka'] == $parent['id']): ?>
                            <li>- <?= htmlspecialchars($child['nazwa']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>

</body>
</html>
