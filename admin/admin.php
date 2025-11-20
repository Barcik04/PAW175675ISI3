<?php
// admin/admin.php
// Łączymy konfigurację (sesja, login, hasło, wersja, baza itd.)
require_once __DIR__ . "/../cfg.php";   // jeśli cfg.php jest poziom wyżej

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

// ===============================
//  CZĘŚĆ DOSTĘPNA TYLKO DLA ADMINA
// ===============================
echo "<h1>Panel administracyjny (wersja {$version})</h1>";
echo "<p>Jesteś zalogowany jako <strong>" . htmlspecialchars($login) . "</strong>.</p>";
echo '<p><a href="admin.php?logout=1">Wyloguj</a></p>';

echo "<hr>";
echo "<p>Tu później dodasz dalsze metody administracyjne (np. zarządzanie stronami).</p>";
