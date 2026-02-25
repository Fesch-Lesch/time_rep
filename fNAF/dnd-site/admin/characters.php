<?php
$pageTitle = 'Управление персонажами';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getDBConnection();
setCurrentUserForTriggers($pdo);

$message = '';
$error = '';
$editCharacter = null;
$viewCharacter = null;

if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("
        SELECT c.*, t.team_color
        FROM CHARACTERS c
        LEFT JOIN TEAMS t ON t.character_id = c.character_id
        WHERE c.character_id = ?
    ");
    $stmt->execute([(int)$_GET['view']]);
    $viewCharacter = $stmt->fetch();
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM CHARACTERS WHERE character_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCharacter = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE TEAMS SET character_id = NULL WHERE character_id = ?")->execute([(int)$_GET['delete']]);
    $pdo->prepare("DELETE FROM CHARACTERS WHERE character_id = ?")->execute([(int)$_GET['delete']]);
    $message = 'Персонаж успешно удален';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'race' => trim($_POST['race']),
        'class' => trim($_POST['class']),
        'level' => (int)$_POST['level'],
        'hp' => (int)$_POST['hp'],
        'armor' => (int)$_POST['armor'],
        'strength' => (int)$_POST['strength'],
        'dexterity' => (int)$_POST['dexterity'],
        'constitution' => (int)$_POST['constitution'],
        'intelligence' => (int)$_POST['intelligence'],
        'wisdom' => (int)$_POST['wisdom'],
        'charisma' => (int)$_POST['charisma'],
        'ability1' => trim($_POST['ability1']) ?: null,
        'ability2' => trim($_POST['ability2']) ?: null,
        'ability3' => trim($_POST['ability3']) ?: null,
        'item1' => trim($_POST['item1']) ?: null,
        'item2' => trim($_POST['item2']) ?: null,
        'item3' => trim($_POST['item3']) ?: null,
        'initiative' => (int)$_POST['initiative'],
        'speed' => (int)$_POST['speed']
    ];

    if (empty($data['name']) || empty($data['race']) || empty($data['class'])) {
        $error = 'Имя, раса и класс обязательны';
    } else {
        if (isset($_POST['character_id']) && $_POST['character_id']) {
            $sql = "UPDATE CHARACTERS SET 
                    name = ?, race = ?, class = ?, level = ?, hp = ?, armor = ?,
                    strength = ?, dexterity = ?, constitution = ?, intelligence = ?, wisdom = ?, charisma = ?,
                    ability1 = ?, ability2 = ?, ability3 = ?, item1 = ?, item2 = ?, item3 = ?,
                    initiative = ?, speed = ?
                    WHERE character_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($data), (int)$_POST['character_id']]);
            $message = 'Персонаж успешно обновлен';
        } else {
            $sql = "INSERT INTO CHARACTERS 
                    (name, race, class, level, hp, armor, strength, dexterity, constitution, 
                     intelligence, wisdom, charisma, ability1, ability2, ability3, 
                     item1, item2, item3, initiative, speed)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));

            $characterId = $pdo->lastInsertId();

            if (!empty($_POST['assign_team_id'])) {
                $stmt = $pdo->prepare("UPDATE TEAMS SET character_id = ? WHERE team_id = ?");
                $stmt->execute([$characterId, (int)$_POST['assign_team_id']]);
            }

            $message = 'Персонаж успешно создан';
        }

        header('Location: characters.php?success=1');
        exit;
    }
}

if (isset($_GET['success'])) {
    $message = 'Операция выполнена успешно';
}

