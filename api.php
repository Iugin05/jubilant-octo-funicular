<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Utente non autenticato']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Verifica la connessione MySQLi
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore di connessione al database: ' . $conn->connect_error]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'getAvailableDates':
        getAvailableDates($conn);
        break;
    
    case 'getTimeSlots':
        getTimeSlots($conn, $user_id);
        break;
    
    case 'bookSlot':
        $input = json_decode(file_get_contents('php://input'), true);
        bookSlot($conn, $user_id, $input);
        break;
    
    case 'cancelBooking':
        $input = json_decode(file_get_contents('php://input'), true);
        cancelBooking($conn, $user_id, $input);
        break;
    
    case 'getUserCredits':
        getUserCredits($conn, $user_id);
        break;
    
    case 'getMonthBookingStatus':
        getMonthBookingStatus($conn, $user_id);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Azione non valida']);
}

function getMonthBookingStatus($conn, $user_id) {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('n');
    
    // Calcola la data limite (30 giorni da oggi)
    $today = date('Y-m-d');
    $limit_date = date('Y-m-d', strtotime('+30 days'));
    
    try {
        // Ottieni la struttura della tabella per conoscere tutte le colonne dei turni
        $result = $conn->query("DESCRIBE prenotazioni_sede_1");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Rimuovi la colonna 'data' dall'array
        $timeColumns = array_filter($columns, function($col) {
            return $col !== 'data';
        });
        
        if (empty($timeColumns)) {
            echo json_encode(['bookingStatus' => []]);
            return;
        }
        
        // Ottieni tutti i dati per il mese richiesto
        $columnsList = implode(', ', array_map(function($col) use ($conn) {
            return '`' . $conn->real_escape_string($col) . '`';
        }, $timeColumns));
        
        $stmt = $conn->prepare("
            SELECT data, $columnsList 
            FROM prenotazioni_sede_1 
            WHERE YEAR(data) = ? AND MONTH(data) = ? 
            AND data >= ? AND data <= ?
            ORDER BY data
        ");
        
        $stmt->bind_param('iiss', $year, $month, $today, $limit_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookingStatus = [];
        
        while ($row = $result->fetch_assoc()) {
            $date = $row['data'];
            $hasMyBooking = false;
            $hasAvailableSlots = false;
            $allOccupied = true;
            
            foreach ($timeColumns as $column) {
                $value = $row[$column];
                
                if ($value == $user_id) {
                    $hasMyBooking = true;
                }
                
                if ($value === null) {
                    $hasAvailableSlots = true;
                    $allOccupied = false;
                }
            }
            
            // Se non ho prenotazioni, controlla se ci sono slot disponibili
            if (!$hasMyBooking && $hasAvailableSlots) {
                $allOccupied = false;
            }
            
            $bookingStatus[$date] = [
                'hasMyBooking' => $hasMyBooking,
                'hasAvailableSlots' => $hasAvailableSlots && !$hasMyBooking,
                'allOccupied' => $allOccupied && !$hasMyBooking
            ];
        }
        
        $stmt->close();
        echo json_encode(['bookingStatus' => $bookingStatus]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Errore nel caricamento dello stato prenotazioni: ' . $e->getMessage()]);
    }
}

function getUserCredits($conn, $user_id) {
    try {
        // Ottieni i crediti dell'utente
        $stmt = $conn->prepare("SELECT crediti FROM utenti WHERE ID = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            echo json_encode(['error' => 'Utente non trovato']);
            return;
        }
        
        $total_credits = $user['crediti'];
        
        // Calcola le prenotazioni già effettuate
        $structure = $conn->query("DESCRIBE prenotazioni_sede_1");
        $turni = [];
        while ($r = $structure->fetch_assoc()) {
            if ($r['Field'] !== 'data') $turni[] = $r['Field'];
        }
        
        if (empty($turni)) {
            echo json_encode(['total_credits' => $total_credits, 'used_credits' => 0, 'available_credits' => $total_credits]);
            return;
        }
        
        $q = "SELECT COUNT(*) as count FROM prenotazioni_sede_1 WHERE " . 
             implode(" = ? OR ", array_map(fn($c) => "`$c`", $turni)) . " = ?";
        
        $stmt = $conn->prepare($q);
        $params = array_fill(0, count($turni), $user_id);
        $stmt->bind_param(str_repeat('i', count($params)), ...$params);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $used_credits = $res['count'];
        
        $available_credits = $total_credits - $used_credits;
        
        echo json_encode([
            'total_credits' => $total_credits,
            'used_credits' => $used_credits,
            'available_credits' => $available_credits
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Errore nel calcolo dei crediti: ' . $e->getMessage()]);
    }
}

function getAvailableDates($conn) {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('n');
    
    // Calcola la data limite (30 giorni da oggi)
    $today = date('Y-m-d');
    $limit_date = date('Y-m-d', strtotime('+30 days'));
    
    // Ottieni tutte le date per il mese richiesto
    $stmt = $conn->prepare("
        SELECT data 
        FROM prenotazioni_sede_1 
        WHERE YEAR(data) = ? AND MONTH(data) = ? 
        AND data >= ? AND data <= ?
        ORDER BY data
    ");
    
    $stmt->bind_param('iiss', $year, $month, $today, $limit_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['data'];
    }
    
    $stmt->close();
    echo json_encode(['dates' => $dates]);
}

function getTimeSlots($conn, $user_id) {
    $date = $_GET['date'] ?? '';
    
    if (empty($date)) {
        echo json_encode(['error' => 'Data non specificata']);
        return;
    }
    
    // Verifica che la data sia entro 30 giorni
    $today = date('Y-m-d');
    $limit_date = date('Y-m-d', strtotime('+30 days'));
    
    if ($date < $today || $date > $limit_date) {
        echo json_encode(['error' => 'Data non valida o fuori dal limite di 30 giorni']);
        return;
    }
    
    // Ottieni la struttura della tabella per conoscere tutte le colonne dei turni
    $result = $conn->query("DESCRIBE prenotazioni_sede_1");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Rimuovi la colonna 'data' dall'array
    $timeColumns = array_filter($columns, function($col) {
        return $col !== 'data';
    });
    
    // Ottieni i dati per la data specificata
    $columnsList = implode(', ', array_map(function($col) use ($conn) {
        return '`' . $conn->real_escape_string($col) . '`';
    }, $timeColumns));
    
    $stmt = $conn->prepare("SELECT $columnsList FROM prenotazioni_sede_1 WHERE data = ?");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $slots = [];
    
    if ($row) {
        foreach ($timeColumns as $column) {
            $value = $row[$column];
            $status = 'available';
            
            if ($value !== null) {
                if ($value == $user_id) {
                    $status = 'booked_by_me';
                } else {
                    $status = 'occupied';
                }
            }
            
            // Converti il nome della colonna in un formato più leggibile
            $displayName = formatTimeSlotName($column);
            
            $slots[] = [
                'column' => $column,
                'name' => $displayName,
                'status' => $status
            ];
        }
    }
    
    $stmt->close();
    echo json_encode(['slots' => $slots]);
}

function bookSlot($conn, $user_id, $input) {
    try {
        $date = $input['date'] ?? '';
        $column = $input['column'] ?? '';
        
        if (empty($date) || empty($column)) {
            echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
            return;
        }
        
        // Verifica che la data sia entro 30 giorni
        $today = date('Y-m-d');
        $limit_date = date('Y-m-d', strtotime('+30 days'));
        
        if ($date < $today || $date > $limit_date) {
            echo json_encode(['success' => false, 'message' => 'Non puoi prenotare guide oltre 30 giorni da oggi']);
            return;
        }
        
        // Verifica i crediti disponibili
        $stmt = $conn->prepare("SELECT crediti FROM utenti WHERE ID = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
            return;
        }
        
        // Calcola le prenotazioni già effettuate
        $structure = $conn->query("DESCRIBE prenotazioni_sede_1");
        $turni = [];
        while ($r = $structure->fetch_assoc()) {
            if ($r['Field'] !== 'data') $turni[] = $r['Field'];
        }
        
        $used_credits = 0;
        if (!empty($turni)) {
            $q = "SELECT COUNT(*) as count FROM prenotazioni_sede_1 WHERE " . 
                 implode(" = ? OR ", array_map(fn($c) => "`$c`", $turni)) . " = ?";
            
            $stmt = $conn->prepare($q);
            $params = array_fill(0, count($turni), $user_id);
            $stmt->bind_param(str_repeat('i', count($params)), ...$params);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $used_credits = $res['count'];
        }
        
        $available_credits = $user['crediti'] - $used_credits;
        
        if ($available_credits <= 0) {
            echo json_encode(['success' => false, 'message' => 'Non hai crediti sufficienti per prenotare']);
            return;
        }
        
        // Verifica che non abbia già più di 2 guide nello stesso giorno
        $columnsList = implode(', ', array_map(function($col) use ($conn) {
            return '`' . $conn->real_escape_string($col) . '`';
        }, $turni));
        
        $stmt = $conn->prepare("SELECT $columnsList FROM prenotazioni_sede_1 WHERE data = ?");
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $dayBookings = $result->fetch_assoc();
        
        $bookingsInDay = 0;
        if ($dayBookings) {
            foreach ($turni as $turno) {
                if ($dayBookings[$turno] == $user_id) {
                    $bookingsInDay++;
                }
            }
        }
        
        if ($bookingsInDay >= 2) {
            echo json_encode(['success' => false, 'message' => 'Non puoi prenotare più di 2 guide nello stesso giorno']);
            return;
        }
        
        // Verifica che la colonna esista
        $result = $conn->query("DESCRIBE prenotazioni_sede_1");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        if (!in_array($column, $columns)) {
            echo json_encode(['success' => false, 'message' => 'Turno non valido']);
            return;
        }
        
        // Verifica che il turno sia disponibile
        $column_escaped = $conn->real_escape_string($column);
        $stmt = $conn->prepare("SELECT `$column_escaped` FROM prenotazioni_sede_1 WHERE data = ?");
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Data non disponibile']);
            return;
        }
        
        if ($row[$column] !== null) {
            echo json_encode(['success' => false, 'message' => 'Turno già prenotato']);
            return;
        }
        
        // Prenota il turno
        $stmt = $conn->prepare("UPDATE prenotazioni_sede_1 SET `$column_escaped` = ? WHERE data = ?");
        $stmt->bind_param('is', $user_id, $date);
        $success = $stmt->execute();
        
        $stmt->close();
        echo json_encode(['success' => $success, 'message' => $success ? 'Prenotazione effettuata con successo' : 'Errore durante la prenotazione']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Errore durante la prenotazione: ' . $e->getMessage()]);
    }
}

function cancelBooking($conn, $user_id, $input) {
    try {
        $date = $input['date'] ?? '';
        $column = $input['column'] ?? '';
        
        if (empty($date) || empty($column)) {
            echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
            return;
        }
        
        // Verifica che non si stia cancellando entro 36 ore
        $booking_datetime = new DateTime($date . ' 09:00:00'); // Assumendo inizio alle 9:00
        $now = new DateTime();
        $hours_diff = ($booking_datetime->getTimestamp() - $now->getTimestamp()) / 3600;
        
        if ($hours_diff <= 36) {
            echo json_encode(['success' => false, 'message' => 'Non puoi cancellare una prenotazione entro 36 ore dall\'inizio']);
            return;
        }
        
        // Verifica che la prenotazione appartenga all'utente
        $column_escaped = $conn->real_escape_string($column);
        $stmt = $conn->prepare("SELECT `$column_escaped` FROM prenotazioni_sede_1 WHERE data = ?");
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row || $row[$column] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Prenotazione non trovata o non autorizzata']);
            return;
        }
        
        // Cancella la prenotazione
        $stmt = $conn->prepare("UPDATE prenotazioni_sede_1 SET `$column_escaped` = NULL WHERE data = ?");
        $stmt->bind_param('s', $date);
        $success = $stmt->execute();
        
        $stmt->close();
        echo json_encode(['success' => $success, 'message' => $success ? 'Prenotazione cancellata con successo' : 'Errore durante la cancellazione']);
        
    } catch (Exception $e) {
        echo json_decode(['success' => false, 'message' => 'Errore durante la cancellazione: ' . $e->getMessage()]);
    }
}

function formatTimeSlotName($columnName) {
    // Converti nomi come "turno_1" in formato più leggibile
    // Puoi personalizzare questa funzione in base alle tue convenzioni di naming
    
    if (preg_match('/^turno_(\d+)$/', $columnName, $matches)) {
        // Se il formato è turno_1, turno_2, etc., potresti voler mappare a orari specifici
        return $columnName; // Per ora restituisce il nome originale
    }
    
    // Se il nome della colonna è già in formato orario (es. "15:00-15:45")
    return str_replace('_', ' ', $columnName);
}
?>
