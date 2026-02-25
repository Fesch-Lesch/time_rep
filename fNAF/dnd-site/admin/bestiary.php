<?php
$pageTitle = 'Редактор бестиария';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getSecondDBConnection();
$pdo_main = getDBConnection();
setCurrentUserForTriggers($pdo_main);

$message = '';
$error = '';
$editCreature = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM BESTIARY WHERE creature_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCreature = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM BESTIARY WHERE creature_id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $message = 'Существо успешно удалено из бестиария';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'type' => trim($_POST['type']),
        'size' => $_POST['size'],
        'alignment' => trim($_POST['alignment']) ?: null,
        'challenge_rating' => (float)$_POST['challenge_rating'],
        'experience_points' => (int)$_POST['experience_points'],
        'hp' => (int)$_POST['hp'],
        'armor_class' => (int)$_POST['armor_class'],
        'speed' => trim($_POST['speed']),
        'strength' => (int)$_POST['strength'],
        'dexterity' => (int)$_POST['dexterity'],
        'constitution' => (int)$_POST['constitution'],
        'intelligence' => (int)$_POST['intelligence'],
        'wisdom' => (int)$_POST['wisdom'],
        'charisma' => (int)$_POST['charisma'],
        'damage_vulnerabilities' => trim($_POST['damage_vulnerabilities']) ?: null,
        'damage_resistances' => trim($_POST['damage_resistances']) ?: null,
        'damage_immunities' => trim($_POST['damage_immunities']) ?: null,
        'condition_immunities' => trim($_POST['condition_immunities']) ?: null,
        'senses' => trim($_POST['senses']) ?: null,
        'languages' => trim($_POST['languages']) ?: null,
        'special_abilities' => trim($_POST['special_abilities']) ?: null,
        'actions' => trim($_POST['actions']) ?: null,
        'legendary_actions' => trim($_POST['legendary_actions']) ?: null,
        'description' => trim($_POST['description']) ?: null,
        'habitat' => trim($_POST['habitat']) ?: null
    ];

    if (empty($data['name']) || empty($data['type'])) {
        $error = 'Имя и тип существа обязательны';
    } else {
        if (isset($_POST['creature_id']) && $_POST['creature_id']) {
            $sql = "UPDATE BESTIARY SET 
                    name = ?, type = ?, size = ?, alignment = ?, challenge_rating = ?,
                    experience_points = ?, hp = ?, armor_class = ?, speed = ?,
                    strength = ?, dexterity = ?, constitution = ?, intelligence = ?, wisdom = ?, charisma = ?,
                    damage_vulnerabilities = ?, damage_resistances = ?, damage_immunities = ?, condition_immunities = ?,
                    senses = ?, languages = ?, special_abilities = ?, actions = ?, legendary_actions = ?,
                    description = ?, habitat = ?
                    WHERE creature_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($data), (int)$_POST['creature_id']]);
            $message = 'Существо успешно обновлено';
        } else {
            $sql = "INSERT INTO BESTIARY 
                    (name, type, size, alignment, challenge_rating, experience_points, hp, armor_class, speed,
                     strength, dexterity, constitution, intelligence, wisdom, charisma,
                     damage_vulnerabilities, damage_resistances, damage_immunities, condition_immunities,
                     senses, languages, special_abilities, actions, legendary_actions, description, habitat)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $message = 'Существо успешно добавлено в бестиарий';
        }

        header('Location: bestiary.php?success=1');
        exit;
    }
}

if (isset($_GET['success'])) {
    $message = 'Операция выполнена успешно';
}

$creatures = $pdo->query("SELECT * FROM BESTIARY ORDER BY challenge_rating, name")->fetchAll();

$sizes = ['tiny', 'small', 'medium', 'large', 'huge', 'gargantuan'];
$sizeLabels = [
    'tiny' => 'Крошечный',
    'small' => 'Маленький',
    'medium' => 'Средний',
    'large' => 'Большой',
    'huge' => 'Огромный',
    'gargantuan' => 'Исполинский'
];
?>