$characters = $pdo->query("
    SELECT c.*, t.team_color, t.team_id
    FROM CHARACTERS c
    LEFT JOIN TEAMS t ON t.character_id = c.character_id
    ORDER BY c.name
")->fetchAll();

$availableTeams = $pdo->query("
    SELECT team_id, team_color
    FROM TEAMS
    WHERE character_id IS NULL
    ORDER BY team_color
")->fetchAll();

$allTeams = $pdo->query("SELECT team_id, team_color FROM TEAMS ORDER BY team_color")->fetchAll();
?>

<h1>🧙 Управление персонажами</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($viewCharacter): ?>
<div class="card">
    <h2>Просмотр персонажа: <?= htmlspecialchars($viewCharacter['name']) ?></h2>
    
    <p><strong>Команда:</strong> <?= htmlspecialchars($viewCharacter['team_color'] ?? 'Не назначен') ?></p>
    <p><strong>Раса:</strong> <?= htmlspecialchars($viewCharacter['race']) ?></p>
    <p><strong>Класс:</strong> <?= htmlspecialchars($viewCharacter['class']) ?></p>
    <p><strong>Уровень:</strong> <?= $viewCharacter['level'] ?></p>
    
    <h3>Характеристики</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['hp'] ?></div>
            <div class="stat-label">HP</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['armor'] ?></div>
            <div class="stat-label">Броня</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['strength'] ?></div>
            <div class="stat-label">Сила</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['dexterity'] ?></div>
            <div class="stat-label">Ловкость</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['constitution'] ?></div>
            <div class="stat-label">Телосложение</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['intelligence'] ?></div>
            <div class="stat-label">Интеллект</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['wisdom'] ?></div>
            <div class="stat-label">Мудрость</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['charisma'] ?></div>
            <div class="stat-label">Харизма</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['initiative'] ?></div>
            <div class="stat-label">Инициатива</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $viewCharacter['speed'] ?></div>
            <div class="stat-label">Скорость</div>
        </div>
    </div>
    
    <h3>Способности</h3>
    <ul>
        <?php if ($viewCharacter['ability1']): ?><li><?= htmlspecialchars($viewCharacter['ability1']) ?></li><?php endif; ?>
        <?php if ($viewCharacter['ability2']): ?><li><?= htmlspecialchars($viewCharacter['ability2']) ?></li><?php endif; ?>
        <?php if ($viewCharacter['ability3']): ?><li><?= htmlspecialchars($viewCharacter['ability3']) ?></li><?php endif; ?>
        <?php if (!$viewCharacter['ability1'] && !$viewCharacter['ability2'] && !$viewCharacter['ability3']): ?>
            <li><em>Нет способностей</em></li>
        <?php endif; ?>
    </ul>
    
    <h3>Предметы</h3>
    <ul>
        <?php if ($viewCharacter['item1']): ?><li><?= htmlspecialchars($viewCharacter['item1']) ?></li><?php endif; ?>
        <?php if ($viewCharacter['item2']): ?><li><?= htmlspecialchars($viewCharacter['item2']) ?></li><?php endif; ?>
        <?php if ($viewCharacter['item3']): ?><li><?= htmlspecialchars($viewCharacter['item3']) ?></li><?php endif; ?>
        <?php if (!$viewCharacter['item1'] && !$viewCharacter['item2'] && !$viewCharacter['item3']): ?>
            <li><em>Нет предметов</em></li>
        <?php endif; ?>
    </ul>
    
    <div style="margin-top: 20px;">
        <a href="characters.php" class="btn btn-secondary">← Назад к списку</a>
        <a href="characters.php?edit=<?= $viewCharacter['character_id'] ?>" class="btn btn-primary">Редактировать</a>
    </div>
</div>

<?php else: ?>

<div class="card">
    <h2><?= $editCharacter ? 'Редактирование персонажа' : 'Создание нового персонажа' ?></h2>
    
    <form method="POST">
        <?php if ($editCharacter): ?>
            <input type="hidden" name="character_id" value="<?= $editCharacter['character_id'] ?>">
        <?php endif; ?>
        
        <h3>Основная информация</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="name">Имя *</label>
                <input type="text" id="name" name="name" required maxlength="20"
                       value="<?= htmlspecialchars($editCharacter['name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="race">Раса *</label>
                <input type="text" id="race" name="race" required maxlength="20"
                       value="<?= htmlspecialchars($editCharacter['race'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="class">Класс *</label>
                <input type="text" id="class" name="class" required maxlength="20"
                       value="<?= htmlspecialchars($editCharacter['class'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="level">Уровень</label>
                <input type="number" id="level" name="level" min="0" max="20"
                       value="<?= $editCharacter['level'] ?? 1 ?>">
            </div>
            
            <?php if (!$editCharacter): ?>
            <div class="form-group">
                <label for="assign_team_id">Назначить команде</label>
                <select name="assign_team_id" id="assign_team_id">
                    <option value="">Не назначать</option>
                    <?php foreach ($availableTeams as $team): ?>
                        <option value="<?= $team['team_id'] ?>">
                            <?= htmlspecialchars($team['team_color']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <h3>Боевые характеристики</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="hp">HP</label>
                <input type="number" id="hp" name="hp" min="0"
                       value="<?= $editCharacter['hp'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="armor">Броня</label>
                <input type="number" id="armor" name="armor" min="0"
                       value="<?= $editCharacter['armor'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="initiative">Инициатива</label>
                <input type="number" id="initiative" name="initiative" min="0"
                       value="<?= $editCharacter['initiative'] ?? 0 ?>">
            </div>
            
            <div class="form-group">
                <label for="speed">Скорость</label>
                <input type="number" id="speed" name="speed" min="0"
                       value="<?= $editCharacter['speed'] ?? 30 ?>">
            </div>
        </div>
        
        <h3>Атрибуты</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="strength">Сила</label>
                <input type="number" id="strength" name="strength" min="-20" max="30"
                       value="<?= $editCharacter['strength'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="dexterity">Ловкость</label>
                <input type="number" id="dexterity" name="dexterity" min="-20" max="30"
                       value="<?= $editCharacter['dexterity'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="constitution">Телосложение</label>
                <input type="number" id="constitution" name="constitution" min="-20" max="30"
                       value="<?= $editCharacter['constitution'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="intelligence">Интеллект</label>
                <input type="number" id="intelligence" name="intelligence" min="-20" max="30"
                       value="<?= $editCharacter['intelligence'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="wisdom">Мудрость</label>
                <input type="number" id="wisdom" name="wisdom" min="-20" max="30"
                       value="<?= $editCharacter['wisdom'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="charisma">Харизма</label>
                <input type="number" id="charisma" name="charisma" min="-20" max="30"
                       value="<?= $editCharacter['charisma'] ?? 10 ?>">
            </div>
        </div>
        
        <h3>Способности</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="ability1">Способность 1</label>
                <textarea id="ability1" name="ability1" rows="2"><?= htmlspecialchars($editCharacter['ability1'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="ability2">Способность 2</label>
                <textarea id="ability2" name="ability2" rows="2"><?= htmlspecialchars($editCharacter['ability2'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="ability3">Способность 3</label>
                <textarea id="ability3" name="ability3" rows="2"><?= htmlspecialchars($editCharacter['ability3'] ?? '') ?></textarea>
            </div>
        </div>
        
        <h3>Предметы</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="item1">Предмет 1</label>
                <textarea id="item1" name="item1" rows="2"><?= htmlspecialchars($editCharacter['item1'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="item2">Предмет 2</label>
                <textarea id="item2" name="item2" rows="2"><?= htmlspecialchars($editCharacter['item2'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="item3">Предмет 3</label>
                <textarea id="item3" name="item3" rows="2"><?= htmlspecialchars($editCharacter['item3'] ?? '') ?></textarea>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-success">
                <?= $editCharacter ? 'Сохранить изменения' : 'Создать персонажа' ?>
            </button>
            <?php if ($editCharacter): ?>
                <a href="characters.php" class="btn btn-secondary">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Список персонажей</h2>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Раса</th>
                <th>Класс</th>
                <th>Уровень</th>
                <th>HP</th>
                <th>Команда</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($characters)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Персонажи не найдены</td>
                </tr>
            <?php else: ?>
                <?php foreach ($characters as $char): ?>
                <tr>
                    <td><?= $char['character_id'] ?></td>
                    <td><?= htmlspecialchars($char['name']) ?></td>
                    <td><?= htmlspecialchars($char['race']) ?></td>
                    <td><?= htmlspecialchars($char['class']) ?></td>
                    <td><?= $char['level'] ?></td>
                    <td><?= $char['hp'] ?></td>
                    <td><?= htmlspecialchars($char['team_color'] ?? 'Не назначен') ?></td>
                    <td>
                        <a href="characters.php?view=<?= $char['character_id'] ?>" class="btn btn-secondary" style="padding: 5px 10px;">Просмотр</a>
                        <a href="characters.php?edit=<?= $char['character_id'] ?>" class="btn btn-primary" style="padding: 5px 10px;">Редактировать</a>
                        <a href="characters.php?delete=<?= $char['character_id'] ?>" class="btn btn-danger" style="padding: 5px 10px;"
                           onclick="return confirm('Удалить персонажа?')">Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>