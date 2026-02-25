<?php
$pageTitle = 'Главная панель';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo = getDBConnection(); // Подключение к основной БД
$pdo_bestiary = getSecondDBConnection(); // Подключение к БД с бестиарием

$teamsCount = $pdo->query("SELECT COUNT(*) FROM TEAMS")->fetchColumn();
$studentsCount = $pdo->query("SELECT COUNT(*) FROM STUDENTS")->fetchColumn();
$charactersCount = $pdo->query("SELECT COUNT(*) FROM CHARACTERS")->fetchColumn();

// ВАЖНО: используем $pdo_bestiary для запроса к BESTIARY
$creaturesCount = $pdo_bestiary->query("SELECT COUNT(*) FROM BESTIARY")->fetchColumn();
?>

<h1>Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>

<?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
    <div class="alert alert-error">У вас нет доступа к этой странице</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-item">
        <div class="stat-value"><?= $teamsCount ?></div>
        <div class="stat-label">Команд</div>
    </div>
    <div class="stat-item">
        <div class="stat-value"><?= $studentsCount ?></div>
        <div class="stat-label">Студентов</div>
    </div>
    <div class="stat-item">
        <div class="stat-value"><?= $charactersCount ?></div>
        <div class="stat-label">Персонажей</div>
    </div>
    <div class="stat-item">
        <div class="stat-value"><?= $creaturesCount ?></div>
        <div class="stat-label">Существ в бестиарии</div>
    </div>
</div>

<div class="card" style="margin-top: 30px;">
    <h2>Ваша роль: <?= ucfirst($_SESSION['role']) ?></h2>

    <?php if (isAdmin()): ?>
        <p>Вы имеете полный доступ ко всем функциям системы.</p>
        <ul>
            <li>Управление студентами</li>
            <li>Управление персонажами</li>
            <li>Редактирование бестиария</li>
            <li>Изменение баллов</li>
        </ul>
    <?php elseif (isTeacher()): ?>
        <p>Вы можете изменять баллы студентов и просматривать всю информацию.</p>
    <?php elseif (isCaptain()): ?>
        <p>Вы можете просматривать информацию о своей команде и рейтинги.</p>
        <?php if ($_SESSION['team_id']): ?>
            <p><a href="public/team-stats.php?team_id=<?= $_SESSION['team_id'] ?>" class="btn btn-primary">Моя команда</a></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>