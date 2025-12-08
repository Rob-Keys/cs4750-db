<?php
function addPictureToTrip($trip_id, $image_data, $pic_caption = null, $date_taken = null) {
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO pictures (trip_id, pic_data, pic_caption, date_taken)
         VALUES (:trip_id, :pic_data, :pic_caption, :date_taken)"
    );
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->bindValue(':pic_data', $image_data, PDO::PARAM_LOB);
    $stmt->bindValue(':pic_caption', $pic_caption);
    $stmt->bindValue(':date_taken', $date_taken);
    $stmt->execute();
    $lastId = $db->lastInsertId();
    $stmt->closeCursor();
    return $lastId;
}

function processUploadedImages($trip_id, $files_array) {
    if (!isset($files_array) || empty($files_array['name'][0])) {
        return 0;
    }

    $file_count = count($files_array['name']);
    $uploaded_count = 0;

    for ($i = 0; $i < $file_count; $i++) {
        if ($files_array['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $files_array['tmp_name'][$i];
            $image_data = file_get_contents($tmp_name);

            addPictureToTrip($trip_id, $image_data);
            $uploaded_count++;
        }
    }

    return $uploaded_count;
}

function getPicturesForTrip($trip_id) {
    global $db;
    $stmt = $db->prepare(
        "SELECT picture_id, pic_caption, date_taken
         FROM pictures
         WHERE trip_id = :trip_id
         ORDER BY date_taken, picture_id"
    );
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}

function getPictureData($picture_id) {
    global $db;
    $stmt = $db->prepare(
        "SELECT pic_data, pic_caption, date_taken
         FROM pictures
         WHERE picture_id = :picture_id"
    );
    $stmt->bindValue(':picture_id', $picture_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}

function deletePicture($picture_id) {
    global $db;
    $stmt = $db->prepare(
        "DELETE FROM pictures
         WHERE picture_id = :picture_id"
    );
    $stmt->bindValue(':picture_id', $picture_id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();
}

function deleteAllPicturesForTrip($trip_id) {
    global $db;
    $stmt = $db->prepare(
        "DELETE FROM pictures
         WHERE trip_id = :trip_id"
    );
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();
}
?>
