<?php
session_start();

// Controlla se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    echo "Devi effettuare il login.";
    exit();
}

include 'db_connection.php'; // File per la connessione al database

// Recupera l'ID utente dalla sessione
$user_id = $_SESSION['user_id'];

// Esegui la query per recuperare la riga del database relativa all'ID utente
$sql = "SELECT * FROM utenti WHERE ID = $user_id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    // Recupera la riga come array associativo
    $row = mysqli_fetch_assoc($result);

    // Mostra tutti i valori verticalmente
    foreach ($row as $column => $value) {
        echo htmlspecialchars($value) . "<br>";
    }
} else {
    echo "Nessun utente trovato con questo ID.";
}

mysqli_close($conn);
?>

<!-- Aggiunta dei pulsanti per logout, index, registrazione e login -->
<div>
    <button onclick="window.location.href='logout.php'">Logout</button>
    <button onclick="window.location.href='index.html'">Home</button>
    <button onclick="window.location.href='register.php'">Registrati</button>
    <button onclick="window.location.href='login.php'">Login</button>
</div>