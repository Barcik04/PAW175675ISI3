<?php
// contact.php
require_once __DIR__ . '/cfg.php';   // ścieżka jak w innych plikach

function PokazKontakt(string $komunikat = ""): void
{
    if ($komunikat !== "") {
        echo '<p class="info" style="color:red;font-weight:bold;">'.$komunikat.'</p>';
    }

    echo <<<HTML
    <h2>Formularz kontaktowy</h2>

    <form class="contact-form" method="post" action="index.php?page=contact">
        
        <label>Imię i nazwisko:<br>
            <input type="text" name="name" required>
        </label><br>

        <label>E-mail:<br>
            <input type="email" name="email" required>
        </label><br>

        <label>Temat:<br>
            <input type="text" name="subject" required>
        </label><br>

        <label>Treść wiadomości:<br>
            <textarea name="message" rows="6" required></textarea>
        </label><br>

        <button type="submit" name="action" value="send">Wyślij</button>
    </form>
    ';

    echo '
    <h2>Formularz kontaktowy</h2>

    <form class="contact-form" method="post" action="index.php?page=contact">
      
        <button type="submit" name="action" value="send">Wyślij</button>
    </form>
    


    <h3>Przypomnienie hasła do panelu admina</h3>

    <form class="contact-form" method="post" action="index.php?page=contact">
        <label>Adres e-mail:<br>
            <input type="email" name="remind_email" required>
        </label><br>

        <button type="submit" name="action" value="remind">Przypomnij hasło</button>
    </form>
    HTML;

}


// 2. WyslijMailKontakt() – wysłanie wiadomości do admina
function WyslijMailKontakt(): string
{
    if (
        empty($_POST['name']) ||
        empty($_POST['message']) ||
        empty($_POST['subject']) ||
        empty($_POST['email'])
    ) {
        return "Nie wypełniłeś pól.";
    }

    // Tworzymy tablicę maila zgodnie z przykładem w zadaniu
    $mail['subject']   = $_POST['subject'];
    $mail['body']      = $_POST['message'];
    $mail['sender']    = $_POST['email'];
    $mail['name']      = $_POST['name'];
    $mail['recipient'] = $GLOBALS['admin_email'] ?? "twoj_mail@domena.pl";

    // Nagłówki zgodnie z przykładem w zadaniu
    $header  = "From: Formularz kontaktowy <" . $mail['sender'] . ">\n";
    $header .= "Content-Type: text/plain; charset=utf-8\n";
    $header .= "Content-Transfer-Encoding: 8bit\n";
    $header .= "X-Sender: <" . $mail['sender'] . ">\n";
    $header .= "X-Mailer: PHP\n";
    $header .= "X-Priority: 3\n";
    $header .= "Return-Path: <" . $mail['sender'] . ">\n";

    // Wysyłka
    if (mail($mail['recipient'], $mail['subject'], $mail['body'], $header)) {
        return "Wiadomość została wysłana.";
    } else {
        return "Błąd podczas wysyłania wiadomości.";
    }
}



// 3. PrzypomnijHaslo() – wysyłka loginu/hasła na podany e-mail
function PrzypomnijHaslo(): string
{
    // mail, na który wyślemy dane logowania
    $email = trim($_POST['remind_email'] ?? '');

    if ($email === '') {
        return "Podaj adres e-mail, na który mamy wysłać dane logowania.";
    }

    // dane admina z cfg.php
    $login = $GLOBALS['login'] ?? 'admin';
    $pass  = $GLOBALS['pass']  ?? 'haslo';

    // temat i treść wiadomości
    $subject = "Przypomnienie hasła do panelu admina";
    $body    = "Login: $login\nHasło: $pass\n";
    $headers = "Content-Type: text/plain; charset=utf-8\r\n";

    if (@mail($email, $subject, $body, $headers)) {
        return "Dane logowania zostały wysłane na podany adres e-mail.";
    }

    return "Nie udało się wysłać przypomnienia hasła.";
}





