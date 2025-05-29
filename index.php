<?php
	session_start();
	
	if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
	    header("Location: Login/Prenotazioni/index.php");
	    exit;
	}
	
	$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null';
	
	// Aggiungi il controllo per l'id dell'utente
	if ($userId == 1) {
	    header("Location: view_prenotations.php");
	    exit;
	}
	?>
<!DOCTYPE html>
<html lang="it">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Calendario Interattivo</title>
		<link rel="stylesheet" href="style.css">
		<link rel="stylesheet" href="calendar.css">
		<link href="img/favicon.ico" rel="icon">
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
		<link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
		<link href="lib/animate/animate.min.css" rel="stylesheet">
		<link href="css/bootstrap.min.css" rel="stylesheet">
		<link href="css/style.css" rel="stylesheet">
		<style>
			.container {
			display: flex;
			flex-wrap: wrap;
			justify-content: space-between;
			gap: 40px;
			}
			.left {
			flex: 1 1 55%;
			padding: 20px;
			box-sizing: border-box;
			}
			.right {
			flex: 1 1 40%;
			padding: 20px;
			box-sizing: border-box;
			display: flex;
			align-items: center; /* centra verticalmente il calendario */
			}
			.calendar-outer {
			background: #fff;
			border-radius: 10px;
			padding: 20px;
			width: 100%;
			box-sizing: border-box;
			}
			.calendar-inner {
			width: 100%;
			}
			.right > div + div {
			display: none; /* Elimina eventuali contenitori vuoti sotto */
			}
			table {
			width: 100%;
			text-align: center;
			border-collapse: collapse;
			}
			table th, table td {
			padding: 10px;
			}
			@media (max-width: 768px) {
			.container {
			flex-direction: column;
			align-items: stretch;
			}
			.left, .right {
			flex: 1 1 100%;
			padding: 10px 0;
			display: block;
			}
			}
			@media (min-width: 992px) {
			.container {
			align-items: center; /* centra verticalmente le due colonne su desktop */
			}
			}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="left">
				<div class="col-lg-7">
					<div class="section-title position-relative pb-3 mb-5">
						<h5 class="fw-bold text-primary text-uppercase">prova il nuovo sistema</h5>
						<h1 class="mb-0">Prenotazione Guide</h1>
					</div>
					<div class="row gx-3">
						<div class="col-sm-6 wow zoomIn" data-wow-delay="0.2s">
							<h5 class="mb-4"><i class="fa fa-reply text-primary me-3"></i>cancella entro 36 ore</h5>
						</div>
						<div class="col-sm-6 wow zoomIn" data-wow-delay="0.4s">
							<h5 class="mb-4"><img src="crediti.svg" alt="Crediti" class="me-3" style="width: 1em; height: 1em;"></i>Crediti rimasti:</h5>
						</div>
					</div>
					<p class="mb-4"><strong>Prenota le tue guide in autonomia!</strong><br><br>Da oggi prenotare le tue guide è più semplice che mai: niente più attese in segreteria o telefonate all’ultimo minuto. <br> Con il nostro <strong>nuovo sistema online</strong> puoi gestire tutto in autonomia, scegliendo comodamente giorno e ora direttamente dal tuo smartphone o dal computer. <br><br> Per prenotare ti serviranno dei <strong>token</strong>, ovvero crediti digitali che puoi acquistare direttamente in segreteria. Ogni token corrisponde a una guida. Una volta attivati, puoi utilizzarli sul portale per visualizzare le disponibilità e bloccare il turno che preferisci. <br><br> <strong>Attenzione:</strong> le guide possono essere prenotate <strong>fino a 36 ore prima</strong> dell’orario di inizio. Oltre questo limite, il sistema non accetta prenotazioni. <br><br> Facile, comodo e veloce.</p>
					<div class="d-flex align-items-center mt-2 wow zoomIn" data-wow-delay="0.6s">
						<div class="bg-primary d-flex align-items-center justify-content-center rounded" style="width: 60px; height: 60px;">
							<i class="fa fa-phone-alt text-white"></i>
						</div>
						<div class="ps-4">
							<h5 class="mb-2">Per qualunque problema chiamaci</h5>
							<h4 class="text-primary mb-0">081 5030693</h4>
						</div>
					</div>
				</div>
			</div>
			<div class="right">
				<div class="calendar-container">
					<!-- Header del calendario -->
					<div class="calendar-header">
						<button class="nav-btn" id="prevMonth">‹</button>
						<h2 class="month-year" id="monthYear"></h2>
						<button class="nav-btn" id="nextMonth">›</button>
					</div>
					<!-- Giorni della settimana -->
					<div class="weekdays">
						<div class="weekday">Lun</div>
						<div class="weekday">Mar</div>
						<div class="weekday">Mer</div>
						<div class="weekday">Gio</div>
						<div class="weekday">Ven</div>
						<div class="weekday">Sab</div>
						<div class="weekday">Dom</div>
					</div>
					<!-- Griglia dei giorni -->
					<div class="calendar-grid" id="calendarGrid">
						<!-- I giorni verranno generati dinamicamente -->
					</div>
					<!-- Sezione turni -->
					<div class="time-slots-container" id="timeSlotsContainer" style="display: none;">
						<h3 id="selectedDate"></h3>
						<div class="time-slots" id="timeSlots">
							<!-- I turni verranno caricati dinamicamente -->
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="script.js"></script>
		<script src="calendar.js"></script>
	</body>
</html>
