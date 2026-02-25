<?php
$pageTitle = 'Рейтинг студентов';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT s.student_id, s.first_name, s.last_name, s.middle_name, s.score,
           t.team_color
    FROM STUDENTS s
    LEFT JOIN TEAMS t ON s.team_id = t.team_id
    ORDER BY s.score DESC
");
$students = $stmt->fetchAll();
?>

<h1>🎓 Рейтинг студентов</h1>

<table>
    <thead>
        <tr>
            <th>Место</th>
            <th>Фамилия</th>
            <th>Имя</th>
            <th>Отчество</th>
            <th>Команда</th>
            <th>Баллы (монеты)</th>
        </tr>
    </thead>
    <tbody>
        <?php $place = 1; foreach ($students as $student): ?>
        <tr>
            <td><?= $place++ ?></td>
            <td><?= htmlspecialchars($student['last_name']) ?></td>
            <td><?= htmlspecialchars($student['first_name']) ?></td>
            <td><?= htmlspecialchars($student['middle_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($student['team_color'] ?? 'Без команды') ?></td>
            <td><strong><?= $student['score'] ?> 🪙</strong></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>