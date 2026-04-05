<?php
// fetch_schedule_events.php
include('processes/server/conn.php');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get parameters
$semester_id = $_GET['semester_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;

// Check if parameters are provided
if (!$semester_id || !$class_id) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {

    // Query to fetch class meetings
    $sql = "SELECT 
                id,
                date,
                start_time,
                end_time,
                type,
                status
            FROM classes_meetings 
            WHERE class_id = ? 
            ORDER BY date, start_time";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id]);
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for FullCalendar
    $events = [];

    foreach ($meetings as $meeting) {
        // Combine date and 12-hour time from DB into ISO datetime for FullCalendar
        $start_dt = DateTime::createFromFormat('Y-m-d g:i A', $meeting['date'] . ' ' . $meeting['start_time']);
        $end_dt   = DateTime::createFromFormat('Y-m-d g:i A', $meeting['date'] . ' ' . $meeting['end_time']);

        $event = [
            'id' => $meeting['id'],
            'title' => $meeting['type'] . ($meeting['status'] && $meeting['status'] !== 'Scheduled' ? ' (' . $meeting['status'] . ')' : ''),
            'start' => $start_dt ? $start_dt->format('Y-m-d\TH:i:s') : $meeting['date'] . 'T00:00:00',
            'end'   => $end_dt   ? $end_dt->format('Y-m-d\TH:i:s') : $meeting['date'] . 'T00:00:00',
            'color' => getEventColor($meeting['status']),
            'extendedProps' => [
                'type' => $meeting['type'],
                'status' => $meeting['status']
            ]
        ];

        $events[] = $event;
    }

    echo json_encode($events);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

function getEventColor($status) {
    $colors = [
        'Scheduled' => '#28a745',
        'Ongoing' => '#007bff', 
        'Ended' => '#6c757d',
        'Finished' => '#dc3545',
        'Rescheduled' => '#ffc107',
        'Cancelled' => '#dc3545'
    ];
    return $colors[$status] ?? '#dc3545';
}
?>
