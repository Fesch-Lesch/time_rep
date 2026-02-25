<?php
$pageTitle = 'Управление пользователями';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

global $db_main_config;
$pdo = getDBConnection($db_main_config);
setCurrentUserForTriggers($pdo);

$message = '';
$error = '';
$editUser = null;
$currentUserId = getCurrentUserId();

// --- Удаление пользователя ---
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId === $currentUserId) {
        $error = 'Нельзя удалить собственную учетную запись.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM USERS WHERE user_id = ?");
        $stmt->execute([$deleteId]);
        $message = 'Пользователь успешно удален';
    }
}

// --- Получение данных для редактирования ---
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM USERS WHERE user_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch();
}

// --- Обработка формы (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Новый пароль
    $confirmPassword = $_POST['confirm_password'] ?? ''; // Подтверждение пароля
    $oldPassword = $_POST['old_password'] ?? ''; // Старый пароль
    
    $isSelfUpdate = (isset($_POST['user_id']) && (int)$_POST['user_id'] === $currentUserId);

    try {
        if (empty($username) || empty($email)) {
            throw new Exception('Логин и Email обязательны для заполнения.');
        }

        // Проверка совпадения нового пароля и подтверждения (если пароль меняется)
        if (!empty($password)) {
            if ($password !== $confirmPassword) {
                throw new Exception('Новые пароли не совпадают!');
            }
        }

        if ($isSelfUpdate) {
            // === АДМИН (САМ СЕБЯ) ===
            
            $sql = "UPDATE USERS SET username = ?, email = ?";
            $params = [$username, $email];

            if (!empty($password)) {
                if (empty($oldPassword)) {
                    throw new Exception('Для смены пароля необходимо ввести СТАРЫЙ пароль.');
                }

                $stmtAuth = $pdo->prepare("SELECT password_hash FROM USERS WHERE user_id = ?");
                $stmtAuth->execute([$currentUserId]);
                $currentUserData = $stmtAuth->fetch();

                if (!password_verify($oldPassword, $currentUserData['password_hash'])) {
                    throw new Exception('Старый пароль введен неверно!');
                }

                $sql .= ", password_hash = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE user_id = ?";
            $params[] = $currentUserId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $_SESSION['username'] = $username;
            $message = 'Ваш профиль успешно обновлен';

        } else {
            // === ДРУГИЕ ПОЛЬЗОВАТЕЛИ ===
            $role = $_POST['role'];

            if ($role === 'admin') {
                throw new Exception("Нельзя создать второго администратора.");
            }

            if (isset($_POST['user_id']) && $_POST['user_id']) {
                // Обновление
                $sql = "UPDATE USERS SET username = ?, email = ?, role = ?";
                $params = [$username, $email, $role];

                if (!empty($password)) {
                    $sql .= ", password_hash = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE user_id = ?";
                $params[] = (int)$_POST['user_id'];

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = 'Пользователь обновлен';
            } else {
                // Создание
                if (empty($password)) {
                    throw new Exception('Для нового пользователя пароль обязателен');
                }
                $stmt = $pdo->prepare("INSERT INTO USERS (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $message = 'Пользователь создан';
            }
        }
        
        if (!$error) {
            if ($isSelfUpdate) {
               $editUser['username'] = $username;
               $editUser['email'] = $email;
               // Очистка полей
               $_POST['password'] = '';
               $_POST['confirm_password'] = '';
               $_POST['old_password'] = '';
            } else {
               header('Location: users.php?success=1');
               exit;
            }
        }

    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $message = 'Операция выполнена успешно';
}

$users = $pdo->query("SELECT * FROM USERS ORDER BY role, username")->fetchAll();
?>

<h1>👥 Управление пользователями</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <?php 
        $isSelf = ($editUser && $editUser['user_id'] === $currentUserId);
    ?>
    <h2><?= $editUser ? ($isSelf ? 'Редактирование моего профиля' : 'Редактирование пользователя') : 'Новый пользователь' ?></h2>
    
    <form method="POST">
        <?php if ($editUser): ?>
            <input type="hidden" name="user_id" value="<?= $editUser['user_id'] ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="username" 
                   value="<?= htmlspecialchars($editUser['username'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" 
                   value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
        </div>
        
        <hr>
        
        <!-- Смена пароля -->
        <?php if ($isSelf): ?>
            <div class="form-group" style="background: rgba(0,0,0,0.05); padding: 15px; border-radius: 5px;">
                <label style="color: #8b3a3a;">Старый пароль (для подтверждения смены)</label>
                <input type="password" name="old_password" placeholder="Введите текущий пароль">
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Новый пароль <?= $editUser ? '(оставьте пустым, если не меняете)' : '*' ?></label>
            <input type="password" name="password" <?= ($editUser) ? '' : 'required' ?>>
        </div>

        <!-- Поле подтверждения пароля (показываем всегда, когда есть поле пароля) -->
        <div class="form-group">
            <label>Подтвердите новый пароль</label>
            <input type="password" name="confirm_password" placeholder="Повторите пароль">
        </div>
        
        <hr>

        <div class="form-group">
            <label>Роль</label>
            <?php if ($isSelf): ?>
                <input type="text" value="Администратор" readonly style="opacity: 0.7; background-color: #e0e0e0;">
                <p style="font-size: 0.9em; color: #8b3a3a; margin-top: 5px;">* Вы не можете изменить свою роль.</p>
            <?php else: ?>
                <select name="role">
                    <option value="teacher" <?= ($editUser['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Учитель</option>
                    <option value="captain" <?= ($editUser['role'] ?? '') === 'captain' ? 'selected' : '' ?>>Капитан</option>
                </select>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary"><?= $editUser ? 'Сохранить' : 'Создать' ?></button>
        <?php if ($editUser): ?>
            <a href="users.php" class="btn btn-secondary">Отмена</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h2>Список пользователей</h2>
    <table>
        <thead>
            <tr>
                <th>Логин</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($u['username']) ?>
                    <?php if ($u['user_id'] == $currentUserId) echo " <strong>(Вы)</strong>"; ?>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td>
                    <a href="users.php?edit=<?= $u['user_id'] ?>" class="btn btn-primary" style="padding: 5px 10px;">
                        <?= ($u['user_id'] == $currentUserId) ? 'Профиль' : 'Изменить' ?>
                    </a>
                    
                    <?php if ($u['user_id'] != $currentUserId): ?>
                        <a href="users.php?delete=<?= $u['user_id'] ?>" class="btn btn-danger" style="padding: 5px 10px;" 
                           onclick="return confirm('Удалить пользователя?')">Удалить</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>