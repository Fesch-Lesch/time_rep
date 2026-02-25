<?php
require_once __DIR__ . '/auth.php';
$basePath = getBasePath();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'DnD System' ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="<?= $basePath ?>/dashboard.php">DnD System</a>
        </div>
        <?php if (isLoggedIn()): ?>
        <ul class="nav-menu">
            <li><a href="<?= $basePath ?>/public/team-rating.php">Рейтинг команд</a></li>
            <li><a href="<?= $basePath ?>/public/student-rating.php">Рейтинг студентов</a></li>
            <li><a href="<?= $basePath ?>/public/team-stats.php">Статистика команды</a></li>
            <li><a href="<?= $basePath ?>/public/bestiary-view.php">Бестиарий</a></li>
            
            <?php if (isTeacher() || isAdmin()): ?>
            <li><a href="<?= $basePath ?>/teacher/score.php">Изменить баллы</a></li>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <li class="dropdown">
                <a href="#">Админ ▼</a>
                <ul class="dropdown-menu">
                    <li><a href="<?= $basePath ?>/admin/users.php">Пользователи</a></li>
                    <li><a href="<?= $basePath ?>/admin/students.php">Студенты</a></li>
                    <li><a href="<?= $basePath ?>/admin/characters.php">Персонажи</a></li>
                    <li><a href="<?= $basePath ?>/admin/bestiary.php">Редактор бестиария</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
        <div class="nav-user">
            <span><?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['role'] ?>)</span>
            <a href="<?= $basePath ?>/logout.php" class="btn btn-logout">Выход</a>
        </div>
        <?php endif; ?>
    </nav>
    <main class="container"></main>