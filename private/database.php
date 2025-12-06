<?php
# Handles all functionality with database, designed to be used by JS Fetch calls to /api/specific_endpoint

# Will respond with JSON of format
# { "data": ... } on success
# { "error": ... } on failure

$db = get_db_connection();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$post_data = json_decode(file_get_contents('php://input'), true);

switch ($segments[1]) {
    case 'initialize_db':
        initialize_db();
        exit();
        break;
    case 'destroy_db':
        destroy_db();
        exit();
        break;
    case 'createUser':
        createUser($post_data['username'], $post_data['email'], $post_data['password_hash'], $post_data['first_name'], $post_data['last_name']);
        send_success();
        break;
    case 'searchLocations':
        $locations = searchLocations($post_data['q'], $post_data['limit'], $post_data['offset']);
        send_json_response(['data' => $locations]);
        break;
    case 'searchReviews':
        $reviews = searchReviews($post_data['q'], $post_data['limit'], $post_data['offset']);
        send_json_response(['data' => $reviews]);
        break;
    case 'createLocation':
        createLocation($post_data['location_name'], $post_data['country'], $post_data['longitude'], $post_data['latitude']);
        send_success();
        break;
    case 'exportUserReviews':
        $username = $_SESSION['username'] ?? null;
        if (!$username) {
            send_error("Not logged in");
            exit();
        }
    
        $rows = getTripsExportForUser($username);
    
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="user_reviews.csv"');
    
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($rows[0]));
    
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
    
        fclose($output);
        break;
    default:
        send_error('Unknown endpoint');
}

function send_success() {
    send_json_response(['data' => 'Success']);
}

function send_error($message) {
    send_json_response(['error' => $message]);
}

function send_json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
}

