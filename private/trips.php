<?php
function createTrip($trip_title, $start_date, $end_date, $username) {
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO trips (trip_title, start_date, end_date, username)
         VALUES (:trip_title, :start_date, :end_date, :username)"
    );
    $stmt->bindValue(':trip_title', $trip_title);
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $lastId = $db->lastInsertId();
    $stmt->closeCursor();
    return $lastId;
}


function addTripStop($trip_id, $location_id, $transit_id) {
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO trip_locations (trip_id, location_id, transit_id)
         VALUES (:trip_id, :location_id, :transit_id)"
    );
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->bindValue(':location_id', $location_id, PDO::PARAM_INT);
    $stmt->bindValue(':transit_id', $transit_id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();
}


function getTripsForUser($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT t.trip_id,
                t.trip_title,
                t.start_date,
                t.end_date,
                GROUP_CONCAT(DISTINCT l.location_name
                             ORDER BY tl.trip_location_id
                             SEPARATOR ' -> ') AS itinerary
         FROM trips t
         LEFT JOIN trip_locations tl ON tl.trip_id = t.trip_id
         LEFT JOIN locations l ON l.location_id = tl.location_id
         WHERE t.username = :username
         GROUP BY t.trip_id, t.trip_title, t.start_date, t.end_date
         ORDER BY t.start_date DESC, t.trip_id DESC"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}


function getTripDetails($trip_id) {
    global $db;
    $stmt = $db->prepare(
        "SELECT t.trip_id,
                t.trip_title,
                t.start_date,
                t.end_date,
                t.username,
                tl.trip_location_id,
                tl.location_id,
                l.location_name,
                l.country,
                tl.transit_id,
                tr.transit_type,
                tr.transit_length
         FROM trips t
         LEFT JOIN trip_locations tl ON tl.trip_id = t.trip_id
         LEFT JOIN locations l ON l.location_id = tl.location_id
         LEFT JOIN transportation tr ON tr.transit_id = tl.transit_id
         WHERE t.trip_id = :trip_id
         ORDER BY tl.trip_location_id"
    );
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}


function updateTrip($trip_id, $owner_username, $trip_title, $start_date, $end_date) {
    global $db;
    $stmt = $db->prepare(
        "UPDATE trips
         SET trip_title = :trip_title,
             start_date = :start_date,
             end_date   = :end_date
         WHERE trip_id = :trip_id AND username = :owner_username"
    );
    $stmt->bindValue(':trip_title', $trip_title);
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_username', $owner_username);
    $stmt->execute();
    $stmt->closeCursor();
}


function deleteTripStop($trip_location_id) {
    global $db;
    $stmt = $db->prepare(
        "DELETE FROM trip_locations
         WHERE trip_location_id = :trip_location_id"
    );
    $stmt->bindValue(':trip_location_id', $trip_location_id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();
}


function deleteTrip($trip_id, $owner_username) {
    global $db;
    $stmt = $db->prepare(
        "DELETE FROM trips
         WHERE trip_id = :trip_id
           AND username = :owner_username"
    );
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_username', $owner_username);
    $stmt->execute();
    $stmt->closeCursor();
}
?>