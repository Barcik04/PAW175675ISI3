<?php
global $conn;
require_once 'cfg.php';

// 🔐 zabezpieczenie: tylko zalogowany admin ma dostęp
if (($_SESSION['zalogowany'] ?? false) !== true) {
    header('Location: admin/admin.php');
    exit;
}

$errors = [];
$messages = [];

// === 1. Obsługa dodawania kategorii / podkategorii ===
function fetchCategories(mysqli $conn): array
{
    $result = mysqli_query($conn, 'SELECT id, matka, nazwa FROM categories ORDER BY nazwa');
    if ($result === false) {
        return ['data' => [], 'error' => 'Błąd pobierania kategorii: ' . mysqli_error($conn)];
    }

    return ['data' => mysqli_fetch_all($result, MYSQLI_ASSOC), 'error' => null];
}

function buildByParent(array $categories): array
{
    $byParent = [];

    foreach ($categories as $cat) {
        $parentId = (int)($cat['matka'] ?? 0);
        $byParent[$parentId][] = $cat;
    }

    return $byParent;
}

/**
 * Usuwa wskazaną kategorię oraz wszystkie jej potomne rekordy.
 */
function deleteCategoryWithChildren(int $id, mysqli $conn): ?string
{
    if ($id <= 0) {
        return 'Nieprawidłowe ID kategorii.';
    }

    $toDelete = [$id];
    $stmtChildren = mysqli_prepare($conn, 'SELECT id FROM categories WHERE matka = ?');

    if ($stmtChildren === false) {
        return 'Błąd przygotowania zapytania: ' . mysqli_error($conn);
    }

    for ($i = 0; $i < count($toDelete); $i++) {
        $currentId = $toDelete[$i];
        mysqli_stmt_bind_param($stmtChildren, 'i', $currentId);

        if (!mysqli_stmt_execute($stmtChildren)) {
            mysqli_stmt_close($stmtChildren);
            return 'Nie udało się pobrać potomków: ' . mysqli_stmt_error($stmtChildren);
        }

        $result = mysqli_stmt_get_result($stmtChildren);
        while ($row = mysqli_fetch_assoc($result)) {
            $toDelete[] = (int)$row['id'];
        }
    }

    mysqli_stmt_close($stmtChildren);


    // Usuwamy od najniższego poziomu
    $stmtDelete = mysqli_prepare($conn, 'DELETE FROM categories WHERE id = ? LIMIT 1');
    if ($stmtDelete === false) {
        return 'Błąd przygotowania usuwania: ' . mysqli_error($conn);
    }


    for ($i = count($toDelete) - 1; $i >= 0; $i--) {
        $deleteId = $toDelete[$i];
        mysqli_stmt_bind_param($stmtDelete, 'i', $deleteId);

        if (!mysqli_stmt_execute($stmtDelete)) {
            mysqli_stmt_close($stmtDelete);
            return 'Nie udało się usunąć kategorii: ' . mysqli_stmt_error($stmtDelete);
        }


    }
    mysqli_stmt_close($stmtDelete);
    return null;
}

    // === Obsługa formularzy ===
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $deleteId = (int)($_POST['delete_id'] ?? 0);
            $error = deleteCategoryWithChildren($deleteId, $conn);


            if ($error) {
                $errors[] = $error;
            } else {
                $messages[] = 'Kategoria została usunięta.';
                header('Location: categories.php?deleted=1');
                exit;
            }
        } else {
            $nazwa = trim($_POST['nazwa'] ?? '');
            $matka = isset($_POST['matka']) ? (int)$_POST['matka'] : 0;

            if ($nazwa === '') {
                $errors[] = 'Nazwa kategorii nie może być pusta.';
            }

            if (!$errors) {
                if ($matka === 0) {
                    $stmt = mysqli_prepare($conn, 'INSERT INTO categories (matka, nazwa) VALUES (NULL, ?)');
                    $typeString = 's';
                    $params = [$nazwa];
                } else {
                    $stmt = mysqli_prepare($conn, 'INSERT INTO categories (matka, nazwa) VALUES (?, ?)');
                    $typeString = 'is';
                    $params = [$matka, $nazwa];
                }

                if ($stmt === false) {
                    $errors[] = 'Błąd przygotowania zapytania: ' . mysqli_error($conn);
                } else {
                    mysqli_stmt_bind_param($stmt, $typeString, ...$params);

                    if (!mysqli_stmt_execute($stmt)) {
                        $errors[] = 'Nie udało się dodać kategorii: ' . mysqli_stmt_error($stmt);
                    } else {
                        mysqli_stmt_close($stmt);
                        header('Location: categories.php?added=1');
                        exit;
                    }

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    if (isset($_GET['added'])) {
        $messages[] = 'Kategoria została dodana poprawnie.';
    }

    if (isset($_GET['deleted'])) {
        $messages[] = 'Kategoria została usunięta.';
    }

    $fetchResult = fetchCategories($conn);
    $categories = $fetchResult['data'];
    if ($fetchResult['error']) {
        $errors[] = $fetchResult['error'];
    }

    $byParent = buildByParent($categories);

/**
 * Rekurencyjnie renderuje drzewo kategorii z przyciskiem usuwania.
 */

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
            echo "        <form method=\"post\" style=\"display:inline;margin-left:8px;\">";
            echo "            <input type=\"hidden\" name=\"delete_id\" value=\"{$id}\">";
            echo "            <button type=\"submit\">Usuń</button>";
            echo "        </form>";

            renderTree($id, $byParent);
            echo "</li>\n";
        }
        echo "</ul>\n";
    }

?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Kategorie produktów</title>
</head>
<body>
<h1>Zarządzanie kategoriami produktów</h1>
<p><a href="admin/admin.php">← Powrót do panelu administracyjnego</a></p>

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

<h2>Dodaj kategorię / podkategorię</h2>
<form method="post">
    <label>
        Nazwa kategorii:<br>
        <input type="text" name="nazwa" required>
    </label>
    <br><br>

    <label>
        Kategoria matka:<br>
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