<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $connection = auth_database();
    $result = $connection->query(
        "SELECT e.event_code, e.title, c.category_name, e.event_date, e.event_time,
                e.venue, e.organizer, e.seat_capacity, e.description, e.poster_path
         FROM events AS e
         INNER JOIN event_categories AS c ON c.category_id = e.category_id
         WHERE e.status = 'Published'
         ORDER BY e.event_date, e.event_time, e.title"
    );

    $events = [];
    while ($event = $result->fetch_assoc()) {
        $events[] = [
            'id' => $event['event_code'],
            'title' => $event['title'],
            'category' => $event['category_name'],
            'date' => $event['event_date'],
            'time' => date('h:i A', strtotime($event['event_time'])),
            'venue' => $event['venue'],
            'organizer' => $event['organizer'],
            'seats' => $event['seat_capacity'],
            'description' => $event['description'],
            'poster' => $event['poster_path'],
        ];
    }
    $connection->close();

    echo json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (mysqli_sql_exception $exception) {
    error_log('StudentHub public event listing failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Event listings are temporarily unavailable.'], JSON_UNESCAPED_SLASHES);
} catch (JsonException $exception) {
    error_log('StudentHub event response encoding failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Event listings are temporarily unavailable.'], JSON_UNESCAPED_SLASHES);
}
