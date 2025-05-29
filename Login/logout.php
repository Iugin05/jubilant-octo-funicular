<?php
// Inizia o ripristina la sessione
session_start();

// Elimina tutte le variabili di sessione
$_SESSION = array();

// Cancella il cookie di sessione
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Distruggi la sessione
session_destroy();

// Reindirizza l'utente alla pagina di login
header("Location: ../index.html");
exit;
?>