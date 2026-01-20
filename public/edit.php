<?php
session_start();

$PASSWORD_HASH = '$2y$10$aWjYPWsDb.kTFD38XwF9quLatC6Y928THLhTgJpmQhkiNPqmq9EnS';
$FILE = __DIR__ . '/bugs2.md';

/* ---------- LOGIN ---------- */
if (isset($_POST['password'])) {
    if (password_verify($_POST['password'], $PASSWORD_HASH)) {
        $_SESSION['authorized'] = true;
    } else {
        $error = "Wrong password";
    }
}

/* ---------- LOGOUT ---------- */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: edit.php");
    exit;
}

/* ---------- SAVE ---------- */
if (isset($_SESSION['authorized']) && isset($_POST['content'])) {
    // Safety limits
    if (strlen($_POST['content']) > 20000) {
        die("File too large");
    }

    file_put_contents($FILE, $_POST['content'], LOCK_EX);
    $saved = true;
}

/* ---------- LOAD FILE ---------- */
$content = file_exists($FILE) ? file_get_contents($FILE) : '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit File</title>
</head>
<body>

<?php if (!isset($_SESSION['authorized'])): ?>

    <h3>Enter Password</h3>
    <?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>

    <form method="POST">
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>

<?php else: ?>

    <p>
        <a href="?logout=1">Logout</a>
    </p>

    <?php if (!empty($saved)) echo "<p style='color:green'>Saved</p>"; ?>

    <form method="POST">
        <textarea name="content" rows="25" cols="100"><?= htmlspecialchars($content) ?></textarea>
        <br><br>
        <button type="submit">Save</button>
    </form>

<?php endif; ?>

</body>
</html>
