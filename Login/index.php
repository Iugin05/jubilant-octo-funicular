<?php
session_start();

// Connessione al database
include 'db_connection.php';

// Verifica se l'utente è già loggato
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location:verify.php");
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

    // Cerca l'utente nel database
    $sql = "SELECT ID, password FROM utenti WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Verifica la password
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['ID'];
            $_SESSION['logged_in'] = true;
            header("Location: " . ($row['ID'] == 4 ? "view_prenotations.php" : "../Prenotazioni"));
            exit();
        } else {
            $login_error = "Password errata.";
        }
    } else {
        $login_error = "Nessun utente trovato con questa email.";
    }
}

// Se il form di registrazione è stato inviato
if (isset($_POST['signup'])) {
    $nome = mysqli_real_escape_string($conn, $_POST['name']);
    $cognome = mysqli_real_escape_string($conn, $_POST['surname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Verifica se l'email è già registrata
    $checkEmailQuery = "SELECT * FROM utenti WHERE email = '$email'";
    $checkEmailResult = mysqli_query($conn, $checkEmailQuery);

    if (mysqli_num_rows($checkEmailResult) > 0) {
        $registration_error = "L'email è già registrata. Prova con un'altra email.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO utenti (nome, cognome, email, password, Data_creazione) 
                VALUES ('$nome', '$cognome', '$email', '$hashed_password', CURDATE())";

        if (mysqli_query($conn, $sql)) {
            // Autenticazione automatica dell'utente dopo la registrazione
            $_SESSION['user_id'] = mysqli_insert_id($conn);
            $_SESSION['logged_in'] = true;
            header("Location: ../../Prenotazioni");
            exit();
        } else {
            $registration_error = "Errore durante la registrazione.";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login + Sign Up</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href='https://fonts.googleapis.com/css?family=Roboto:300,400,600' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="back">
  <canvas id="canvas" class="canvas-back"></canvas>
  <div class="backRight"></div>
  <div class="backLeft"></div>
</div>

<div id="slideBox">
  <div class="topLayer">
    <div class="left">
      <div class="content">
        <h2>Registrati</h2>
        <!-- Mostra messaggi di errore o successo -->
        <?php if (!empty($registration_error)): ?>
            <p class="error"><?php echo $registration_error; ?></p>
        <?php elseif (!empty($registration_success)): ?>
            <p class="success"><?php echo $registration_success; ?></p>
        <?php endif; ?>
        
        <form id="form-signup" method="post">
          <div class="form-element form-stack">
            <label for="name-signup" class="form-label">Nome</label>
            <input id="name-signup" type="text" name="name" required>
          </div>
          <div class="form-element form-stack">
            <label for="surname-signup" class="form-label">Cognome</label>
            <input id="surname-signup" type="text" name="surname" required>
          </div>
          <div class="form-element form-stack">
            <label for="email" class="form-label">E-mail</label>
            <input id="email" type="email" name="email" required>
          </div>
          <div class="form-element form-stack">
            <label for="password-signup" class="form-label">Password</label>
            <input id="password-signup" type="password" name="password" required>
          </div>
          <div class="form-element form-checkbox">
            <input id="confirm-terms" type="checkbox" name="confirm" value="yes" class="checkbox" required>
            <label for="confirm-terms">Accetto <a href="#">Termini</a> e <a href="#">Condizioni</a></label>
          </div>
          <div class="form-element form-submit">
            <button id="signUp" class="signup" type="submit" name="signup">Registrati</button>
            <button id="goLeft" class="signup off">Oppure Accedi</button> 
          </div>
        </form>
      </div>
    </div>
    
    <div class="right">
      <div class="content">
        <h2>Accedi</h2>
        <!-- Mostra messaggi di errore -->
        <?php if (!empty($login_error)): ?>
            <p class="error"><?php echo $login_error; ?></p>
        <?php endif; ?>

        <form id="form-login" method="post">
          <div class="form-element form-stack">
            <label for="username-login" class="form-label">e-mail</label>
            <input id="username-login" type="text" name="username" required>
          </div>
          <div class="form-element form-stack">
            <label for="password-login" class="form-label">Password</label>
            <input id="password-login" type="password" name="password" required>
          </div>
          <div class="form-element form-submit">
            <button id="logIn" class="login" type="submit" name="login">Accedi</button>
            <button id="goRight" class="login off">Oppure registrati</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/paper.js/0.11.3/paper-full.min.js'></script>
<script src="script.js"></script>

</body>
</html>