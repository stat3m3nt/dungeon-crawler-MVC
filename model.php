<?php
/**
 * This file returns a PDO connection to the database.
 *
 * Update the host, dbname, username, and password to match your environment.
 *
 * @return PDO
 */
function db_connect(): PDO {
    $host = "localhost";
    $dbname = "moore";
    $username = "root";
    $password = "";

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
}

/**
 * Starts the game session.
 *
 * @return void
 */
function start_game(): void {
    $_SESSION['started'] = true;
    $_SESSION['room_id'] = 22;
    $_SESSION['orientation'] = 'N';
}

/**
 * Returns true if the game has started.
 *
 * @return bool
 */
function game_started(): bool {
    return isset($_SESSION['started']) && $_SESSION['started'] === true;
}

/**
 * Returns the current room id.
 *
 * @return int
 */
function get_current_room_id(): int {
    return isset($_SESSION['room_id']) ? (int)$_SESSION['room_id'] : 22;
}

/**
 * Returns the player's orientation.
 *
 * @return string
 */
function get_orientation(): string {
    return $_SESSION['orientation'] ?? 'N';
}

/**
 * Turns the player left.
 *
 * @return void
 */
function turn_left(): void {
    switch (get_orientation()) {
        case 'N':
            $_SESSION['orientation'] = 'W';
            break;
        case 'W':
            $_SESSION['orientation'] = 'S';
            break;
        case 'S':
            $_SESSION['orientation'] = 'E';
            break;
        case 'E':
            $_SESSION['orientation'] = 'N';
            break;
    }
}

/**
 * Turns the player right.
 *
 * @return void
 */
function turn_right(): void {
    switch (get_orientation()) {
        case 'N':
            $_SESSION['orientation'] = 'E';
            break;
        case 'E':
            $_SESSION['orientation'] = 'S';
            break;
        case 'S':
            $_SESSION['orientation'] = 'W';
            break;
        case 'W':
            $_SESSION['orientation'] = 'N';
            break;
    }
}

/**
 * Returns a room record by room id.
 *
 * @param int $room_id
 * @return array|null
 */
function get_room(int $room_id): ?array {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = :room_id");
    $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    return $room ?: null;
}

/**
 * Returns the exits relative to the player's orientation.
 *
 * @param array $room
 * @param string $orientation
 * @return array
 */
function get_relative_exits(array $room, string $orientation): array {
    switch ($orientation) {
        case 'N':
            return [
                'left' => $room['west'],
                'forward' => $room['north'],
                'right' => $room['east']
            ];

        case 'E':
            return [
                'left' => $room['north'],
                'forward' => $room['east'],
                'right' => $room['south']
            ];

        case 'S':
            return [
                'left' => $room['east'],
                'forward' => $room['south'],
                'right' => $room['west']
            ];

        case 'W':
            return [
                'left' => $room['south'],
                'forward' => $room['west'],
                'right' => $room['north']
            ];
    }

    return [
        'left' => null,
        'forward' => null,
        'right' => null
    ];
}

/**
 * Returns the area background filename based on available relative exits.
 *
 * @param array $room
 * @param string $orientation
 * @return string
 */
function get_dungeon_background(array $room, string $orientation): string {
    $exits = get_relative_exits($room, $orientation);
    $name = '';

    if (!is_null($exits['left'])) {
        $name .= 'L';
    }

    if (!is_null($exits['forward'])) {
        $name .= 'F';
    }

    if (!is_null($exits['right'])) {
        $name .= 'R';
    }

    if ($name === '') {
        $name = 'X';
    }

    return $name . '.png';
}

/**
 * Returns an encounter record by id.
 *
 * @param int $encounter_id
 * @return array|null
 */
function get_encounter(int $encounter_id): ?array {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM encounters WHERE id = :id");
    $stmt->bindValue(':id', $encounter_id, PDO::PARAM_INT);
    $stmt->execute();

    $encounter = $stmt->fetch(PDO::FETCH_ASSOC);
    return $encounter ?: null;
}

/**
 * Returns the encounter for a given room if one exists.
 *
 * @param array $room
 * @return array|null
 */
function get_room_encounter(array $room): ?array {
    if (!isset($room['encounter_id']) || is_null($room['encounter_id'])) {
        return null;
    }

    return get_encounter((int)$room['encounter_id']);
}

/**
 * Moves to the requested room only if it is directly forward.
 *
 * @param int $requested_room
 * @return void
 */
function go_to_room(int $requested_room): void {
    $current_room = get_room(get_current_room_id());
    if ($current_room === null) {
        return;
    }

    $exits = get_relative_exits($current_room, get_orientation());

    if (!is_null($exits['forward']) && (int)$exits['forward'] === $requested_room) {
        $_SESSION['room_id'] = $requested_room;
    }
}

/**
 * Builds template values needed by the dungeon view.
 *
 * @return array
 */
function build_dungeon_tpl(): array {
    $tpl = [];

    $room = get_room(get_current_room_id());
    if ($room === null) {
        return $tpl;
    }

    $orientation = get_orientation();
    $exits = get_relative_exits($room, $orientation);

    $tpl['forward'] = $exits['forward'];
    $tpl['dungeon_background'] = get_dungeon_background($room, $orientation);

    $encounter = get_room_encounter($room);
    if ($encounter !== null) {
        $tpl['encounter'] = $encounter;
    }

    return $tpl;
}
?>
