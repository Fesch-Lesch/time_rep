<?php
$pageTitle = 'Статистика команды';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$pdo = getDBConnection();

$teams = $pdo->query("SELECT team_id, team_color FROM TEAMS ORDER BY team_color")->fetchAll();

$selectedTeam = null;
$teamMembers = [];

if (isset($_GET['team_id'])) {
    $teamId = (int)$_GET['team_id'];
    
    $stmt = $pdo->prepare("
        SELECT t.*, c.*
        FROM TEAMS t
        LEFT JOIN CHARACTERS c ON t.character_id = c.character_id
        WHERE t.team_id = ?
    ");
    $stmt->execute([$teamId]);
    $selectedTeam = $stmt->fetch();
    
    if ($selectedTeam) {
        $stmt = $pdo->prepare("
            SELECT * FROM STUDENTS 
            WHERE team_id = ? 
            ORDER BY score DESC
        ");
        $stmt->execute([$teamId]);
        $teamMembers = $stmt->fetchAll();
    }
}
?>

<h1>📊 Статистика команды</h1>

<div class="card">
    <form method="GET">
        <div class="form-group">
            <label for="team_id">Выберите команду:</label>
            <select name="team_id" id="team_id" onchange="this.form.submit()">
                <option value="">-- Выберите команду --</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= $team['team_id'] ?>" 
                            <?= (isset($_GET['team_id']) && $_GET['team_id'] == $team['team_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($team['team_color']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($selectedTeam): ?>

<div class="card">
    <h2>Команда: <?= htmlspecialchars($selectedTeam['team_color']) ?></h2>
    
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['amount'] ?> 🪙</div>
            <div class="stat-label">Всего баллов</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['inspiration'] ?> ✨</div>
            <div class="stat-label">Вдохновение</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= count($teamMembers) ?></div>
            <div class="stat-label">Участников</div>
        </div>
    </div>
</div>

<?php if ($selectedTeam['name']): ?>
<div class="card">
    <h2>🧙 Персонаж: <?= htmlspecialchars($selectedTeam['name']) ?></h2>
    
    <p><strong>Раса:</strong> <?= htmlspecialchars($selectedTeam['race']) ?></p>
    <p><strong>Класс:</strong> <?= htmlspecialchars($selectedTeam['class']) ?></p>
    <p><strong>Уровень:</strong> <?= $selectedTeam['level'] ?></p>
    
    <h3>Характеристики</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['hp'] ?></div>
            <div class="stat-label">HP</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['armor'] ?></div>
            <div class="stat-label">Броня</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['strength'] ?></div>
            <div class="stat-label">Сила</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['dexterity'] ?></div>
            <div class="stat-label">Ловкость</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['constitution'] ?></div>
            <div class="stat-label">Телосложение</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['intelligence'] ?></div>
            <div class="stat-label">Интеллект</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['wisdom'] ?></div>
            <div class="stat-label">Мудрость</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['charisma'] ?></div>
            <div class="stat-label">Харизма</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['initiative'] ?></div>
            <div class="stat-label">Инициатива</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedTeam['speed'] ?></div>
            <div class="stat-label">Скорость</div>
        </div>
    </div>
    
    <h3>Способности</h3>
    <ul>
        <?php if ($selectedTeam['ability1']): ?><li><?= htmlspecialchars($selectedTeam['ability1']) ?></li><?php endif; ?>
        <?php if ($selectedTeam['ability2']): ?><li><?= htmlspecialchars($selectedTeam['ability2']) ?></li><?php endif; ?>
        <?php if ($selectedTeam['ability3']): ?><li><?= htmlspecialchars($selectedTeam['ability3']) ?></li><?php endif; ?>
        <?php if (!$selectedTeam['ability1'] && !$selectedTeam['ability2'] && !$selectedTeam['ability3']): ?>
            <li><em>Нет способностей</em></li>
        <?php endif; ?>
    </ul>
    
    <h3>Предметы</h3>
    <ul>
        <?php if ($selectedTeam['item1']): ?><li><?= htmlspecialchars($selectedTeam['item1']) ?></li><?php endif; ?>
        <?php if ($selectedTeam['item2']): ?><li><?= htmlspecialchars($selectedTeam['item2']) ?></li><?php endif; ?>
        <?php if ($selectedTeam['item3']): ?><li><?= htmlspecialchars($selectedTeam['item3']) ?></li><?php endif; ?>
        <?php if (!$selectedTeam['item1'] && !$selectedTeam['item2'] && !$selectedTeam['item3']): ?>
            <li><em>Нет предметов</em></li>
        <?php endif; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <h2>👥 Участники команды</h2>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>ФИО</th>
                <th>Баллы</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($teamMembers)): ?>
                <tr>
                    <td colspan="3" style="text-align: center;">В команде нет участников</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($teamMembers as $member): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <?= htmlspecialchars($member['last_name']) ?>
                        <?= htmlspecialchars($member['first_name']) ?>
                        <?= htmlspecialchars($member['middle_name'] ?? '') ?>
                    </td>
                    <td><?= $member['score'] ?> 🪙</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>