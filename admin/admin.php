<?php
// admin/admin.php
require_once __DIR__ . "/../cfg.php";

$login ??= "";
$pass  ??= "";
$version ??= "unknown";

function AdminHeader(string $title = "Panel administracyjny"): void
{
    echo '<!doctype html>';
    echo '<html lang="pl">';
    echo '<head>';
    echo '  <meta charset="UTF-8">';
    echo '  <meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '  <title>' . htmlspecialchars($title) . '</title>';
    echo '  <link rel="stylesheet" href="../175675/admin.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="admin-panel">';
    echo '  <div class="admin-panel__container">';
}

function AdminFooter(): void
{
    echo '  </div>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}

/**
 * Wyświetla formularz logowania.
 */
function FormularzLogowania(string $komunikat = ""): void
{
    AdminHeader("Logowanie — Panel administracyjny");

    echo '<h2 class="admin-panel__title">Logowanie do panelu administracyjnego</h2>';

    if ($komunikat !== "") {
        echo '<div class="admin-panel__notice admin-panel__notice--error">'
            . htmlspecialchars($komunikat) . '</div>';
    }

    echo '
    <div class="admin-panel__card">
      <form class="admin-panel__form" method="post" action="admin.php">
          <label class="admin-panel__label">
              Login:
              <input class="admin-panel__input" type="text" name="login" />
          </label>

          <label class="admin-panel__label">
              Hasło:
              <input class="admin-panel__input" type="password" name="pass" />
          </label>

          <input class="admin-panel__btn" type="submit" name="zaloguj" value="Zaloguj" />
      </form>
    </div>
    ';

    AdminFooter();
}

// Inicjalizacja flagi w sesji
if (!isset($_SESSION['zalogowany'])) {
    $_SESSION['zalogowany'] = false;
}

// Obsługa wylogowania
if (isset($_GET['logout'])) {
    $_SESSION['zalogowany'] = false;
    FormularzLogowania("Zostałeś poprawnie wylogowany.");
    exit;
}

// Obsługa próby logowania
if (isset($_POST['zaloguj'])) {
    $loginForm = $_POST['login'] ?? "";
    $passForm  = $_POST['pass'] ?? "";

    if ($loginForm === $login && $passForm === $pass) {
        $_SESSION['zalogowany'] = true;
    } else {
        $_SESSION['zalogowany'] = false;
        FormularzLogowania("Błędny login lub hasło.");
        exit;
    }
}

if ($_SESSION['zalogowany'] !== true) {
    FormularzLogowania();
    exit;
}

// Obsługa zapisu zmian w edycji
if (isset($_POST['save']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    ZapiszPodstrone($id);
    exit;
}

// Obsługa kliknięcia "Edytuj"
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
        echo '<div class="admin-panel__notice admin-panel__notice--error">Błąd zapytania: '
            . htmlspecialchars(mysqli_error($conn)) . '</div>';
        return;
    }

    echo '<h2 class="admin-panel__subtitle">Lista podstron</h2>';

    echo '<div class="admin-panel__actions">';
    echo '  <form class="admin-panel__inline" method="post" action="admin.php">';
    echo '    <input class="admin-panel__btn" type="submit" name="add" value="Dodaj nową podstronę">';
    echo '  </form>';
    echo '</div>';

    echo '<div class="admin-panel__table-wrap">';
    echo '<table class="admin-panel__table">';
    echo '<thead><tr><th>ID</th><th>Alias</th><th>Akcje</th></tr></thead><tbody>';

    while ($row = mysqli_fetch_assoc($result)) {
        $id    = (int)$row['id'];
        $alias = htmlspecialchars($row['alias']);

        echo '<tr>';
        echo '  <td>' . $id . '</td>';
        echo '  <td>' . $alias . '</td>';
        echo '  <td class="admin-panel__td-actions">';

        echo '    <form class="admin-panel__inline" method="post" action="admin.php">';
        echo '      <input type="hidden" name="id" value="' . $id . '">';
        echo '      <input class="admin-panel__btn admin-panel__btn--ghost" type="submit" name="edit" value="Edytuj">';
        echo '    </form>';

        echo '    <form class="admin-panel__inline" method="post" action="admin.php">';
        echo '      <input type="hidden" name="id" value="' . $id . '">';
        echo '      <input class="admin-panel__btn admin-panel__btn--danger" type="submit" name="delete" value="Usuń">';
        echo '    </form>';

        echo '  </td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

function EdytujPodstrone(int $id, array $prefill = []): void
{
    global $conn;

    AdminHeader("Edycja podstrony — Panel admina");

    $id = (int)$id;

    $query  = "SELECT alias, page_content, status FROM page_list WHERE id = $id LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        echo '<div class="admin-panel__notice admin-panel__notice--error">Nie znaleziono podstrony o ID '
            . $id . '.</div>';
        echo '<div class="admin-panel__toplinks"><a class="admin-panel__link" href="admin.php">Wróć</a></div>';
        AdminFooter();
        return;
    }

    $row = mysqli_fetch_assoc($result);

    $aliasSource       = $prefill['alias'] ?? $row['alias'];
    $pageContentSource = $prefill['page_content'] ?? $row['page_content'];
    $statusSource      = array_key_exists('status', $prefill) ? (int)$prefill['status'] : (int)$row['status'];

    $alias       = htmlspecialchars($aliasSource);
    $pageContent = htmlspecialchars($pageContentSource);
    $status      = $statusSource;

    echo '<h2 class="admin-panel__title">Edycja podstrony</h2>';

    echo '<div class="admin-panel__card">';
    echo '<form class="admin-panel__form" method="post" action="admin.php">';
    echo '<input type="hidden" name="id" value="' . $id . '">';

    echo '<label class="admin-panel__label">Tytuł / alias:';
    echo '<input class="admin-panel__input" type="text" name="alias" value="' . $alias . '">';
    echo '</label>';

    echo '<label class="admin-panel__label">Treść strony:';
    echo '<textarea class="admin-panel__textarea" name="page_content" rows="10">' . $pageContent . '</textarea>';
    echo '</label>';

    echo '<label class="admin-panel__check">';
    echo '<input type="checkbox" name="status" value="1" ' . ($status ? 'checked' : '') . '>';
    echo '<span>Strona aktywna</span>';
    echo '</label>';

    echo '<div class="admin-panel__form-actions">';
    echo '<input class="admin-panel__btn" type="submit" name="save" value="Zapisz zmiany">';
    echo '<a class="admin-panel__link admin-panel__link--muted" href="admin.php">Anuluj</a>';
    echo '</div>';

    echo '</form>';
    echo '</div>';

    AdminFooter();
}

function ZapiszPodstrone(int $id): void
{
    global $conn;

    AdminHeader("Zapis — Panel admina");

    $id = (int)$id;
    $alias       = trim($_POST['alias'] ?? "");
    $pageContent = trim($_POST['page_content'] ?? "");
    $status      = isset($_POST['status']) ? 1 : 0;

    if ($alias === '') {
        echo '<div class="admin-panel__notice admin-panel__notice--error">Alias (tytuł) nie może być pusty.</div>';
        AdminFooter();
        EdytujPodstrone($id, $_POST);
        return;
    }

    $aliasEsc   = mysqli_real_escape_string($conn, $alias);
    $contentEsc = mysqli_real_escape_string($conn, $pageContent);

    $query = "
        UPDATE page_list
        SET alias = '$aliasEsc', page_content = '$contentEsc', status = $status
        WHERE id = $id
        LIMIT 1
    ";

    if (mysqli_query($conn, $query)) {
        echo '<div class="admin-panel__notice admin-panel__notice--success">Podstrona została zaktualizowana.</div>';
        echo '<div class="admin-panel__toplinks"><a class="admin-panel__link" href="admin.php">Wróć do listy podstron</a></div>';
    } else {
        echo '<div class="admin-panel__notice admin-panel__notice--error">Błąd podczas zapisywania: '
            . htmlspecialchars(mysqli_error($conn)) . '</div>';
        echo '<div class="admin-panel__toplinks"><a class="admin-panel__link" href="admin.php">Wróć</a></div>';
    }

    AdminFooter();
}

function DodajNowaPodstrone(): void
{
    global $conn;

    AdminHeader("Dodaj podstronę — Panel admina");

    if (isset($_POST['save_new'])) {
        $alias       = trim($_POST['alias'] ?? "");
        $pageContent = trim($_POST['page_content'] ?? "");
        $status      = isset($_POST['status']) ? 1 : 0;

        if ($alias === "") {
            echo '<div class="admin-panel__notice admin-panel__notice--error">Alias (tytuł) nie może być pusty.</div>';
        } else {
            $aliasEsc = mysqli_real_escape_string($conn, $alias);
            $contentEsc = mysqli_real_escape_string($conn, $pageContent);

            $query = "
                INSERT INTO page_list (alias, page_content, status)
                VALUES ('$aliasEsc', '$contentEsc', $status)
            ";

            if (mysqli_query($conn, $query)) {
                echo '<div class="admin-panel__notice admin-panel__notice--success">Nowa podstrona została dodana.</div>';
                echo '<div class="admin-panel__toplinks"><a class="admin-panel__link" href="admin.php">Wróć do listy podstron</a></div>';
                AdminFooter();
                return;
            } else {
                echo '<div class="admin-panel__notice admin-panel__notice--error">Błąd podczas dodawania: '
                    . htmlspecialchars(mysqli_error($conn)) . '</div>';
            }
        }
    }

    echo '<h2 class="admin-panel__title">Dodaj nową podstronę</h2>';

    echo '<div class="admin-panel__card">';
    echo '<form class="admin-panel__form" method="post" action="admin.php">';

    echo '<label class="admin-panel__label">Tytuł / alias:';
    echo '<input class="admin-panel__input" type="text" name="alias" value="'
        . htmlspecialchars($_POST['alias'] ?? "") . '">';
    echo '</label>';

    echo '<label class="admin-panel__label">Treść strony:';
    echo '<textarea class="admin-panel__textarea" name="page_content" rows="10">'
        . htmlspecialchars($_POST['page_content'] ?? "") . '</textarea>';
    echo '</label>';

    echo '<label class="admin-panel__check">';
    echo '<input type="checkbox" name="status" value="1" ' . (isset($_POST['status']) ? 'checked' : '') . '>';
    echo '<span>Strona aktywna</span>';
    echo '</label>';

    echo '<div class="admin-panel__form-actions">';
    echo '<input class="admin-panel__btn" type="submit" name="save_new" value="Dodaj podstronę">';
    echo '<a class="admin-panel__link admin-panel__link--muted" href="admin.php">Anuluj</a>';
    echo '</div>';

    echo '</form>';
    echo '</div>';

    AdminFooter();
}

function UsunPodstrone(int $id): void
{
    global $conn;

    AdminHeader("Usuń podstronę — Panel admina");

    $id = (int)$id;
    $query = "DELETE FROM page_list WHERE id = $id LIMIT 1";

    if (mysqli_query($conn, $query)) {
        echo '<div class="admin-panel__notice admin-panel__notice--success">Podstrona o ID '
            . $id . ' została usunięta.</div>';
        echo '<div class="admin-panel__toplinks"><a class="admin-panel__link" href="admin.php">Wróć do listy podstron</a></div>';
    } else {
        echo '<div class="admin-panel__notice admin-panel__notice--error">Błąd podczas usuwania: '
            . htmlspecialchars(mysqli_error($conn)) . '</div>';
        echo '<div class="admin-panel__toplinks"><a class="admin-panel__link" href="admin.php">Wróć</a></div>';
    }

    AdminFooter();
}

AdminHeader("Panel administracyjny");

echo '<h1 class="admin-panel__h1">Panel administracyjny (wersja ' . htmlspecialchars($version) . ')</h1>';
echo '<p class="admin-panel__meta">Jesteś zalogowany jako <strong>' . htmlspecialchars($login) . '</strong>.</p>';

echo '<div class="admin-panel__toplinks">';
echo '  <a class="admin-panel__link" href="admin.php?logout=1">Wyloguj</a>';
echo '  <a class="admin-panel__link admin-panel__link--strong" href="../index.php">← Powrót na stronę główną</a>';
echo '</div>';

echo '<div class="admin-panel__divider"></div>';

ListaPodstron();

AdminFooter();
