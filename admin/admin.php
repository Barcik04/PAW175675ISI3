<?php
// admin/admin.php
// Łączymy konfigurację (sesja, login, hasło, wersja, baza itd.)
require_once __DIR__ . "/../cfg.php";   // jeśli cfg.php jest poziom wyżej

$login ??= "";
$pass  ??= "";
$version ??= "unknown";

/**
 * Wyświetla formularz logowania.
 * Jeśli przekażemy $komunikat, pokaże go nad formularzem.
 */
function FormularzLogowania(string $komunikat = ""): void
{
    if ($komunikat !== "") {
        echo '<p style="color:red;font-weight:bold;">' . htmlspecialchars($komunikat) . '</p>';
    }

    echo '
    <h2>Logowanie do panelu administracyjnego</h2>
    <form method="post" action="admin.php">
        <label>
            Login:
            <input type="text" name="login" />
        </label><br><br>
        <label>
            Hasło:
            <input type="password" name="pass" />
        </label><br><br>
        <input type="submit" name="zaloguj" value="Zaloguj" />
    </form>
    ';
}

// Inicjalizacja flagi w sesji
if (!isset($_SESSION['zalogowany'])) {
    $_SESSION['zalogowany'] = false;
}

// Obsługa wylogowania (np. admin.php?logout=1)
if (isset($_GET['logout'])) {
    $_SESSION['zalogowany'] = false;
    FormularzLogowania("Zostałeś poprawnie wylogowany.");
    exit;
}

// Obsługa próby logowania (wysłany formularz)
if (isset($_POST['zaloguj'])) {
    $loginForm = $_POST['login'] ?? "";
    $passForm  = $_POST['pass'] ?? "";

    // $login i $pass pochodzą z cfg.php
    if ($loginForm === $login && $passForm === $pass) {
        $_SESSION['zalogowany'] = true;
    } else {
        $_SESSION['zalogowany'] = false;
        FormularzLogowania("Błędny login lub hasło.");
        exit;
    }
}

// Jeśli nadal nie zalogowany → pokaż formularz i zakończ
if ($_SESSION['zalogowany'] !== true) {
    FormularzLogowania();
    exit;
}

// Obsługa kliknięcia "Edytuj" – pokaż formularz edycji zamiast listy
if (isset($_POST['edit']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    EdytujPodstrone($id);
    exit;
}

// Obsługa kliknięcia "Dodaj nową podstronę"
if (isset($_POST['add']) || isset($_POST['save_new'])) {
    DodajNowaPodstrone();
    exit;
}

// Obsługa kliknięcia "Usuń"
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    UsunPodstrone($id);
    exit;
}





function ListaPodstron(): void
{
    global $conn;

    $query  = "SELECT id, alias FROM page_list ORDER BY id ASC";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "<p>Błąd zapytania: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
        return;
    }

    echo '<h2>Lista podstron</h2>';

    // PRZYCISK „Dodaj nową podstronę”
    echo '<form method="post" action="admin.php" style="margin-bottom:10px;">';
    echo '<input type="submit" name="add" value="Dodaj nową podstronę">';
    echo '</form>';

    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>ID</th><th>Alias</th><th>Akcje</th></tr>';

    while ($row = mysqli_fetch_assoc($result)) {

        $id    = (int)$row['id'];
        $alias = htmlspecialchars($row['alias']);

        echo '<tr>';
        echo '<td>' . $id . '</td>';
        echo '<td>' . $alias . '</td>';
        echo '<td>';

        // EDYTUJ
        echo '<form method="post" action="admin.php" style="display:inline-block; margin-right:6px;">';
        echo '<input type="hidden" name="id" value="' . $id . '">';
        echo '<input type="submit" name="edit" value="Edytuj">';
        echo '</form>';

        // USUŃ (obsłużymy w kolejnym zadaniu)
        echo '<form method="post" action="admin.php" style="display:inline-block;">';
        echo '<input type="hidden" name="id" value="' . $id . '">';
        echo '<input type="submit" name="delete" value="Usuń">';
        echo '</form>';

        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';
}



