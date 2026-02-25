<?php
$pageTitle = 'Изменение баллов';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'teacher']);

$pdo = getDBConnection();
setCurrentUserForTriggers($pdo);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)$_POST['student_id'];
    $action = $_POST['action'];
    $amount = (int)$_POST['amount'];
    $reason = trim($_POST['reason'] ?? '');
    
    if ($studentId <= 0 || $amount <= 0) {
        $error = 'Некорректные данные';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM STUDENTS WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            $error = 'Студент не найден';
        } else {
            if ($action === 'add') {
                $newScore = $student['score'] + $amount;
            } elseif ($action === 'subtract') {
                $newScore = max(0, $student['score'] - $amount);
            } else {
                $newScore = $amount;
            }
            
            $stmt = $pdo->prepare("UPDATE STUDENTS SET score = ? WHERE student_id = ?");
            $stmt->execute([$newScore, $studentId]);
            
            $message = "Баллы студента {$student['last_name']} {$student['first_name']} успешно обновлены. Новый баланс: {$newScore} монет";
        }
    }
}

$stmt = $pdo->query("
    SELECT s.*, t.team_color 
    FROM STUDENTS s 
    LEFT JOIN TEAMS t ON s.team_id = t.team_id 
    ORDER BY t.team_color, s.last_name
");
$students = $stmt->fetchAll();

$teams = $pdo->query("SELECT team_id, team_color FROM TEAMS ORDER BY team_color")->fetchAll();
?>

<h1>💰 Изменение баллов студентов</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Начислить/Списать баллы</h2>
    
    <form method="POST">
        <div class="form-group">
            <label for="student_id">Выберите студента:</label>
            <select name="student_id" id="student_id" required>
                <option value="">-- Выберите студента --</option>
                <?php 
                $currentTeam = null;
                foreach ($students as $student): 
                    if ($currentTeam !== $student['team_color']):
                        if ($currentTeam !== null) echo '</optgroup>';
                        $currentTeam = $student['team_color'];
                        echo '<optgroup label="' . htmlspecialchars($currentTeam ?? 'Без команды') . '">';
                    endif;
                ?>
                    <option value="<?= $student['student_id'] ?>">
                        <?= htmlspecialchars($student['last_name']) ?> 
                        <?= htmlspecialchars($student['first_name']) ?>
                        (<?= $student['score'] ?> монет)
                    </option>
                <?php endforeach; ?>
                <?php if ($currentTeam !== null) echo '</optgroup>'; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="action">Действие:</label>
            <select name="action" id="action" required>
                <option value="add">Начислить (+)</option>
                <option value="subtract">Списать (-)</option>
                <option value="set">Установить значение</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="amount">Количество монет:</label>
            <input type="number" id="amount" name="amount" min="1" required>
        </div>
        
        <div class="form-group">
            <label for="reason">Причина (необязательно):</label>
            <textarea id="reason" name="reason" rows="2" placeholder="За что начисляются/списываются баллы"></textarea>
        </div>
        
        <button type="submit" class="btn btn-success">Применить</button>
    </form>
</div>

<div class="card">
    <h2>Быстрое начисление по команде</h2>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="team_filter">Фильтр по команде:</label>
            <select id="team_filter" onchange="filterByTeam(this.value)">
                <option value="">Все команды</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= $team['team_id'] ?>"><?= htmlspecialchars($team['team_color']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    
    <table id="students-table">
        <thead>
            <tr>
                <th>ФИО</th>
                <th>Команда</th>
                <th>Текущие баллы</th>
                <th>Быстрые действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr data-team="<?= $student['team_id'] ?>">
                <td>
                    <?= htmlspecialchars($student['last_name']) ?>
                    <?= htmlspecialchars($student['first_name']) ?>
                    <?= htmlspecialchars($student['middle_name'] ?? '') ?>
                </td>
                <td><?= htmlspecialchars($student['team_color'] ?? 'Без команды') ?></td>
                <td class="score-cell"><?= $student['score'] ?> 🪙</td>
                <td>
                    <form method="POST" style="display: inline-flex; gap: 5px;">
                        <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="number" name="amount" min="1" value="1" style="width: 60px;">
                        <button type="submit" class="btn btn-success" style="padding: 5px 10px;">+</button>
                    </form>
                    <form method="POST" style="display: inline-flex; gap: 5px; margin-left: 10px;">
                        <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                        <input type="hidden" name="action" value="subtract">
                        <input type="number" name="amount" min="1" value="1" style="width: 60px;">
                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px;">−</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function filterByTeam(teamId) {
    const rows = document.querySelectorAll('#students-table tbody tr');
    rows.forEach(row => {
        if (!teamId || row.dataset.team === teamId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>