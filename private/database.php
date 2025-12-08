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
require_once('../private/pictures.php');
require_once('../private/user_permissions.php');

$db = get_db_connection();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$post_data = json_decode(file_get_contents('php://input'), true);

switch ($segments[1]) {
    case 'dev':
        switch($segments[2]) {
            case 'initialize_db':
                initialize_db();
                exit();
            case 'destroy_db':
                destroy_db();
                exit();
            case 'populate_db':
                populate_db();
                exit();
            case 'display_db':
                display_db();
                exit();
            default:
                send_error('Unknown dev endpoint', true);
                exit();
        }
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

        foreach ($post_data['location_ids'] as $location_id) {
            if ($location_id !== '' && $location_id !== null) {
                addTripStop($trip_id, (int)$location_id, null);
            }
        }

        send_json_response(['data' => ['trip_id' => $trip_id]]);
        break;
    case 'createReview':
        $rating = $_POST['rating'] ?? $post_data['rating'] ?? null;
        $review_text = $_POST['review_text'] ?? $post_data['review_text'] ?? null;
        $trip_id = $_POST['trip_id'] ?? $post_data['trip_id'] ?? null;

        if (!$rating || !$trip_id) {
            send_error('Missing required review fields', true);
            exit();
        }

        $review_id = createReview($rating, $review_text, $trip_id);

        if (isset($_FILES['review_images'])) {
            processUploadedImages($trip_id, $_FILES['review_images']);
        }

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
        $review_id = $_POST['review_id'] ?? $post_data['review_id'] ?? null;
        $rating = $_POST['rating'] ?? $post_data['rating'] ?? null;
        $review_text = $_POST['review_text'] ?? $post_data['review_text'] ?? '';
        $trip_id = $_POST['trip_id'] ?? $post_data['trip_id'] ?? null;

        updateReview($review_id, $_SESSION['username'] ?? null, $rating, $review_text);

        if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
            if ($trip_id) {
                deleteAllPicturesForTrip($trip_id);
                processUploadedImages($trip_id, $_FILES['review_images']);
            }
        }

        send_success();
        break;
    case 'deleteReview':
        deleteReview((int)$post_data['review_id'], $_SESSION['username'] ?? null);
        send_success();
        break;
    case 'getReviewByTripId':
        $review = getReviewByTripId((int)$post_data['trip_id']);
        send_json_response(['data' => $review]);
        break;
    case 'getPicturesForTrip':
        $pictures = getPicturesForTrip((int)$post_data['trip_id']);
        send_json_response(['data' => $pictures]);
        break;
    case 'getPicture':
        $picture_id = (int)($_GET['picture_id'] ?? 0);
        $picture = getPictureData($picture_id);

        if ($picture && $picture['pic_data']) {
            header('Content-Type: image/jpeg');
            echo $picture['pic_data'];
        } else {
            http_response_code(404);
            echo 'Picture not found';
        }
        break;
    case 'deleteTrip':
        deleteTrip((int)$post_data['trip_id'], $_SESSION['username'] ?? null);
        send_success();
        break;
    case 'deleteList':
        deleteList((int)$post_data['list_id'], $_SESSION['username'] ?? null);
        send_success();
        break;
    default:
        send_error('Unknown endpoint', true);
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
                email VARCHAR(255) UNIQUE NOT NULL,
                password TEXT NOT NULL,
                first_name TEXT,
                last_name TEXT,
                is_admin BOOLEAN DEFAULT FALSE 
            );
            CREATE TABLE IF NOT EXISTS following (
                follow_id INT AUTO_INCREMENT PRIMARY KEY,
                follower_username VARCHAR(255),
                followee_username VARCHAR(255),
                UNIQUE (follower_username, followee_username),
                FOREIGN KEY (followee_username) REFERENCES users(username) ON DELETE CASCADE,
                FOREIGN KEY (follower_username) REFERENCES users(username) ON DELETE CASCADE
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
                transit_length TEXT
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
            CREATE TABLE IF NOT EXISTS pictures (
                picture_id INT AUTO_INCREMENT PRIMARY KEY,
                trip_id INT,
                pic_data LONGBLOB,
                pic_caption TEXT,
                date_taken DATE,
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
            
    $db->exec($stmt);

    // Create row-level security triggers
    createRowLevelSecurityTriggers();

    $db->exec("DROP TRIGGER IF EXISTS trg_review_set_date");
    $db->exec("
        CREATE TRIGGER trg_review_set_date
        BEFORE INSERT ON reviews
        FOR EACH ROW
        BEGIN
            IF NEW.date_written IS NULL THEN
                SET NEW.date_written = NOW();
            END IF;
        END
    ");

    $db->exec("DROP TRIGGER IF EXISTS trg_review_update_date");
    $db->exec("
        CREATE TRIGGER trg_review_update_date
        BEFORE UPDATE ON reviews
        FOR EACH ROW
        BEGIN
            SET NEW.date_written = NOW();
        END
    ");

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
    $is_admin = is_admin($_SESSION['username'] ?? null);
    if ($is_admin != true) {
        send_error('Insufficient permissions. Admin required.', true);
        exit();
    }

    global $db;

    // Drop all MySQL user accounts BEFORE dropping the users table
    dropAllMySQLUserAccounts();

    $stmt = "SET FOREIGN_KEY_CHECKS = 0;
            DROP TABLE IF EXISTS list_item;
            DROP TABLE IF EXISTS list;
            DROP TABLE IF EXISTS comments;
            DROP TABLE IF EXISTS reviews;
            DROP TABLE IF EXISTS trip_locations;
            DROP TABLE IF EXISTS transportation;
            DROP TABLE IF EXISTS trips;
            DROP TABLE IF EXISTS locations;
            DROP TABLE IF EXISTS following;
            DROP TABLE IF EXISTS users;
            DROP TABLE IF EXISTS pictures;
            SET FOREIGN_KEY_CHECKS = 1;";
    $db->exec($stmt);

    send_json_response(['data' => 'Database and user accounts destroyed successfully']);
}

function populate_db() {
    global $db;

    // Check if all tables are empty before populating
    $tables = ['users', 'following', 'trips', 'locations', 'transportation', 'trip_locations',
               'reviews', 'comments', 'list', 'list_item', 'pictures'];

    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            send_error("Cannot populate database: table '$table' already contains $count row(s). Tables must be empty.", true);
            exit();
        }
    }

    // All tables are empty, proceed with population
    $sql = '
        INSERT INTO users (username, email, password, first_name, last_name, is_admin) VALUES
          ("jill","jill@gmail.com", "jill", "Jill", "Smith", false),
          ("alex", "alex@gmail.com", "alex", "Alex", "Petullo", false),
          ("chandler", "chandler@virginia.edu", "chandler", "Chandler", "Morris", false),
          ("jeremy","ehb3rt@virginia.edu", "jeremy", "Jeremy", "Grossman", false),
          ("kingjames","lebron@gmail.com", "lebron", "LeBron", "James", true),
          ("tswift", "taylor@gmail.com", "taytay","Taylor", "Swift", false),
          ("dacount", "dracula@gmail.com", "blood", "Count", "Dracula", false),
          ("spiderman", "spiderman@gmail.com", "spidey", "Peter", "Parker", false),
          ("honestabe", "abe@gmail.com", "pres16", "Abraham", "Lincoln", false),
          ("ScienceGuy", "billnye@gmail.com","billbillbill", "Bill", "Nye", false),
          ("JimRyan", "jimryan@gmail.com", "Pres", "Jim", "Ryan", true),
          ("JaneAusten", "Jane@gmail.com", "author", "Jane", "Austen", false),
          ("coachodom","rodom@gmail.com", "fastpace", "Ryan", "Odom", false),
          ("Mstreep", "meryl@gmail.com","oscarwinner", "Meryl", "Streep", false),
          ("MadTitan", "thanos@gmail.com", "infinitystones","Thanos", "Thanos", false),
          ("ironman", "ironman@gmail.com", "iamironman", "Tony", "Stark", false),
          ("admin", "admin@admin.com", "$2y$10$OS2QvbrYuFam3FwaOdEPq.00FILSMooMfoXXBieWjvzjdrYVIqfPW", "Admin", "User", true);
          
        INSERT INTO following (follower_username, followee_username) VALUES
          ("dacount","MadTitan"), 
          ("kingjames","tswift"), 
          ("tswift","kingjames"),
          ("honestabe","ironman"), 
          ("alex","chandler"), 
          ("chandler","coachodom"),
          ("ironman","spiderman"), 
          ("coachodom","spiderman"), 
          ("spiderman","ironman"),
          ("ironman","ScienceGuy"), 
          ("alex","jill"), 
          ("Mstreep","jeremy"), 
          ("jeremy","spiderman"),
          ("kingjames","spiderman");
          
        INSERT INTO trips (trip_title, start_date,end_date, username) VALUES 
          ("Paris trip!", "2025-07-24", "2025-07-31", "tswift"), 
          ("London trip!", "2025-08-01", "2025-08-11", "tswift"), 
          ("nashvilletrip with friends", "2023-01-01", "2023-01-09", "alex"), 
          ("football game in cali", "2025-10-26", "2025-11-01", "chandler"), 
          ("basketball game in lousville", "2025-09-26", "2025-09-29", "coachodom"), 
          ("sucking blood in transylvania", "2019-02-06", "2019-02-15", "dacount"),
          ("visting family up north", "2020-01-03", "2020-02-10", "honestabe"), 
          ("saving theworld", "2024-11-04", "2024-11-10", "ironman"), 
          ("visiting canada", "2022-03-04", "2022-03-12", "kingjames"), 
          ("visiting detroit", "2021-09-14", "2021-09-22", "JaneAusten"), 
          ("germany studyabroad", "2023-12-31", "2024-01-09", "jeremy"), 
          ("going up the hill", "2023-11-30", "2023-12-05", "jill"), 
          ("visiting UVA", "2025-01-31", "2025-02-02", "JimRyan"), 
          ("fishing trip", "2025-02-03", "2025-02-04", "JimRyan"), 
          ("game 1", "2025-10-01", "2025-10-04", "kingjames"),
          ("rehab", "2025-10-11", "2025-10-18", "kingjames"), 
          ("visiting my family", "2025-10-20", "2025-10-27", "kingjames"), 
          ("Oscars", "2024-10-10", "2024-10-17", "Mstreep"), 
          ("Filming show inHollywood", "2024-01-10", "2024-01-17", "ScienceGuy"), 
          ("fighting green goblin", "2024-03-02", "2024-03-06", "spiderman");
          
        INSERT INTO locations (location_name, country, longitude, latitude) VALUES 
          ("Paris","France",2.35,48.86), 
          ("London","United Kingdom",-0.13,51.51),
          ("Nashville","United States",-86.78,36.16), 
          ("Berkeley","United States", -122.27,37.87), 
          ("Louisville","United States",-85.77,38.25), 
          ("Transylvania", "Romania",25.22, 46.18),
          ("Toronto", "Canada", -79.38, 43.65), 
          ("Rio de Janeiro", "Brazil", -43.17, -22.91), 
          ("Calgary", "Canada", -114.07, 51.04), 
          ("Detroit","United States",-83.04,42.33),
          ("Dortmund","Germany",7.47,51.51), 
          ("Boise","United States", -116.20,43.62),
          ("Charlottesville","United States",-78.48,38.03),
          ("Athens","Greece",23.73,37.98), 
          ("Los Angeles","United States",-118.24,34.05),
          ("Akron","United States",-81.52,41.08), 
          ("Cleveland","United States",-81.69,41.50), 
          ("NewYork City","United States",-73.98,40.75), 
          ("Secaucus", "United States", -74.05, 40.79),
          ("Beijing", "China", 116.41, 39.90);
          
        INSERT INTO transportation (transit_type, transit_length) VALUES 
          ("car", "<1 hour"), ("car", "1-2 hours"), ("car", "3-4 hours"), ("car", "5-8 hours"),
          ("car", "9-15 hours"), ("car", "16-24 hours"), ("car", ">24 hours"), ("train", "<1 hour"), ("train","1-2 hours"), 
          ("train", "3-4 hours"), ("train", "5-8 hours"), ("train", "9-15 hours"), ("train", "16-24 hours"), ("train", ">24 hours"), 
          ("plane", "<1 hour"), ("plane", "1-2 hours"), ("plane", "3-4 hours"), ("plane", "5-8 hours"), ("plane", "9-15 hours"), 
          ("plane", "16-24 hours"), ("plane", ">24 hours"), ("bus", "<1 hour"), ("bus", "1-2 hours"), ("bus", "3-4 hours"), 
          ("bus", "5-8 hours"), ("bus", "9-15 hours"), ("bus", "24 hours"), ("bus", ">24 hours"), ("boat", "<1 hour"), 
          ("boat", "1-2 hours"), ("boat", "3-4 hours"), ("boat", "5-8 hours"), ("boat", "9-15 hours"), ("boat", "16-24 hours"), 
          ("boat", ">24 hours");
          
        INSERT INTO trip_locations (trip_id, location_id, transit_id) VALUES 
          (1,1,19), (2,2,19),(3,3,3), (4,4,14), (5,5,11), (6,6,32), (7,7,6), (8,8,19), 
          (9,9,7), (10,10,5), (11,11,4),(12,12,5), (13,13,6), (14,14,33), (15,15,10), 
          (16,16,9), (17,17,6), (18,18,8), (19,15,3),(20,20,27);
          
        INSERT INTO reviews (rating,written_review,date_written,trip_id) VALUES 
          (4,"goodtrip","2025-10-08",1), 
          (4,"good trip","2025-11-18",2), 
          (3,"mid trip","2025-11-09",3),
          (2,"awful","2025-11-19",4), 
          (3,"decent","2025-12-08",5), 
          (3,"good trip","2025-11-18",6),
          (5,"good trip","2025-11-08",7), 
          (5,"time of my life","2025-12-08",8), 
          (5,"great trip","2025-12-18",9), 
          (3,"mid trip","2025-12-25",10), 
          (1,"bad trip","2025-12-24",11), 
          (2,"bad trip","2025-12-29",12), 
          (2,"bad trip","2025-12-30",13), 
          (1,"i nearly died","2025-12-31",14), 
          (4,"goodtrip","2025-11-11",15), 
          (4,"good trip","2025-12-15",16), 
          (3,"mediocre trip","2025-11-29",17),
          (2,"bad trip","2025-11-28",18), 
          (5,"good trip","2025-11-30",19), 
          (5,"i met my wife","2024-04-08",20);
        
        INSERT INTO comments (comment_text, date_written, commenter_username, review_id) VALUES
          ("IAgree", "2026-02-03", "alex",1), 
          ("I Disagree", "2026-02-05", "coachodom",1), 
          ("I disagreeand I hate you", "2026-12-03", "dacount",2), 
          ("I went there last summer! so fun", "2026-01-03", "honestabe", 3), 
          ("Thinking of going there soon; know any good restaurants?", "2026-07-07", "kingjames",6);
          
        INSERT INTO list (list_title, username) VALUES 
          ("concert locations", "tswift"), 
          ("fun basketball spots", "kingjames"),
          ("blood sucking locations", "dacount"), 
          ("good fight locations", "spiderman"), 
          ("my favoritetrips ever", "JimRyan");
    ';
    $db->exec($sql);

    // Convert our actual images to binary data for insertion into the database.
    $image_path_1 = __DIR__ . '..\..\public\images\vacation1.jpg';
    $image_path_2 = __DIR__ . '..\..\public\images\vacation2.jpg';
    $image_path_3 = __DIR__ . '..\..\public\images\vacation3.jpg';
    $image_path_4 = __DIR__ . '..\..\public\images\vacation4.jpg';

    $stmt = $db->prepare(
        "INSERT INTO pictures (trip_id, pic_data, pic_caption, date_taken)
        VALUES (:trip_id, :pic_data, :pic_caption, :date_taken)"
    );

    function insert_pic($db, $stmt, $trip_id, $path, $caption, $date) {
        $data = file_get_contents($path);

        $stmt->bindValue(':trip_id', $trip_id);
        $stmt->bindValue(':pic_data', $data, PDO::PARAM_LOB);
        $stmt->bindValue(':pic_caption', $caption);
        $stmt->bindValue(':date_taken',  $date);

        $stmt->execute();
    }

    insert_pic($db, $stmt, 10, $image_path_1, 'The Beach!', '2025-07-25');
    insert_pic($db, $stmt, 10, $image_path_2, 'Peru!', '2025-07-26');
    insert_pic($db, $stmt, 17, $image_path_3, 'Downtown Nashville!', '2023-01-03');
    insert_pic($db, $stmt, 4, $image_path_4, 'Scary highway.', '2024-11-07');

    $stmt->closeCursor();

    // Grant permissions to all users that were just populated
    grantPermissionsToExistingUsers();

    send_json_response(['data' => 'Database populated and permissions granted successfully']);
}

function display_db() {
    $is_admin = is_admin($_SESSION['username'] ?? null);
    if ($is_admin != true) {
        send_error('Insufficient permissions. Admin required.', true);
        exit();
    }
    global $db;

    // Get all table names
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $output = "<!DOCTYPE html>\n<html>\n<body>\n";
    $output .= "<h1>Database Contents</h1>\n";

    foreach ($tables as $table) {
        $output .= "<h2>Table: $table</h2>\n";

        // Get row count
        $countStmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $countStmt->fetch()['count'];
        $output .= "<div class='table-info'>Total rows: $count</div>\n";

        if ($count > 0) {
            // Get all data from the table
            $dataStmt = $db->query("SELECT * FROM `$table`");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $output .= "<table>\n";

                // Table headers
                $output .= "<tr>\n";
                foreach (array_keys($rows[0]) as $column) {
                    $output .= "<th>" . $column . "</th>\n";
                }
                $output .= "</tr>\n";

                // Table rows
                foreach ($rows as $row) {
                    $output .= "<tr>\n";
                    foreach ($row as $column => $value) {
                        // Special handling for binary data (images)
                        if ($column === 'pic_data' && $value !== null) {
                            $output .= "<td>[BINARY DATA - " . strlen($value) . " bytes]</td>\n";
                        } else {
                            $output .= "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>\n";
                        }
                    }
                    $output .= "</tr>\n";
                }

                $output .= "</table>\n";
            }
        } else {
            $output .= "<div class='no-data'>No data in this table</div>\n";
        }
    }

    $output .= "</body>\n</html>";

    header('Content-Type: text/html');
    echo $output;
}
?>