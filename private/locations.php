<?php

function searchLocations($q, $limit, $offset) {
    global $db;

    $stmt = $db->prepare(
    "SELECT location_id, location_name, country, longitude, latitude
     FROM locations
     WHERE (:q1 IS NULL
            OR location_name LIKE CONCAT('%', :q2, '%')
            OR country LIKE CONCAT('%', :q3, '%'))
     ORDER BY location_name
     LIMIT :limit OFFSET :offset"
);
    if ($q === null) {
        $stmt->bindValue(':q1', null, PDO::PARAM_NULL);
        $stmt->bindValue(':q2', null, PDO::PARAM_NULL);
        $stmt->bindValue(':q3', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':q1', $q, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $q, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $q, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}

function createLocation($location_name, $country, $longitude, $latitude) {
    if(!isset($_SESSION['username'])){
        send_error('Must be logged in to create a location', true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO locations (location_name, country, longitude, latitude)
         VALUES (:location_name, :country, :longitude, :latitude)"
    );
    $stmt->bindValue(':location_name', $location_name);
    $stmt->bindValue(':country', $country);
    $stmt->bindValue(':longitude', $longitude);
    $stmt->bindValue(':latitude', $latitude);
    $stmt->execute();
    $id = $db->lastInsertId();
    $stmt->closeCursor();
    return $id;
}

function getTopRatedLocations($min_reviews, $limit, $offset) {
    global $db;
    $stmt = $db->prepare(
        "SELECT l.location_id,
                l.location_name,
                l.country,
                AVG(r.rating) AS avg_rating,
                COUNT(DISTINCT r.review_id) AS review_count
         FROM locations l
         JOIN trip_locations tl ON tl.location_id = l.location_id
         JOIN reviews r ON r.trip_id = tl.trip_id
         GROUP BY l.location_id, l.location_name, l.country
         HAVING review_count >= :min_reviews
         ORDER BY avg_rating DESC, review_count DESC, l.location_name
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':min_reviews', (int)$min_reviews, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $results;
}
?>