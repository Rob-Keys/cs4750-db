<?php
# Handles all functionality with database, designed to be used by JS Fetch calls to /api/specific_endpoint

# Will respond with JSON of format
# { "data": ... } on success
# { "error": ... } on failure

require_once('../private/locations.php');
require_once('../private/lists.php');
require_once('../private/reviews.php');
require_once('../private/trips.php');
require_once('../private/users.php');

$db = get_db_connection();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$post_data = json_decode(file_get_contents('php://input'), true);

switch ($segments[1]) {
    case 'initialize_db':
        initialize_db();
        exit();
    case 'destroy_db':
        destroy_db();
        exit();
    case 'searchLocations':
        $locations = searchLocations($post_data['q'], $post_data['limit'], $post_data['offset']);
        send_json_response(['data' => $locations]);
        break;
    case 'searchReviews':
        $reviews = searchReviews($post_data['q'], $post_data['limit'], $post_data['offset']);
        send_json_response(['data' => $reviews]);
        break;
    case 'getTripsForUser':
        $trips = getTripsForUser($post_data['username']);
        send_json_response(['data' => $trips]);
        break;
    case 'getListsForUser':
        $lists = getListsForUser($post_data['username']);
        send_json_response(['data' => $lists]);
        break;
    case 'getReviewsForUser':
        $reviews = getReviewsForUser($post_data['username']);
        send_json_response(['data' => $reviews]);
        break;
    case 'createLocation':
        $trip_id = $post_data['trip_id'] ?? null;
    
        $location_id = createLocation(
            $post_data['location_name'],
            $post_data['country'],
            $post_data['longitude'],
            $post_data['latitude']
        );
    
        if ($trip_id !== null && $trip_id !== '') {
            addTripStop((int)$trip_id, (int)$location_id, null);
        }
    
        send_success();
        break;
    
    case 'createUser':
        createUser(
            $post_data['username'],
            $post_data['email'],
            $post_data['password'],
            $post_data['first_name'],
            $post_data['last_name']
        );
    
        $_SESSION['username'] = $post_data['username'];
    
        send_success();
        break;
    case 'createTrip':
        $trip_id = createTrip($post_data['trip_title'], $post_data['start_date'], $post_data['end_date'], $post_data['username']);
        send_json_response(['data' => ['trip_id' => $trip_id]]);
        break;
    case 'createReview':
        $review_id = createReview($post_data['rating'], $post_data['review_text'], $post_data['trip_id']);
        send_json_response(['data' => ['review_id' => $review_id]]);
        break;
    case 'loginUser':
        loginUser($post_data['username'], $post_data['password']);
        send_success();
        break;
    case 'signout':
        unset($_SESSION['username']);
        send_success();
        break;
    case 'exportUserReviews':
        $username = $_SESSION['username'] ?? null;
        if (!$username) {
            send_error("Not logged in", true);
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
    case 'createList':
        $username = $_SESSION['username'] ?? null;
        if (!$username) {
            send_error('Not logged in', true);
            exit();
        }

        $list_title = $post_data['list_title'] ?? '';
        $trip_ids   = $post_data['trip_ids'] ?? [];

        if (trim($list_title) === '') {
            send_error('List title is required', true);
            exit();
        }

        $list_id = createList($list_title, $username);

        $index = 0;
        foreach ($trip_ids as $trip_id) {
            if ($trip_id !== '' && $trip_id !== null) {
                addTripToList($index++, $list_id, (int)$trip_id);
            }
        }

        send_json_response(['data' => ['list_id' => $list_id]]);
        break;
    case 'followUser':
        followUser($_SESSION['username'] ?? null, $post_data['followee_username'] ?? null);
        send_success();
        break;
    
    case 'unfollowUser':
        unfollowUser($_SESSION['username'] ?? null, $post_data['followee_username'] ?? null);
        send_success();
        break;
    
    case 'getFollowingForUser':
        $following = getFollowing($post_data['username'] ?? $_SESSION['username'] ?? null);
        send_json_response(['data' => $following]);
        break;

    case 'getFollowersForUser':
        $followers = getFollowers($post_data['username'] ?? $_SESSION['username'] ?? null);
        send_json_response(['data' => $followers]);
        break;

    case 'addComment':
        $comment_id = addComment($post_data['comment_text'] ?? '', date('Y-m-d H:i:s'), $_SESSION['username'] ?? null, (int)$post_data['review_id']);
        send_json_response(['data' => ['comment_id' => $comment_id]]);
        break;

    case 'getCommentsForReview':
        $comments = getCommentsForReview((int)$post_data['review_id'], (int)($post_data['limit'] ?? 20), (int)($post_data['offset'] ?? 0));
        send_json_response(['data' => $comments]);
        break;

    case 'updateComment':
        updateComment((int)$post_data['comment_id'], $_SESSION['username'] ?? null, $post_data['comment_text'] ?? '');
        send_success();
        break;

    case 'deleteComment':
        deleteComment((int)$post_data['comment_id'], $_SESSION['username'] ?? null);
        send_success();
        break;
    case 'getHomeFeed':
        $feed = getHomeFeed($_SESSION['username'] ?? ($post_data['username'] ?? null), (int)($post_data['limit'] ?? 10), (int)($post_data['offset'] ?? 0));
        send_json_response(['data' => $feed]);
        break;

    case 'updateReview':
        updateReview($post_data['review_id'] ?? null, $_SESSION['username'] ?? null, $post_data['rating'] ?? null, $post_data['review_text'] ?? '');
        send_success();
        break;
    case 'deleteReview':
        deleteReview((int)$post_data['review_id'], $_SESSION['username'] ?? null);
        send_success();
        break;
    default:
        send_error('Unknown endpoint', false);
}

function send_success() {
    send_json_response(['data' => 'Success']);
}

function send_error($message, $is_user_error = false) {
    if($is_user_error) {
        send_json_response(['error' => $message]);
    } else {
        send_json_response(['error' => "Internal Server Error"]);
    }
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
    # The VARCHAR(255) fields are required to be VARCHAR instead of text because theyre Primary Keys or reference a PK
    $stmt = "CREATE TABLE IF NOT EXISTS users (
                username VARCHAR(255) PRIMARY KEY,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                first_name TEXT,
                last_name TEXT
            );
            CREATE TABLE IF NOT EXISTS following (
                follower_username VARCHAR(255),
                followee_username VARCHAR(255),
                PRIMARY KEY (follower_username, followee_username),
                FOREIGN KEY (follower_username) REFERENCES users(username) ON DELETE CASCADE,
                FOREIGN KEY (followee_username) REFERENCES users(username) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS locations (
                location_id INT AUTO_INCREMENT PRIMARY KEY,
                location_name TEXT NOT NULL,
                country TEXT NOT NULL,
                longitude DECIMAL(9,6),
                latitude DECIMAL(9,6)
            );
            CREATE TABLE IF NOT EXISTS trips (
                trip_id INT AUTO_INCREMENT PRIMARY KEY,
                trip_title TEXT NOT NULL,
                start_date DATE,
                end_date DATE,
                username VARCHAR(255),
                FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS transportation (
                transit_id INT AUTO_INCREMENT PRIMARY KEY,
                transit_type TEXT,
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
                commenter_username VARCHAR(255),
                review_id INT,
                FOREIGN KEY (commenter_username) REFERENCES users(username) ON DELETE CASCADE,
                FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS list (
                list_id INT AUTO_INCREMENT PRIMARY KEY,
                list_title TEXT NOT NULL,
                username VARCHAR(255),
                FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS list_item (
                list_item_id INT AUTO_INCREMENT PRIMARY KEY,
                list_index INT,
                list_id INT,
                trip_id INT,
                FOREIGN KEY (list_id) REFERENCES list(list_id) ON DELETE CASCADE,
                FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE
            );
            ALTER TABLE trips 
            ADD CONSTRAINT chk_trip_dates 
            CHECK (start_date IS NULL OR end_date IS NULL OR end_date >= start_date);

            ALTER TABLE reviews 
            ADD CONSTRAINT chk_review_rating 
            CHECK (rating BETWEEN 1 AND 5);

            ALTER TABLE locations 
            ADD CONSTRAINT chk_latitude  CHECK (latitude  BETWEEN -90  AND 90),
            ADD CONSTRAINT chk_longitude CHECK (longitude BETWEEN -180 AND 180);

            ALTER TABLE list_item 
            ADD CONSTRAINT chk_list_index_nonneg 
            CHECK (list_index >= 0);
            
            ";

        $triggers = "
            DROP TRIGGER IF EXISTS trg_review_set_date;
            DROP TRIGGER IF EXISTS trg_review_update_date;

            DELIMITER //

            CREATE TRIGGER trg_review_set_date
            BEFORE INSERT ON reviews
            FOR EACH ROW
            BEGIN
                IF NEW.date_written IS NULL THEN
                    SET NEW.date_written = NOW();
                END IF;
            END//

            CREATE TRIGGER trg_review_update_date
            BEFORE UPDATE ON reviews
            FOR EACH ROW
            BEGIN
                SET NEW.date_written = NOW();
            END//

            DELIMITER ;
            ";

    $db->exec($triggers);
    $db->exec($stmt);
    create_stored_procedures();
}
function create_stored_procedures() {
    global $db;

    try {
        $db->exec("DROP PROCEDURE IF EXISTS sp_get_user_feed");
    } catch (PDOException $e) {
    }

    $procSql = "
        CREATE PROCEDURE sp_get_user_feed (
            IN p_username VARCHAR(255),
            IN p_limit INT
        )
        BEGIN
            SELECT r.review_id, 
                   r.rating, 
                   r.written_review, 
                   r.date_written, 
                   t.trip_id, 
                   t.trip_title, 
                   t.start_date, 
                   t.end_date, 
                   t.username AS author, 
                   GROUP_CONCAT(DISTINCT l.location_name 
                                ORDER BY tl.trip_location_id 
                                SEPARATOR ' -> ') AS itinerary 
            FROM following f 
            JOIN trips t           ON t.username = f.followee_username 
            JOIN reviews r         ON r.trip_id  = t.trip_id 
            LEFT JOIN trip_locations tl ON tl.trip_id = t.trip_id 
            LEFT JOIN locations l       ON l.location_id = tl.location_id 
            WHERE f.follower_username = p_username 
            GROUP BY r.review_id, r.rating, r.written_review, r.date_written, 
                     t.trip_id, t.trip_title, t.start_date, t.end_date, t.username 
            ORDER BY r.date_written DESC 
            LIMIT p_limit;
        END
    ";

    try {
        $db->exec($procSql);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
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
?>