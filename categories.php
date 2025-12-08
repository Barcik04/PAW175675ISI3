<?php
global $conn;
require_once 'cfg.php';

// 🔐 zabezpieczenie: tylko zalogowany admin ma dostęp
if (empty($_SESSION['admin'])) {        // dostosuj nazwę klucza do tego, co masz w admin.php
    header('Location: admin.php');
    exit;
}

$errors = [];
$messages = [];

// === 1. Obsługa dodawania kategorii / podkategorii ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa = trim($_POST['nazwa'] ?? '');
    $matka = isset($_POST['matka']) ? (int)$_POST['matka'] : 0;

    if ($nazwa === '') {
        $errors[] = 'Nazwa kategorii nie może być pusta.';
    }

    if (!$errors) {
        $matkaParam = $matka > 0 ? $matka : null;
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO categories (matka, nazwa) VALUES (?, ?)'
        );

        if ($stmt === false) {
            $errors[] = 'Błąd przygotowania zapytania: ' . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, 'is', $matkaParam, $nazwa);

            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = 'Nie udało się dodać kategorii: ' . mysqli_stmt_error($stmt);
            } else {
                $messages[] = 'Kategoria została dodana poprawnie.';
            }

            mysqli_stmt_close($stmt);
        }

        if (!$errors) {
            // refresh, żeby po F5 nie dodało drugi raz
            header('Location: categories.php?added=1');
            exit;
        }
    }
}

if (isset($_GET['added'])) {
    $messages[] = 'Kategoria została dodana poprawnie.';
}

// === 2. Pobranie wszystkich kategorii ===
$categories = [];

$sql = 'SELECT id, matka, nazwa FROM categories ORDER BY nazwa';
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
} elseif ($result === false) {
    $errors[] = 'Błąd pobierania kategorii: ' . mysqli_error($conn);
}

function buildTree(array $categories): array
{
    $tree = [];
    $byParent = [];

    foreach ($categories as $cat) {
        $parentId = (int)($cat['matka'] ?? 0);
        $byParent[$parentId][] = $cat;
    }

    $stack = [0];
    $tree[0] = $byParent[0] ?? [];

    while ($stack) {
        $parentId = array_pop($stack);
        if (!isset($byParent[$parentId])) {
            continue;
        }

        foreach ($byParent[$parentId] as $child) {
            $childId = (int)$child['id'];
            $tree[$childId] = $byParent[$childId] ?? [];
            $stack[] = $childId;
        }
    }

    return [$tree, $byParent];
}

function renderTree(int $parentId, array $byParent): void
{
    if (empty($byParent[$parentId])) {
        return;
    }

    echo "<ul>\n";
    foreach ($byParent[$parentId] as $node) {
        $name = htmlspecialchars($node['nazwa']);
        $id = (int)$node['id'];
        echo "    <li><strong>{$name}</strong>";
        renderTree($id, $byParent);
        echo "</li>\n";
    }
    echo "</ul>\n";
}

[$tree, $byParent] = buildTree($categories);
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Kategorie produktów</title>
</head>
<body>

<h2>Dodaj kategorię / podkategorię</h2>

<?php if ($errors): ?>
    <div style="color: red;">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($messages): ?>
    <div style="color: green;">
        <ul>
            <?php foreach ($messages as $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

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

<?php renderTree(0, $byParent); ?>

</body>
</html>
