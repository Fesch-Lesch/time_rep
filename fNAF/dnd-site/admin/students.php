<?php
$pageTitle = 'Управление студентами';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getDBConnection();
setCurrentUserForTriggers($pdo);

$message = '';
$error = '';
$editStudent = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM STUDENTS WHERE student_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editStudent = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM STUDENTS WHERE student_id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $message = 'Студент успешно удален';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $middleName = trim($_POST['middle_name']) ?: null;
    $teamId = $_POST['team_id'] ?: null;
    $score = (int)$_POST['score'];

    if (empty($firstName) || empty($lastName)) {
        $error = 'Имя и фамилия обязательны';
    } else {
        if (isset($_POST['student_id']) && $_POST['student_id']) {
            $stmt = $pdo->prepare("
                UPDATE STUDENTS 
                SET first_name = ?, last_name = ?, middle_name = ?, team_id = ?, score = ?
                WHERE student_id = ?
            ");
            $stmt->execute([$firstName, $lastName, $middleName, $teamId, $score, (int)$_POST['student_id']]);
            $message = 'Студент успешно обновлен';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO STUDENTS (first_name, last_name, middle_name, team_id, score)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$firstName, $lastName, $middleName, $teamId, $score]);
            $message = 'Студент успешно добавлен';
        }

        header('Location: students.php?success=1');
        exit;
    }
}

if (isset($_GET['success'])) {
    $message = 'Операция выполнена успешно';
}

$teams = $pdo->query("SELECT team_id, team_color FROM TEAMS ORDER BY team_color")->fetchAll();

$stmt = $pdo->query("
    SELECT s.*, t.team_color 
    FROM STUDENTS s 
    LEFT JOIN TEAMS t ON s.team_id = t.team_id 
    ORDER BY s.last_name, s.first_name
");
$students = $stmt->fetchAll();
?>

<h1>👨‍🎓 Управление студентами</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= $editStudent ? 'Редактирование студента' : 'Добавление нового студента' ?></h2>
    
    <form method="POST">
        <?php if ($editStudent): ?>
            <input type="hidden" name="student_id" value="<?= $editStudent['student_id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label for="last_name">Фамилия *</label>
                <input type="text" id="last_name" name="last_name" required maxlength="30"
                       value="<?= htmlspecialchars($editStudent['last_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="first_name">Имя *</label>
                <input type="text" id="first_name" name="first_name" required maxlength="20"
                       value="<?= htmlspecialchars($editStudent['first_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="middle_name">Отчество</label>
                <input type="text" id="middle_name" name="middle_name" maxlength="30"
                       value="<?= htmlspecialchars($editStudent['middle_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="team_id">Команда</label>
                <select name="team_id" id="team_id">
                    <option value="">Без команды</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['team_id'] ?>" 
                                <?= (isset($editStudent['team_id']) && $editStudent['team_id'] == $team['team_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($team['team_color']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="score">Баллы (монеты)</label>
                <input type="number" id="score" name="score" min="0"
                       value="<?= $editStudent['score'] ?? 0 ?>">
            </div>
        </div>
        
        <div style="margin-top: 15px;">
            <button type="submit" class="btn btn-success">
                <?= $editStudent ? 'Сохранить изменения' : 'Добавить студента' ?>
            </button>
            <?php if ($editStudent): ?>
                <a href="students.php" class="btn btn-secondary">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Список студентов</h2>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Фамилия</th>
                <th>Имя</th>
                <th>Отчество</th>
                <th>Команда</th>
                <th>Баллы</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Студенты не найдены</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= $student['student_id'] ?></td>
                    <td><?= htmlspecialchars($student['last_name']) ?></td>
                    <td><?= htmlspecialchars($student['first_name']) ?></td>
                    <td><?= htmlspecialchars($student['middle_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($student['team_color'] ?? 'Без команды') ?></td>
                    <td><?= $student['score'] ?> 🪙</td>
                    <td>
                        <a href="students.php?edit=<?= $student['student_id'] ?>" class="btn btn-primary" style="padding: 5px 10px;">Редактировать</a>
                        <a href="students.php?delete=<?= $student['student_id'] ?>" class="btn btn-danger" style="padding: 5px 10px;" 
                           onclick="return confirm('Удалить студента?')">Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>