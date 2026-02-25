<?php
$pageTitle = 'Рейтинг команд';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT t.team_id, t.team_color, t.amount, t.inspiration,
           c.name AS character_name, c.class AS character_class, c.level,
           (SELECT COUNT(*) FROM STUDENTS s WHERE s.team_id = t.team_id) AS members_count
    FROM TEAMS t
    LEFT JOIN CHARACTERS c ON t.character_id = c.character_id
    ORDER BY t.amount DESC
");
$teams = $stmt->fetchAll();
?>

<h1>🏆 Рейтинг команд</h1>

<table>
    <thead>
        <tr>
            <th>Место</th>
            <th>Цвет команды</th>
            <th>Персонаж</th>
            <th>Уровень</th>
            <th>Участников</th>
            <th>Всего баллов</th>
            <th>Вдохновение</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php $place = 1; foreach ($teams as $team): ?>
        <tr>
            <td><?= $place++ ?></td>
            <td><?= htmlspecialchars($team['team_color']) ?></td>
            <td><?= htmlspecialchars($team['character_name'] ?? 'Нет персонажа') ?> 
                <?php if ($team['character_class']): ?>
                    (<?= htmlspecialchars($team['character_class']) ?>)
                <?php endif; ?>
            </td>
            <td><?= $team['level'] ?? '-' ?></td>
            <td><?= $team['members_count'] ?></td>
            <td><strong><?= $team['amount'] ?></strong></td>
            <td><?= $team['inspiration'] ?> ✨</td>
            <td>
                <a href="team-stats.php?team_id=<?= $team['team_id'] ?>" class="btn btn-primary">Подробнее</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>