<?php
function createReview($rating, $written_review, $trip_id) {
    $date_written = date('Y-m-d H:i:s');
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO reviews (rating, written_review, date_written, trip_id)
         VALUES (:rating, :written_review, :date_written, :trip_id)"
    );
    $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindValue(':written_review', $written_review);
    $stmt->bindValue(':date_written', $date_written);
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->execute();
    $lastId = $db->lastInsertId();
    $stmt->closeCursor();
    return $lastId;
}


function updateReview($review_id, $owner_username, $rating, $written_review) {
    if(!$owner_username) {
        send_error("Not logged in", true);
        exit();
    }
    if($review_id === null || $rating === null) {
        send_error("Missing review_id or rating", true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "UPDATE reviews r
         JOIN trips t ON t.trip_id = r.trip_id
         SET r.rating = :rating,
             r.written_review = :written_review
         WHERE r.review_id = :review_id
           AND t.username = :owner_username"
    );
    $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindValue(':written_review', $written_review);
    $stmt->bindValue(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_username', $owner_username);
    $stmt->execute();
    $stmt->closeCursor();
}


function deleteReview($review_id, $owner_username) {
    if(!$owner_username) {
        send_error("Not logged in", true);
        exit();
    }
    if($review_id === null) {
        send_error("Missing review_id", true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "DELETE r
         FROM reviews r
         JOIN trips t ON t.trip_id = r.trip_id
         WHERE r.review_id = :review_id
           AND t.username = :owner_username"
    );
    $stmt->bindValue(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_username', $owner_username);
    $stmt->execute();
    $stmt->closeCursor();
}




function addComment($comment_text, $date_written, $commenter_username, $review_id) {
    if(!$commenter_username) {
        send_error("Not logged in", true);
        exit();
    }
    if(!$review_id || trim($comment_text) === '') {
        send_error("Missing review_id or comment_text", true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO comments (comment_text, date_written, commenter_username, review_id)
         VALUES (:comment_text, :date_written, :commenter_username, :review_id)"
    );
    $stmt->bindValue(':comment_text', $comment_text);
    $stmt->bindValue(':date_written', $date_written);
    $stmt->bindValue(':commenter_username', $commenter_username);
    $stmt->bindValue(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->execute();
    $lastId = $db->lastInsertId();
    $stmt->closeCursor();
    return $lastId;
}


function getCommentsForReview($review_id, $limit, $offset) {
    if(!$review_id) {
        send_error("Missing review_id", true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "SELECT c.comment_id,
                c.comment_text,
                c.date_written,
                c.commenter_username
         FROM comments c
         WHERE c.review_id = :review_id
         ORDER BY c.date_written ASC, c.comment_id ASC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}


function updateComment($comment_id, $commenter_username, $comment_text) {
    if(!$commenter_username) {
        send_error("Not logged in", true);
        exit();
    }
    if(!$comment_id || trim($comment_text) === '') {
        send_error("Missing comment_id or comment_text", true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "UPDATE comments
         SET comment_text = :comment_text
         WHERE comment_id = :comment_id
           AND commenter_username = :commenter_username"
    );
    $stmt->bindValue(':comment_text', $comment_text);
    $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
    $stmt->bindValue(':commenter_username', $commenter_username);
    $stmt->execute();
    $stmt->closeCursor();
}


function deleteComment($comment_id, $commenter_username) {
    if(!$commenter_username) {
        send_error("Not logged in", true);
        exit();
    }
    if(!$comment_id) {
        send_error("Missing comment_id", true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "DELETE FROM comments
         WHERE comment_id = :comment_id
           AND commenter_username = :commenter_username"
    );
    $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
    $stmt->bindValue(':commenter_username', $commenter_username);
    $stmt->execute();
    $stmt->closeCursor();
}

function getReviewsForUser($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT r.review_id,
                r.rating,
                r.written_review,
                r.date_written,
                t.trip_id,
                t.trip_title,
                GROUP_CONCAT(DISTINCT l.location_name
                             ORDER BY tl.trip_location_id
                             SEPARATOR ' -> ') AS itinerary
         FROM reviews r
         JOIN trips t ON t.trip_id = r.trip_id
         LEFT JOIN trip_locations tl ON tl.trip_id = t.trip_id
         LEFT JOIN locations l ON l.location_id = tl.location_id
         WHERE t.username = :username
         GROUP BY r.review_id, r.rating, r.written_review,
                  r.date_written, t.trip_id, t.trip_title
         ORDER BY r.date_written DESC"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $results;
}

function getReviewByTripId($trip_id) {
    global $db;
    $stmt = $db->prepare(
        "SELECT r.review_id,
                r.rating,
                r.written_review,
                r.date_written,
                r.trip_id
         FROM reviews r
         WHERE r.trip_id = :trip_id
         LIMIT 1"
    );
    $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}

function getHomeFeed($viewer_username, $limit, $offset) {
    if(!$viewer_username) {
        send_error("Not logged in", true);
        exit();
    }
    global $db;

    // Try using the stored procedure
    try {
        $stmt = $db->prepare("CALL sp_get_user_feed(:username, :limit)");
        $stmt->bindValue(':username', $viewer_username);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();


        if ($results !== false) {
            return $results;
        }
    } catch (PDOException $e) {
    }

    // Fallback
    $stmt = $db->prepare(
        "SELECT r.review_id,
                r.rating,
                r.written_review,
                r.date_written,
                t.trip_id,
                t.trip_title,
                t.start_date,
                t.end_date,
                u.username AS author,
                GROUP_CONCAT(DISTINCT l.location_name
                             ORDER BY tl.trip_location_id
                             SEPARATOR ' -> ') AS itinerary
         FROM following f
         JOIN users u  ON u.username = f.followee_username
         JOIN trips t  ON t.username = u.username
         JOIN reviews r ON r.trip_id = t.trip_id
         LEFT JOIN trip_locations tl ON tl.trip_id = t.trip_id
         LEFT JOIN locations l       ON l.location_id = tl.location_id
         WHERE f.follower_username = :viewer
         GROUP BY r.review_id, r.rating, r.written_review,
                  r.date_written, t.trip_id, t.trip_title,
                  t.start_date, t.end_date, u.username
         ORDER BY r.date_written DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':viewer', $viewer_username);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $results;
}



function searchReviews($q, $limit, $offset) {
    global $db;

    try {
        $stmt = $db->prepare(
            "SELECT r.review_id,
                    r.rating,
                    r.written_review,
                    r.date_written,
                    t.trip_id,
                    t.trip_title,
                    t.username AS author,
                    GROUP_CONCAT(DISTINCT l.location_name
                                 ORDER BY tl.trip_location_id
                                 SEPARATOR ' -> ') AS itinerary
             FROM reviews r
             JOIN trips t ON t.trip_id = r.trip_id
             LEFT JOIN trip_locations tl ON tl.trip_id = t.trip_id
             LEFT JOIN locations l ON l.location_id = tl.location_id
             WHERE (:q1 IS NULL
               OR r.written_review LIKE CONCAT('%', :q2, '%')
               OR t.trip_title     LIKE CONCAT('%', :q3, '%')
               OR l.location_name  LIKE CONCAT('%', :q4, '%'))
             GROUP BY r.review_id, r.rating, r.written_review,
                      r.date_written, t.trip_id, t.trip_title, t.username
             ORDER BY r.date_written DESC
             LIMIT :limit OFFSET :offset"
        );
        if ($q === null || $q === '') {
            $stmt->bindValue(':q1', null, PDO::PARAM_NULL);
            $stmt->bindValue(':q2', null, PDO::PARAM_NULL);
            $stmt->bindValue(':q3', null, PDO::PARAM_NULL);
            $stmt->bindValue(':q4', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':q1', $q, PDO::PARAM_STR);
            $stmt->bindValue(':q2', $q, PDO::PARAM_STR);
            $stmt->bindValue(':q3', $q, PDO::PARAM_STR);
            $stmt->bindValue(':q4', $q, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $results;
    } catch (PDOException $e) {
        send_error($e->getMessage(), false);
        exit();
    }
}

function getTripsExportForUser($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT t.trip_id,
                t.trip_title,
                t.start_date,
                t.end_date,
                r.review_id,
                r.rating,
                r.written_review,
                r.date_written,
                GROUP_CONCAT(DISTINCT l.location_name
                             ORDER BY tl.trip_location_id
                             SEPARATOR ' -> ') AS itinerary
         FROM trips t
         LEFT JOIN reviews r ON r.trip_id = t.trip_id
         LEFT JOIN trip_locations tl ON tl.trip_id = t.trip_id
         LEFT JOIN locations l ON l.location_id = tl.location_id
         WHERE t.username = :username
         GROUP BY t.trip_id, t.trip_title, t.start_date, t.end_date,
                  r.review_id, r.rating, r.written_review, r.date_written
         ORDER BY t.start_date DESC, t.trip_id DESC"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $results;
}


function getCommentsExportForUser($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT t.trip_id,
                t.trip_title,
                r.review_id,
                c.comment_id,
                c.commenter_username,
                c.comment_text,
                c.date_written
         FROM trips t
         JOIN reviews r  ON r.trip_id = t.trip_id
         JOIN comments c ON c.review_id = r.review_id
         WHERE t.username = :username
         ORDER BY r.review_id, c.date_written, c.comment_id"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $results;
}
?>