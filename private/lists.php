<?php

function getListsForUser($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT l.list_id,
                l.list_title,
                li.list_item_id,
                li.list_index,
                t.trip_id,
                t.trip_title
         FROM list l
         LEFT JOIN list_item li ON li.list_id = l.list_id
         LEFT JOIN trips t      ON t.trip_id = li.trip_id
         WHERE l.username = :username
         ORDER BY l.list_id, li.list_index"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $rows; 
}

function createList($list_title, $username) {
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO list (list_title, username)
         VALUES (:list_title, :username)"
    );
    $stmt->bindValue(':list_title', $list_title);
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $lastId = $db->lastInsertId();
    $stmt->closeCursor();
    return $lastId;
}


function addTripToList($list_index, $list_id, $trip_id) {
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO list_item (list_index, list_id, trip_id)
         VALUES (:list_index, :list_id, :trip_id)"
    );
    $stmt->bindValue(':list_index', $list_index, PDO::PARAM_INT);
    $stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();
}


function deleteListItem($list_item_id, $owner_username) {
    global $db;
    $stmt = $db->prepare(
        "DELETE li
         FROM list_item li
         JOIN list l ON l.list_id = li.list_id
         WHERE li.list_item_id = :list_item_id
           AND l.username = :owner_username"
    );
    $stmt->bindValue(':list_item_id', $list_item_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_username', $owner_username);
    $stmt->execute();
    $stmt->closeCursor();
}


function deleteList($list_id, $owner_username) {
    global $db;
    $stmt = $db->prepare(
        "DELETE FROM list
         WHERE list_id = :list_id
           AND username = :owner_username"
    );
    $stmt->bindValue(':list_id', $list_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_username', $owner_username);
    $stmt->execute();
    $stmt->closeCursor();
}
?>