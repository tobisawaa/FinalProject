<?php
require_once __DIR__ . '/../../src/classes/Auth.php';
require_once __DIR__ . '/../../src/classes/Activity.php';

$auth = new Auth();


if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$activity = new Activity();
$id = $_GET['id'] ?? 0;

$activityData = $activity->getById($id);
if (!$activityData || (!$auth->isAdmin() && $activityData['user_id'] != $user['id'])) {
    header('Location: index.php');
    exit;
}

$activity->delete($id);
header('Location: index.php');
exit;
