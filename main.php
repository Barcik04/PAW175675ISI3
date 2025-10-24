<?php
session_start(); // ← musi być na samym początku!

$nr_indeksu = '175675';
$nrGrupy = 'ISI3';
echo 'Igor Barcikowski ' . $nr_indeksu . ' grupa ' . $nrGrupy . '<br><br>';

// a) include(), require_once()
echo 'a) include(), require_once()<br>';
include 'test.php';
require_once 'test.php';

// b) if, else, elseif, switch
echo '<br>b) if, else, elseif, switch<br>';
$x = 5;
if ($x > 5) echo 'większe<br>';
elseif ($x == 5) echo 'równe<br>';
else echo 'mniejsze<br>';

$color = 'red';
switch ($color) {
    case 'red': echo 'czerwony<br>'; break;
    default: echo 'inny<br>';
}

// c) while(), for()
echo '<br>c) while(), for()<br>';
$i = 1;
while ($i <= 3) { echo $i.' '; $i++; }
echo '<br>';
for ($j = 1; $j <= 3; $j++) echo $j.' ';
echo '<br>';

// d) $_GET, $_POST, $_SESSION
echo '<br>d) $_GET, $_POST, $_SESSION<br>';
$_SESSION['x'] = 'sesja';
echo 'GET: '.($_GET['a'] ?? 'brak').' ';
echo 'POST: '.($_POST['b'] ?? 'brak').' ';
echo 'SESSION: '.$_SESSION['x'];
?>