function get_db_connection() {
    $host = 'localhost';
    $db   = 'project';
    $user = 'project_db_connection';
    $pass = 'fake_password';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
         return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
         throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

function initialize_db() {
    global $db;
    $stmt = "CREATE TABLE IF NOT EXISTS users (
                username VARCHAR(50) PRIMARY KEY,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50)
            );
            CREATE TABLE IF NOT EXISTS following (
                follower_username VARCHAR(50),
                followee_username VARCHAR(50),
                PRIMARY KEY (follower_username, followee_username),
                FOREIGN KEY (follower_username) REFERENCES users(username) ON DELETE CASCADE,
                FOREIGN KEY (followee_username) REFERENCES users(username) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS locations (
                location_id INT AUTO_INCREMENT PRIMARY KEY,
                location_name VARCHAR(100) NOT NULL,
                country VARCHAR(100) NOT NULL,
                longitude DECIMAL(9,6),
                latitude DECIMAL(9,6)
            );
            CREATE TABLE IF NOT EXISTS trips (
                trip_id INT AUTO_INCREMENT PRIMARY KEY,
                trip_title VARCHAR(100) NOT NULL,
                start_date DATE,
                end_date DATE,
                username VARCHAR(50),
                FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS transportation (
                transit_id INT AUTO_INCREMENT PRIMARY KEY,
                transit_type VARCHAR(50),
                transit_length INT
            );
            CREATE TABLE IF NOT EXISTS trip_locations (
                trip_location_id INT AUTO_INCREMENT PRIMARY KEY,
                trip_id INT,
                location_id INT,
                transit_id INT,
                FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE,
                FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE,
                FOREIGN KEY (transit_id) REFERENCES transportation(transit_id) ON DELETE SET NULL
            );
            CREATE TABLE IF NOT EXISTS reviews (
                review_id INT AUTO_INCREMENT PRIMARY KEY,
                rating INT NOT NULL,
                written_review TEXT,
                date_written DATE,
                trip_id INT,
                FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS comments (
                comment_id INT AUTO_INCREMENT PRIMARY KEY,
                comment_text TEXT NOT NULL,
                date_written DATE,
                commenter_username VARCHAR(50),
                review_id INT,
                FOREIGN KEY (commenter_username) REFERENCES users(username) ON DELETE CASCADE,
                FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS list (
                list_id INT AUTO_INCREMENT PRIMARY KEY,
                list_title VARCHAR(100) NOT NULL,
                username VARCHAR(50),
                FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS list_item (
                list_item_id INT AUTO_INCREMENT PRIMARY KEY,
                list_index INT,
                list_id INT,
                trip_id INT,
                FOREIGN KEY (list_id) REFERENCES list(list_id) ON DELETE CASCADE,
                FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE
            );";
    $db->exec($stmt);
}

function destroy_db() {
    # TODO: Add check if youre logged in as an admin user
    global $db;
    $stmt = "DROP TABLE IF EXISTS list_item;
            DROP TABLE IF EXISTS list;
            DROP TABLE IF EXISTS comments;
            DROP TABLE IF EXISTS reviews;
            DROP TABLE IF EXISTS trip_locations;
            DROP TABLE IF EXISTS transportation;
            DROP TABLE IF EXISTS trips;
            DROP TABLE IF EXISTS locations;
            DROP TABLE IF EXISTS following;
            DROP TABLE IF EXISTS users;";
    $db->exec($stmt);
}

# TODO: why do we need this? All the functions from milestone 2 reference this but I dont know why
function db_null_or_string($value) {
    if ($value === null || trim($value) === '') {
        return null;
    }
    return $value;
}


function createUser($username, $email, $password_hash, $first_name, $last_name) {
    if($_SESSION['username']){
        send_error('Already logged in');
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO users (username, email, password, first_name, last_name)
         VALUES (:username, :email, :password_hash, :first_name, :last_name)"
    );
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password_hash', $password_hash);
    $stmt->bindValue(':first_name', $first_name);
    $stmt->bindValue(':last_name', $last_name);
    $stmt->execute();
    $stmt->closeCursor();
}


function getUserByLogin($login) {
    global $db;
    $stmt = $db->prepare(
        "SELECT username, email, password, first_name, last_name
         FROM users
         WHERE (username = :login OR email = :login)
         LIMIT 1"
    );
    $stmt->bindValue(':login', $login);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}

function getProfileStats($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT u.username,
                u.first_name,
                u.last_name,
                u.email,
                (SELECT COUNT(*) FROM trips t WHERE t.username = u.username) AS trip_count,
                (SELECT COUNT(*) FROM reviews r JOIN trips t ON t.trip_id = r.trip_id
                 WHERE t.username = u.username) AS review_count,
                (SELECT COUNT(*) FROM following f WHERE f.followee_username = u.username) AS followers,
                (SELECT COUNT(*) FROM following f WHERE f.follower_username = u.username) AS following
         FROM users u
         WHERE u.username = :username"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result;
}




function followUser($follower_username, $followee_username) {
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO following (follower_username, followee_username)
         VALUES (:follower_username, :followee_username)"
    );
    $stmt->bindValue(':follower_username', $follower_username);
    $stmt->bindValue(':followee_username', $followee_username);
    $stmt->execute();
    $stmt->closeCursor();
}


function unfollowUser($follower_username, $followee_username) {
    global $db;
    $stmt = $db->prepare(
        "DELETE FROM following
         WHERE follower_username = :follower_username
           AND followee_username = :followee_username"
    );
    $stmt->bindValue(':follower_username', $follower_username);
    $stmt->bindValue(':followee_username', $followee_username);
    $stmt->execute();
    $stmt->closeCursor();
}


function getFollowers($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT follower_username
         FROM following
         WHERE followee_username = :username
         ORDER BY follower_username"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();
    return $result;
}


function getFollowing($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT followee_username
         FROM following
         WHERE follower_username = :username
         ORDER BY followee_username"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();
    return $result;
}




function createLocation($location_name, $country, $longitude, $latitude) {
    if(!$_SESSION['username']){
        send_error('Must be logged in to create a location');
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
    $stmt->closeCursor();
}


function searchLocations($q, $limit, $offset) {
    global $db;
    $q = db_null_or_string($q);

    $stmt = $db->prepare(
    # TODO: this used to be "SELECT location_id, location_name, country, longitude, latitude. BUt i removed id bc i want it to work rn so i can stop
    "SELECT location_name, country, longitude, latitude
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


function getTripsForUser($username, $limit, $offset) {
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
         ORDER BY t.start_date DESC, t.trip_id DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
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




function createReview($rating, $written_review, $date_written, $trip_id) {
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


function updateReview($review_id, $owner_username, $rating, $written_review, $date_written) {
    global $db;
    $stmt = $db->prepare(
        "UPDATE reviews r
         JOIN trips t ON t.trip_id = r.trip_id
         SET r.rating = :rating,
             r.written_review = :written_review,
             r.date_written = :date_written
         WHERE r.review_id = :review_id
           AND t.username = :owner_username"
    );
    $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindValue(':written_review', $written_review);
    $stmt->bindValue(':date_written', $date_written);
    $stmt->bindValue(':review_id', $review_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_username', $owner_username);
    $stmt->execute();
    $stmt->closeCursor();
}


function deleteReview($review_id, $owner_username) {
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



function getHomeFeed($viewer_username, $limit, $offset) {
    global $db;
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
    $q = db_null_or_string($q);

    $stmt = $db->prepare(
        "SELECT r.review_id,
                r.rating,
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
         GROUP BY r.review_id, r.rating, r.date_written,
                  t.trip_id, t.trip_title, t.username
         ORDER BY r.date_written DESC
         LIMIT :limit OFFSET :offset"
    );
    if ($q === null) {
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