<h1>📖 Редактор бестиария</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= $editCreature ? 'Редактирование существа' : 'Добавление нового существа' ?></h2>
    
    <form method="POST">
        <?php if ($editCreature): ?>
            <input type="hidden" name="creature_id" value="<?= $editCreature['creature_id'] ?>">
        <?php endif; ?>
        
        <h3>Основная информация</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="name">Название *</label>
                <input type="text" id="name" name="name" required maxlength="50"
                       value="<?= htmlspecialchars($editCreature['name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="type">Тип *</label>
                <input type="text" id="type" name="type" required maxlength="30"
                       placeholder="Нежить, Зверь, Гуманоид..."
                       value="<?= htmlspecialchars($editCreature['type'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="size">Размер</label>
                <select name="size" id="size">
                    <?php foreach ($sizes as $size): ?>
                        <option value="<?= $size ?>" 
                                <?= (isset($editCreature['size']) && $editCreature['size'] === $size) ? 'selected' : '' ?>>
                            <?= $sizeLabels[$size] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="alignment">Мировоззрение</label>
                <input type="text" id="alignment" name="alignment" maxlength="30"
                       placeholder="Хаотично-злой, Законно-добрый..."
                       value="<?= htmlspecialchars($editCreature['alignment'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="challenge_rating">Уровень опасности</label>
                <input type="number" id="challenge_rating" name="challenge_rating" min="0" max="30" step="0.125"
                       value="<?= $editCreature['challenge_rating'] ?? 0 ?>">
            </div>
            
            <div class="form-group">
                <label for="experience_points">Опыт (XP)</label>
                <input type="number" id="experience_points" name="experience_points" min="0"
                       value="<?= $editCreature['experience_points'] ?? 0 ?>">
            </div>
        </div>
        
        <h3>Боевые характеристики</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="hp">Хиты (HP)</label>
                <input type="number" id="hp" name="hp" min="1"
                       value="<?= $editCreature['hp'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="armor_class">Класс доспеха (AC)</label>
                <input type="number" id="armor_class" name="armor_class" min="0"
                       value="<?= $editCreature['armor_class'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="speed">Скорость</label>
                <input type="text" id="speed" name="speed" maxlength="100"
                       placeholder="30 ft., fly 60 ft."
                       value="<?= htmlspecialchars($editCreature['speed'] ?? '30 ft.') ?>">
            </div>
        </div>
        
        <h3>Атрибуты</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="strength">Сила</label>
                <input type="number" id="strength" name="strength" min="1" max="30"
                       value="<?= $editCreature['strength'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="dexterity">Ловкость</label>
                <input type="number" id="dexterity" name="dexterity" min="1" max="30"
                       value="<?= $editCreature['dexterity'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="constitution">Телосложение</label>
                <input type="number" id="constitution" name="constitution" min="1" max="30"
                       value="<?= $editCreature['constitution'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="intelligence">Интеллект</label>
                <input type="number" id="intelligence" name="intelligence" min="1" max="30"
                       value="<?= $editCreature['intelligence'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="wisdom">Мудрость</label>
                <input type="number" id="wisdom" name="wisdom" min="1" max="30"
                       value="<?= $editCreature['wisdom'] ?? 10 ?>">
            </div>
            
            <div class="form-group">
                <label for="charisma">Харизма</label>
                <input type="number" id="charisma" name="charisma" min="1" max="30"
                       value="<?= $editCreature['charisma'] ?? 10 ?>">
            </div>
        </div>
        
        <h3>Защитные свойства</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="damage_vulnerabilities">Уязвимости к урону</label>
                <input type="text" id="damage_vulnerabilities" name="damage_vulnerabilities"
                       placeholder="огонь, холод..."
                       value="<?= htmlspecialchars($editCreature['damage_vulnerabilities'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="damage_resistances">Сопротивления к урону</label>
                <input type="text" id="damage_resistances" name="damage_resistances"
                       placeholder="дробящий, колющий..."
                       value="<?= htmlspecialchars($editCreature['damage_resistances'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="damage_immunities">Иммунитеты к урону</label>
                <input type="text" id="damage_immunities" name="damage_immunities"
                       placeholder="яд, некротический..."
                       value="<?= htmlspecialchars($editCreature['damage_immunities'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="condition_immunities">Иммунитеты к состояниям</label>
                <input type="text" id="condition_immunities" name="condition_immunities"
                       placeholder="отравление, страх..."
                       value="<?= htmlspecialchars($editCreature['condition_immunities'] ?? '') ?>">
            </div>
        </div>
        
        <h3>Чувства и языки</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="senses">Чувства</label>
                <input type="text" id="senses" name="senses"
                       placeholder="Темное зрение 60 ft., пассивное восприятие 12"
                       value="<?= htmlspecialchars($editCreature['senses'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="languages">Языки</label>
                <input type="text" id="languages" name="languages"
                       placeholder="Общий, Орочий, Гоблинский"
                       value="<?= htmlspecialchars($editCreature['languages'] ?? '') ?>">
            </div>
        </div>
        
        <h3>Способности и действия</h3>
        <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
            <div class="form-group">
                <label for="special_abilities">Особые способности</label>
                <textarea id="special_abilities" name="special_abilities" rows="4"
                          placeholder="Описание особых способностей существа..."><?= htmlspecialchars($editCreature['special_abilities'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="actions">Действия</label>
                <textarea id="actions" name="actions" rows="4"
                          placeholder="Описание действий в бою..."><?= htmlspecialchars($editCreature['actions'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="legendary_actions">Легендарные действия</label>
                <textarea id="legendary_actions" name="legendary_actions" rows="4"
                          placeholder="Легендарные действия (если есть)..."><?= htmlspecialchars($editCreature['legendary_actions'] ?? '') ?></textarea>
            </div>
        </div>
        
        <h3>Дополнительная информация</h3>
        <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
            <div class="form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Общее описание существа, его поведение, история..."><?= htmlspecialchars($editCreature['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="habitat">Среда обитания</label>
                <input type="text" id="habitat" name="habitat"
                       placeholder="Леса, пещеры, подземелья..."
                       value="<?= htmlspecialchars($editCreature['habitat'] ?? '') ?>">
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-success">
                <?= $editCreature ? 'Сохранить изменения' : 'Добавить существо' ?>
            </button>
            <?php if ($editCreature): ?>
                <a href="bestiary.php" class="btn btn-secondary">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Список существ в бестиарии</h2>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Тип</th>
                <th>Размер</th>
                <th>CR</th>
                <th>XP</th>
                <th>HP</th>
                <th>AC</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($creatures)): ?>
                <tr>
                    <td colspan="9" style="text-align: center;">Бестиарий пуст</td>
                </tr>
            <?php else: ?>
                <?php foreach ($creatures as $creature): ?>
                <tr>
                    <td><?= $creature['creature_id'] ?></td>
                    <td><?= htmlspecialchars($creature['name']) ?></td>
                    <td><?= htmlspecialchars($creature['type']) ?></td>
                    <td><?= $sizeLabels[$creature['size']] ?? $creature['size'] ?></td>
                    <td><?= $creature['challenge_rating'] ?></td>
                    <td><?= $creature['experience_points'] ?></td>
                    <td><?= $creature['hp'] ?></td>
                    <td><?= $creature['armor_class'] ?></td>
                    <td style="white-space: nowrap;">
                        <a href="../public/bestiary-view.php?creature_id=<?= $creature['creature_id'] ?>" 
                           class="btn btn-secondary" style="padding: 5px 10px;" target="_blank">Просмотр</a>
                        <a href="bestiary.php?edit=<?= $creature['creature_id'] ?>" 
                           class="btn btn-primary" style="padding: 5px 10px;">Редактировать</a>
                        <a href="bestiary.php?delete=<?= $creature['creature_id'] ?>" 
                           class="btn btn-danger" style="padding: 5px 10px;"
                           onclick="return confirm('Удалить существо?')">Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>