function EdytujPodstrone(int $id): void
{
    global $conn;

    $id = (int)$id;

    // Pobierz dane podstrony z bazy
    $query  = "SELECT alias, page_content, status FROM page_list WHERE id = $id LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        echo "<p>Nie znaleziono podstrony o ID $id.</p>";
        return;
    }

    $row         = mysqli_fetch_assoc($result);
    $alias       = htmlspecialchars($row['alias']);
    $pageContent = htmlspecialchars($row['page_content']);
    $status      = (int)$row['status']; // 1 = aktywna, 0 = nieaktywna

    echo '<h2>Edycja podstrony</h2>';
    echo '<form method="post" action="admin.php">';
    echo '<input type="hidden" name="id" value="' . $id . '">';

    // input text – alias / tytuł
    echo '<p>';
    echo '<label>Tytuł / alias:<br>';
    echo '<input type="text" name="alias" value="' . $alias . '" size="60">';
    echo '</label>';
    echo '</p>';

    // textarea – treść
    echo '<p>';
    echo '<label>Treść strony:<br>';
    echo '<textarea name="page_content" rows="10" cols="80">' . $pageContent . '</textarea>';
    echo '</label>';
    echo '</p>';

    // checkbox – zaznaczona jeśli status = 1
    echo '<p>';
    echo '<label>';
    echo '<input type="checkbox" name="status" value="1" ' . ($status ? 'checked' : '') . '>';
    echo ' Strona aktywna';
    echo '</label>';
    echo '</p>';

    echo '<p><input type="submit" name="save" value="Zapisz zmiany"></p>';
    echo '</form>';
}

function DodajNowaPodstrone(): void
{
    global $conn;

    // jeśli formularz został wysłany → próbujemy dodać do bazy
    if (isset($_POST['save_new'])) {

        $alias       = trim($_POST['alias'] ?? "");
        $pageContent = trim($_POST['page_content'] ?? "");
        $status      = isset($_POST['status']) ? 1 : 0;

        if ($alias === "") {
            echo "<p style='color:red;font-weight:bold;'>Alias (tytuł) nie może być pusty.</p>";
        } else {

            $aliasEsc = mysqli_real_escape_string($conn, $alias);
            $contentEsc = mysqli_real_escape_string($conn, $pageContent);

            $query = "
                INSERT INTO page_list (alias, page_content, status)
                VALUES ('$aliasEsc', '$contentEsc', $status)
            ";

            if (mysqli_query($conn, $query)) {
                echo "<p>Nowa podstrona została dodana.</p>";
                echo "<p><a href='admin.php'>Wróć do listy podstron</a></p>";
                return; // NIE pokazujemy ponownie formularza
            } else {
                echo "<p style='color:red;'>Błąd podczas dodawania: "
                        . htmlspecialchars(mysqli_error($conn)) . "</p>";
            }
        }
    }

    // formularz dodawania (pokazywany przy pierwszym wejściu lub po błędzie walidacji)
    echo '<h2>Dodaj nową podstronę</h2>';
    echo '<form method="post" action="admin.php">';

    echo '<p>';
    echo '<label>Tytuł / alias:<br>';
    echo '<input type="text" name="alias" value="'
            . htmlspecialchars($_POST['alias'] ?? "") . '" size="60">';
    echo '</label>';
    echo '</p>';

    echo '<p>';
    echo '<label>Treść strony:<br>';
    echo '<textarea name="page_content" rows="10" cols="80">'
            . htmlspecialchars($_POST['page_content'] ?? "") . '</textarea>';
    echo '</label>';
    echo '</p>';

    echo '<p>';
    echo '<label>';
    echo '<input type="checkbox" name="status" value="1" '
            . (isset($_POST['status']) ? 'checked' : '') . '>';
    echo ' Strona aktywna';
    echo '</label>';
    echo '</p>';

    echo '<p><input type="submit" name="save_new" value="Dodaj podstronę"></p>';
    echo '</form>';

    echo "<p><a href='admin.php'>Anuluj i wróć do listy</a></p>";
}

function UsunPodstrone(int $id): void
{
    global $conn;

    $id = (int)$id;

    // DELETE z LIMIT 1 — wymagane przez TIP 3
    $query = "DELETE FROM page_list WHERE id = $id LIMIT 1";

    if (mysqli_query($conn, $query)) {
        echo "<p>Podstrona o ID $id została usunięta.</p>";
        echo "<p><a href='admin.php'>Wróć do listy podstron</a></p>";
    } else {
        echo "<p style='color:red;'>Błąd podczas usuwania: " .
                htmlspecialchars(mysqli_error($conn)) . "</p>";
    }
}



// ===============================
//  CZĘŚĆ DOSTĘPNA TYLKO DLA ADMINA
// ===============================
echo "<h1>Panel administracyjny (wersja {$version})</h1>";
echo "<p>Jesteś zalogowany jako <strong>" . htmlspecialchars($login) . "</strong>.</p>";
echo '<p><a href="admin.php?logout=1">Wyloguj</a></p>';

echo "<hr>";
ListaPodstron();

