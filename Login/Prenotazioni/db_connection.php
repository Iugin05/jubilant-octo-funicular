<?php
// Parametri di connessione al database
$servername = "31.11.39.181";
$username = "Sql1822618";
$password = "xanwum-nahtyc-7temWu";
$dbname = "Sql1822618_1";

// Crea la connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Nota: Non c'è bisogno di chiudere la connessione qui,
// verrà chiusa automaticamente quando lo script termina.
?>