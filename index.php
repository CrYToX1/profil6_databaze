<?php

require_once __DIR__ . '/init.php';

function set_flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));

        if ($name === '') {
            set_flash('Pole nesmí být prázdné.', 'error');
        } else {
            try {
                $stmt = $db->prepare('INSERT INTO interests (name) VALUES (?)');
                $stmt->execute([$name]);
                set_flash('Zájem byl přidán.', 'success');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    set_flash('Tento zájem už existuje.', 'error');
                } else {
                    set_flash('Nastala chyba při přidávání zájmu.', 'error');
                }
            }
        }

        header('Location: index.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM interests WHERE id = ?');
            $stmt->execute([$id]);
            set_flash('Zájem byl odstraněn.', 'success');
        }

        header('Location: index.php');
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));

        if ($name === '') {
            set_flash('Pole nesmí být prázdné.', 'error');
            header('Location: index.php');
            exit;
        }

        try {
            $stmt = $db->prepare('UPDATE interests SET name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);
            set_flash('Zájem byl upraven.', 'success');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                set_flash('Tento zájem už existuje.', 'error');
            } else {
                set_flash('Nastala chyba při úpravě zájmu.', 'error');
            }
        }

        header('Location: index.php');
        exit;
    }
}

$interests = $db->query('SELECT * FROM interests ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$editingId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editingInterest = null;

if ($editingId > 0) {
    $stmt = $db->prepare('SELECT * FROM interests WHERE id = ?');
    $stmt->execute([$editingId]);
    $editingInterest = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?><!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zájmy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Zájmy</h1>

    <?php if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section>
        <h2>Přidat nový zájem</h2>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <label>
                Název:
                <input type="text" name="name" required>
            </label>
            <button type="submit">Přidat</button>
        </form>
    </section>

    <?php if ($editingInterest): ?>
        <section>
            <h2>Upravit zájem</h2>
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= (int)$editingInterest['id'] ?>">
                <label>
                    Název:
                    <input type="text" name="name" value="<?= htmlspecialchars($editingInterest['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <button type="submit">Uložit</button>
                <a href="index.php">Zrušit</a>
            </form>
        </section>
    <?php endif; ?>

    <section>
        <h2>Seznam zájmů</h2>
        <?php if (count($interests) === 0): ?>
            <p>Žádné zájmy zatím nejsou.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($interests as $interest): ?>
                    <li class="interest">
                        <span><?= htmlspecialchars($interest['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="actions">
                            <a href="?edit=<?= (int)$interest['id'] ?>">Upravit</a>
                            <form method="post" onsubmit="return confirm('Opravdu chcete odstranit tento zájem?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$interest['id'] ?>">
                                <button type="submit">Smazat</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</body>
</html>
