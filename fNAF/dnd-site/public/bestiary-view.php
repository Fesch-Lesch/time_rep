<?php
$pageTitle = 'Бестиарий';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$pdo = getSecondDBConnection();

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';

$sql = "SELECT * FROM BESTIARY WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}

if ($type) {
    $sql .= " AND type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY challenge_rating, name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$creatures = $stmt->fetchAll();

$types = $pdo->query("SELECT DISTINCT type FROM BESTIARY ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);

$selectedCreature = null;
if (isset($_GET['creature_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM BESTIARY WHERE creature_id = ?");
    $stmt->execute([(int)$_GET['creature_id']]);
    $selectedCreature = $stmt->fetch();
}

$sizeLabels = [
    'tiny' => 'Крошечный',
    'small' => 'Маленький',
    'medium' => 'Средний',
    'large' => 'Большой',
    'huge' => 'Огромный',
    'gargantuan' => 'Исполинский'
];
?>

<h1>📖 Бестиарий</h1>

<div class="card">
    <form method="GET">
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label for="search">Поиск по имени:</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Введите имя существа...">
            </div>
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label for="type">Тип существа:</label>
                <select name="type" id="type">
                    <option value="">Все типы</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary">Найти</button>
                <a href="bestiary-view.php" class="btn btn-secondary" style="margin-left: 10px;">Сбросить</a>
            </div>
        </div>
    </form>
</div>

<?php if ($selectedCreature): ?>
<div class="card">
    <h2><?= htmlspecialchars($selectedCreature['name']) ?></h2>
    <p><em><?= $sizeLabels[$selectedCreature['size']] ?? $selectedCreature['size'] ?> <?= htmlspecialchars($selectedCreature['type']) ?>, <?= htmlspecialchars($selectedCreature['alignment'] ?? 'без мировоззрения') ?></em></p>
    
    <hr style="margin: 15px 0;">
    
    <p><strong>Класс доспеха:</strong> <?= $selectedCreature['armor_class'] ?></p>
    <p><strong>Хиты:</strong> <?= $selectedCreature['hp'] ?></p>
    <p><strong>Скорость:</strong> <?= htmlspecialchars($selectedCreature['speed']) ?></p>
    
    <hr style="margin: 15px 0;">
    
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-value"><?= $selectedCreature['strength'] ?></div>
            <div class="stat-label">СИЛ</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedCreature['dexterity'] ?></div>
            <div class="stat-label">ЛОВ</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedCreature['constitution'] ?></div>
            <div class="stat-label">ТЕЛ</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedCreature['intelligence'] ?></div>
            <div class="stat-label">ИНТ</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedCreature['wisdom'] ?></div>
            <div class="stat-label">МДР</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $selectedCreature['charisma'] ?></div>
            <div class="stat-label">ХАР</div>
        </div>
    </div>
    
    <hr style="margin: 15px 0;">
    
    <?php if (!empty($selectedCreature['damage_vulnerabilities'])): ?>
        <p><strong>Уязвимости:</strong> <?= htmlspecialchars($selectedCreature['damage_vulnerabilities']) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['damage_resistances'])): ?>
        <p><strong>Сопротивления:</strong> <?= htmlspecialchars($selectedCreature['damage_resistances']) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['damage_immunities'])): ?>
        <p><strong>Иммунитеты к урону:</strong> <?= htmlspecialchars($selectedCreature['damage_immunities']) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['condition_immunities'])): ?>
        <p><strong>Иммунитеты к состояниям:</strong> <?= htmlspecialchars($selectedCreature['condition_immunities']) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['senses'])): ?>
        <p><strong>Чувства:</strong> <?= htmlspecialchars($selectedCreature['senses']) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['languages'])): ?>
        <p><strong>Языки:</strong> <?= htmlspecialchars($selectedCreature['languages']) ?></p>
    <?php endif; ?>
    
    <p><strong>Опасность:</strong> <?= $selectedCreature['challenge_rating'] ?> (<?= $selectedCreature['experience_points'] ?> XP)</p>
    
    <?php if (!empty($selectedCreature['special_abilities'])): ?>
        <hr style="margin: 15px 0;">
        <h3>Особые способности</h3>
        <p><?= nl2br(htmlspecialchars($selectedCreature['special_abilities'])) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['actions'])): ?>
        <hr style="margin: 15px 0;">
        <h3>Действия</h3>
        <p><?= nl2br(htmlspecialchars($selectedCreature['actions'])) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['legendary_actions'])): ?>
        <hr style="margin: 15px 0;">
        <h3>Легендарные действия</h3>
        <p><?= nl2br(htmlspecialchars($selectedCreature['legendary_actions'])) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['description'])): ?>
        <hr style="margin: 15px 0;">
        <h3>Описание</h3>
        <p><?= nl2br(htmlspecialchars($selectedCreature['description'])) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($selectedCreature['habitat'])): ?>
        <p><strong>Среда обитания:</strong> <?= htmlspecialchars($selectedCreature['habitat']) ?></p>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="bestiary-view.php" class="btn btn-secondary">← Назад к списку</a>
    </div>
</div>

<?php else: ?>

<div class="card">
    <p>Найдено существ: <strong><?= count($creatures) ?></strong></p>
</div>

<table>
    <thead>
        <tr>
            <th>Имя</th>
            <th>Тип</th>
            <th>Размер</th>
            <th>Опасность</th>
            <th>HP</th>
            <th>AC</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($creatures)): ?>
            <tr>
                <td colspan="7" style="text-align: center;">Существа не найдены</td>
            </tr>
        <?php else: ?>
            <?php foreach ($creatures as $creature): ?>
            <tr>
                <td><?= htmlspecialchars($creature['name']) ?></td>
                <td><?= htmlspecialchars($creature['type']) ?></td>
                <td><?= $sizeLabels[$creature['size']] ?? $creature['size'] ?></td>
                <td><?= $creature['challenge_rating'] ?></td>
                <td><?= $creature['hp'] ?></td>
                <td><?= $creature['armor_class'] ?></td>
                <td>
                    <a href="bestiary-view.php?creature_id=<?= $creature['creature_id'] ?>" class="btn btn-primary">Подробнее</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>