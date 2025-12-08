<?php
function createUser($username, $email, $password, $first_name, $last_name) {
    if(isset($_SESSION['username'])){
        send_error('Already logged in', true);
        exit();
    }
    global $db;
    $stmt = $db->prepare(
        "INSERT INTO users (username, email, password, first_name, last_name, is_admin)
         VALUES (:username, :email, :password_hash, :first_name, :last_name, 0)"
    );
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password_hash', value: password_hash($password, PASSWORD_DEFAULT));
    $stmt->bindValue(':first_name', $first_name);
    $stmt->bindValue(':last_name', $last_name);
    $stmt->execute();
    $stmt->closeCursor();
}

function loginUser($username, $password) {
    if(isset($_SESSION['username'])){
        send_error('Already logged in', true);
        exit();
    }
    $user = getUserByLogin($username);
    if (!$user || !password_verify($password, $user['password'])) {
        send_error('Invalid username/email or password', true);
        exit();
    }
    $_SESSION['username'] = $username;
}


function getUserByLogin($username) {
    global $db;
    $stmt = $db->prepare(
        "SELECT username, email, password, first_name, last_name
         FROM users
         WHERE username = :username
         LIMIT 1"
    );
    $stmt->bindValue(':username', $username);
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
    if (!$follower_username) {
        send_error("Not logged in", true);
        exit();
    }

    if (!$followee_username || $followee_username === $follower_username) {
        send_error("Invalid follow target", true);
        exit();
    }

    try {
        global $db;
        $stmt = $db->prepare(
            "INSERT INTO following (follower_username, followee_username)
             VALUES (:follower_username, :followee_username)"
        );
        $stmt->bindValue(':follower_username', $follower_username);
        $stmt->bindValue(':followee_username', $followee_username);
        $stmt->execute();
        $stmt->closeCursor();
    } catch (PDOException $e) {
        if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
            // Do nothing, already following
        } else {
            send_error("Could not follow user", false);
        }
    }
}


function unfollowUser($follower_username, $followee_username) {
    if (!$follower_username) {
        send_error("Not logged in", true);
        exit();
    }

    if (!$followee_username || $followee_username === $follower_username) {
        send_error("Invalid unfollow target", true);
        exit();
    }

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
    if(!$username) {
        send_error("Not logged in", true);
        exit();
    }
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
    if(!$username) {
        send_error("Not logged in", true);
        exit();
    }
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

function is_admin($username) {
    if(!$username) {
        return 0;
    }
    global $db;
    $stmt = $db->prepare(
        "SELECT is_admin
         FROM users
         WHERE username = :username
         LIMIT 1"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $result ? (int)$result['is_admin'] : false;
}
?>