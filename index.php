<?php
/*
 * This file acts as the controller for the dungeon crawler application.
 * It processes incoming requests, calls appropriate model functions,
 * and selects the correct view to display.
 */
session_start();

require_once 'model.php';

$TPL = [];

/**
 * Used by the provided views to build base path and ensure image/link paths resolve correctly.
 */
$TPL['base_path'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($TPL['base_path'] === '.' || $TPL['base_path'] === '/') {
    $TPL['base_path'] = '';
} else {
    $TPL['base_path'] .= '/';
}

/**
 * Determine requested path.
 */
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($script_dir !== '' && $script_dir !== '/') {
    if (strpos($request_path, $script_dir) === 0) {
        $request_path = substr($request_path, strlen($script_dir));
    }
}

$request_path = trim($request_path, '/');

/**
 * Determines which action to perform based on requested path
 */
switch ($request_path) {
    case '':
        if (game_started()) {
            $TPL = array_merge($TPL, build_dungeon_tpl());
            include 'view.dungeon.php';
        } else {
            include 'view.entrance.php';
        }
        break;

    case 'start':
        start_game();
        $TPL = array_merge($TPL, build_dungeon_tpl());
        include 'view.dungeon.php';
        break;

    case 'turn/left':
        if (game_started()) {
            turn_left();
            $TPL = array_merge($TPL, build_dungeon_tpl());
            include 'view.dungeon.php';
        } else {
            include 'view.entrance.php';
        }
        break;

    case 'turn/right':
        if (game_started()) {
            turn_right();
            $TPL = array_merge($TPL, build_dungeon_tpl());
            include 'view.dungeon.php';
        } else {
            include 'view.entrance.php';
        }
        break;

    default:
        if (preg_match('#^goto/(\d+)$#', $request_path, $matches)) {
            if (game_started()) {
                $room_id = filter_var($matches[1], FILTER_VALIDATE_INT);

                if ($room_id !== false) {
                    go_to_room((int)$room_id);
                }

                $TPL = array_merge($TPL, build_dungeon_tpl());
                include 'view.dungeon.php';
            } else {
                include 'view.entrance.php';
            }
        } else {
            if (game_started()) {
                $TPL = array_merge($TPL, build_dungeon_tpl());
                include 'view.dungeon.php';
            } else {
                include 'view.entrance.php';
            }
        }
        break;
}
?>
