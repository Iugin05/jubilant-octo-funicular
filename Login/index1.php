<?php
session_start();

// Connessione al database
include 'db_connection.php';

// Verifica se l'utente è già loggato
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: ../");
    exit();
}

// Variabile per gli errori
$login_error = '';
$registration_error = '';
$registration_success = '';

// Se il form di login è stato inviato
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);


// Se il form di registrazione è stato inviato
if (isset($_POST['signup'])) {
    $nome = mysqli_real_escape_string($conn, $_POST['name']);
    $cognome = mysqli_real_escape_string($conn, $_POST['surname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
?>