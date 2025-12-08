<?php
function createMySQLUserAccount($username) {
    global $db;

    $short_username = substr($username, 0, 32); // MySQL username max length is 32

    // Generate a random password for the user
    $password = bin2hex(random_bytes(16));

    try {
        $db->exec("CREATE USER IF NOT EXISTS '{$short_username}'@'localhost' IDENTIFIED BY '{$password}'");
        $db->exec("GRANT SELECT, INSERT ON project.* TO '{$short_username}'@'localhost'");

        // Grant UPDATE and DELETE on specific columns/tables where they own the data
        // This is enforced via triggers that check ownership
        $db->exec("GRANT UPDATE, DELETE ON project.trips TO '{$short_username}'@'localhost'");
        $db->exec("GRANT UPDATE, DELETE ON project.reviews TO '{$short_username}'@'localhost'");
        $db->exec("GRANT UPDATE, DELETE ON project.comments TO '{$short_username}'@'localhost'");
        $db->exec("GRANT UPDATE, DELETE ON project.list TO '{$short_username}'@'localhost'");

        $db->exec("FLUSH PRIVILEGES");

        return [
            'mysql_username' => $short_username,
            'mysql_password' => $password
        ];
    } catch (PDOException $e) {
        error_log("User creation error for {$short_username}: " . $e->getMessage());
        return null;
    }
}

// Users can only UPDATE/DELETE rows they created
function createRowLevelSecurityTriggers() {
    global $db;

    $db->exec("DROP TRIGGER IF EXISTS trg_trips_update_check");
    $db->exec("
        CREATE TRIGGER trg_trips_update_check
        BEFORE UPDATE ON trips
        FOR EACH ROW
        BEGIN
            DECLARE current_mysql_user VARCHAR(32);
            DECLARE short_username VARCHAR(32);

            SET current_mysql_user = SUBSTRING_INDEX(USER(), '@', 1);
            SET short_username = SUBSTRING(OLD.username, 1, 32);
            IF current_mysql_user != 'project_db_connection'
               AND current_mysql_user != 'admin'
               AND current_mysql_user != short_username THEN
                SIGNAL SQLSTATE '45067'
                SET MESSAGE_TEXT = 'You can only update your own trips';
            END IF;
        END
    ");

    $db->exec("DROP TRIGGER IF EXISTS trg_trips_delete_check");
    $db->exec("
        CREATE TRIGGER trg_trips_delete_check
        BEFORE DELETE ON trips
        FOR EACH ROW
        BEGIN
            DECLARE current_mysql_user VARCHAR(32);
            DECLARE short_username VARCHAR(32);

            SET current_mysql_user = SUBSTRING_INDEX(USER(), '@', 1);
            SET short_username = SUBSTRING(OLD.username, 1, 32);
            IF current_mysql_user != 'project_db_connection'
               AND current_mysql_user != 'admin'
               AND current_mysql_user != short_username THEN
                SIGNAL SQLSTATE '45067'
                SET MESSAGE_TEXT = 'You can only delete your own trips';
            END IF;
        END
    ");

    $db->exec("DROP TRIGGER IF EXISTS trg_comments_update_check");
    $db->exec("
        CREATE TRIGGER trg_comments_update_check
        BEFORE UPDATE ON comments
        FOR EACH ROW
        BEGIN
            DECLARE current_mysql_user VARCHAR(32);
            DECLARE short_username VARCHAR(32);

            SET current_mysql_user = SUBSTRING_INDEX(USER(), '@', 1);
            SET short_username = SUBSTRING(OLD.commenter_username, 1, 32);

            IF current_mysql_user != 'project_db_connection'
               AND current_mysql_user != 'admin'
               AND current_mysql_user != short_username THEN
                SIGNAL SQLSTATE '45067'
                SET MESSAGE_TEXT = 'You can only update your own comments';
            END IF;
        END
    ");

    $db->exec("DROP TRIGGER IF EXISTS trg_comments_delete_check");
    $db->exec("
        CREATE TRIGGER trg_comments_delete_check
        BEFORE DELETE ON comments
        FOR EACH ROW
        BEGIN
            DECLARE current_mysql_user VARCHAR(32);
            DECLARE short_username VARCHAR(32);

            SET current_mysql_user = SUBSTRING_INDEX(USER(), '@', 1);
            SET short_username = SUBSTRING(OLD.commenter_username, 1, 32);

            IF current_mysql_user != 'project_db_connection'
               AND current_mysql_user != 'admin'
               AND current_mysql_user != short_username THEN
                SIGNAL SQLSTATE '45067'
                SET MESSAGE_TEXT = 'You can only delete your own comments';
            END IF;
        END
    ");

    $db->exec("DROP TRIGGER IF EXISTS trg_list_update_check");
    $db->exec("
        CREATE TRIGGER trg_list_update_check
        BEFORE UPDATE ON list
        FOR EACH ROW
        BEGIN
            DECLARE current_mysql_user VARCHAR(32);
            DECLARE short_username VARCHAR(32);

            SET current_mysql_user = SUBSTRING_INDEX(USER(), '@', 1);
            SET short_username = SUBSTRING(OLD.username, 1, 32);

            IF current_mysql_user != 'project_db_connection'
               AND current_mysql_user != 'admin'
               AND current_mysql_user != short_username THEN
                SIGNAL SQLSTATE '45067'
                SET MESSAGE_TEXT = 'You can only update your own lists';
            END IF;
        END
    ");

    $db->exec("DROP TRIGGER IF EXISTS trg_list_delete_check");
    $db->exec("
        CREATE TRIGGER trg_list_delete_check
        BEFORE DELETE ON list
        FOR EACH ROW
        BEGIN
            DECLARE current_mysql_user VARCHAR(32);
            DECLARE short_username VARCHAR(32);

            SET current_mysql_user = SUBSTRING_INDEX(USER(), '@', 1);
            SET short_username = SUBSTRING(OLD.username, 1, 32);

            IF current_mysql_user != 'project_db_connection'
               AND current_mysql_user != 'admin'
               AND current_mysql_user != short_username THEN
                SIGNAL SQLSTATE '45067'
                SET MESSAGE_TEXT = 'You can only delete your own lists';
            END IF;
        END
    ");
}

function grantPermissionsToExistingUsers() {
    global $db;

    $stmt = $db->query("SELECT username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($users as $username) {
        createMySQLUserAccount($username);
    }
}

function dropMySQLUserAccount($username) {
    global $db;

    $short_username = substr($username, 0, 32);

    try {
        $db->exec("DROP USER IF EXISTS '{$short_username}'@'localhost'");
        $db->exec("FLUSH PRIVILEGES");
    } catch (PDOException $e) {
        error_log("MySQL user deletion error for {$short_username}: " . $e->getMessage());
    }
}

function dropAllMySQLUserAccounts() {
    global $db;

    try {
        $stmt = $db->query("SELECT username FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $username) {
            dropMySQLUserAccount($username);
        }
    } catch (PDOException $e) {
        error_log("Error while dropping MySQL users: " . $e->getMessage());
    }
}
?>
