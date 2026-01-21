<?php
global $conn;
require_once __DIR__ . '/cfg.php';

if (($_SESSION['zalogowany'] ?? false) !== true) {
    header('Location: admin/admin.php');
    exit;
}

class ProductManager
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, error: string|null}
     */
    public function listProducts(): array
    {
        $sql = "SELECT id, title, description, category, price_net, tax_rate, stock_qty, availability_status, expires_at, gabarit, image_url, created_at
        FROM products
        ORDER BY id DESC";
        $result = mysqli_query($this->conn, $sql);
        if ($result === false) {
            return ['data' => [], 'error' => 'Błąd pobierania produktów: ' . mysqli_error($this->conn)];
        }

        return ['data' => mysqli_fetch_all($result, MYSQLI_ASSOC), 'error' => null];
    }

    public function getProduct(int $id): ?array
    {
        $stmt = mysqli_prepare($this->conn, 'SELECT id, title, description, category, price_net, tax_rate, stock_qty, availability_status, expires_at, gabarit, image_url
                                    FROM products
                                    WHERE id = ? LIMIT 1');
        if ($stmt === false) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success: bool, errors: array<int, string>}
     */
    public function createProduct(array $payload): array
    {
        [$product, $errors] = $this->normalizePayload($payload);
        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = mysqli_prepare(
            $this->conn,
            'INSERT INTO products (title, description, category, price_net, tax_rate, stock_qty, availability_status, expires_at, gabarit, image_url, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        if ($stmt === false) {
            return ['success' => false, 'errors' => ['Błąd przygotowania zapytania: ' . mysqli_error($this->conn)]];
        }

        mysqli_stmt_bind_param(
            $stmt,
            'sssddissss',
            $product['title'],
            $product['description'],
            $product['category'],
            $product['price_net'],
            $product['tax_rate'],
            $product['stock_qty'],
            $product['availability_status'],
            $product['expires_at'],
            $product['gabarit'],
            $product['image_url']
        );


        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) {
            $errors[] = 'Nie udało się dodać produktu: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);

        return ['success' => $ok, 'errors' => $errors];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success: bool, errors: array<int, string>}
     */
    public function updateProduct(int $id, array $payload): array
    {
        [$product, $errors] = $this->normalizePayload($payload);
        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = mysqli_prepare(
            $this->conn,
            'UPDATE products SET title = ?, description = ?, category = ?, price_net = ?, tax_rate = ?, stock_qty = ?, availability_status = ?, expires_at = ?, gabarit = ?, image_url = ? WHERE id = ? LIMIT 1'
        );

        if ($stmt === false) {
            return ['success' => false, 'errors' => ['Błąd przygotowania zapytania: ' . mysqli_error($this->conn)]];
        }

        mysqli_stmt_bind_param(
            $stmt,
            'sssddissssi',
            $product['title'],
            $product['description'],
            $product['category'],
            $product['price_net'],
            $product['tax_rate'],
            $product['stock_qty'],
            $product['availability_status'],
            $product['expires_at'],
            $product['gabarit'],
            $product['image_url'],
            $id
        );


        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) {
            $errors[] = 'Nie udało się zaktualizować produktu: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);

        return ['success' => $ok, 'errors' => $errors];
    }

    public function deleteProduct(int $id): array
    {
        $stmt = mysqli_prepare($this->conn, 'DELETE FROM products WHERE id = ? LIMIT 1');
        if ($stmt === false) {
            return ['success' => false, 'errors' => ['Błąd przygotowania zapytania: ' . mysqli_error($this->conn)]];
        }

        mysqli_stmt_bind_param($stmt, 'i', $id);
        $ok = mysqli_stmt_execute($stmt);
        $errors = [];
        if (!$ok) {
            $errors[] = 'Nie udało się usunąć produktu: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);

        return ['success' => $ok, 'errors' => $errors];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function normalizePayload(array $payload): array
    {
        $title = trim($payload['title'] ?? '');
        $description = trim($payload['description'] ?? '');
        $category = trim($payload['category'] ?? '');
        $priceNet = (float)str_replace(',', '.', $payload['price_net'] ?? 0);
        $taxRate = (float)str_replace(',', '.', $payload['tax_rate'] ?? 0);
        $stockQty = max(0, (int)($payload['stock_qty'] ?? 0));
        $status = strtoupper(trim($payload['availability_status'] ?? 'ACTIVE'));
        $expiresAt = trim($payload['expires_at'] ?? '');
        $gabarit = trim($payload['gabarit'] ?? '');
        $imageUrl = trim($payload['image_url'] ?? '');


        $allowedStatuses = ['ACTIVE', 'OUT_OF_STOCK', 'DISCONTINUED'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'ACTIVE';
        }

        $errors = [];
        if ($title === '') {
            $errors[] = 'Tytuł produktu nie może być pusty.';
        }
        if ($description === '') {
            $errors[] = 'Opis produktu nie może być pusty.';
        }
        if ($category === '') {
            $errors[] = 'Kategoria nie może być pusta.';
        }
        if ($priceNet < 0) {
            $errors[] = 'Cena netto nie może być ujemna.';
        }
        if ($taxRate < 0) {
            $errors[] = 'Stawka podatku nie może być ujemna.';
        }
        if ($expiresAt !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiresAt)) {
            $errors[] = 'Data ważności musi być w formacie RRRR-MM-DD lub pusta.';
        }

        return [
            [
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'price_net' => $priceNet,
                'tax_rate' => $taxRate,
                'stock_qty' => $stockQty,
                'availability_status' => $status,
                'expires_at' => $expiresAt !== '' ? $expiresAt : null,
                'gabarit' => $gabarit,
                'image_url' => $imageUrl,

            ],
            $errors,
        ];
    }
}

$manager = new ProductManager($conn);
$errors = [];
$messages = [];
$prefill = [];
$editingProduct = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $manager->createProduct($_POST);
        if ($result['success']) {
            $messages[] = 'Produkt został dodany poprawnie.';
            $prefill = [];
        }
        $errors = $result['errors'];
        $prefill = $_POST;
    } elseif ($action === 'update') {
        $productId = (int)($_POST['id'] ?? 0);
        $result = $manager->updateProduct($productId, $_POST);
        if ($result['success']) {
            $messages[] = 'Produkt został zaktualizowany.';
            $editingProduct = null;
            $prefill = [];
        }

        $errors = $result['errors'];
        $prefill = $_POST;
        $editingProduct = $manager->getProduct($productId);
    } elseif ($action === 'delete') {
        $productId = (int)($_POST['id'] ?? 0);
        $result = $manager->deleteProduct($productId);
        if ($result['success']) {
            $messages[] = 'Produkt został usunięty.';
        }

        $errors = $result['errors'];
    }
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editingProduct = $manager->getProduct($editId);
    if ($editingProduct) {
        $prefill = $editingProduct;
    } else {
        $errors[] = 'Nie znaleziono produktu o podanym ID.';
    }
}

if (isset($_GET['added'])) {
    $messages[] = 'Produkt został dodany poprawnie.';
}
if (isset($_GET['updated'])) {
    $messages[] = 'Produkt został zaktualizowany.';
}
if (isset($_GET['deleted'])) {
    $messages[] = 'Produkt został usunięty.';
}

$products = $manager->listProducts();
if ($products['error']) {
    $errors[] = $products['error'];
}

function fieldValue(array $prefill, string $key): string
{
    return htmlspecialchars((string)($prefill[$key] ?? ''));
}

$allowedStatuses = ['ACTIVE' => 'Aktywny', 'OUT_OF_STOCK' => 'Brak na stanie', 'DISCONTINUED' => 'Wycofany'];
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie produktami</title>
    <link rel="stylesheet" href="175675/products.css">

</head>
<body>
<div class="container products-page">
    <h1>System zarządzania produktami</h1>

    <?php if ($errors): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($messages): ?>
        <div class="messages">
            <ul>
                <?php foreach ($messages as $message): ?>
                    <li><?= htmlspecialchars($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-section">
        <h2><?= $editingProduct ? 'Edytuj produkt' : 'Dodaj nowy produkt' ?></h2>
        <form method="post">
            <?php if ($editingProduct): ?>
                <input type="hidden" name="id" value="<?= (int)$editingProduct['id'] ?>">
                <input type="hidden" name="action" value="update">
            <?php else: ?>
                <input type="hidden" name="action" value="create">
            <?php endif; ?>

            <div class="form-row">
                <label>Tytuł</label>
                <input type="text" name="title" value="<?= fieldValue($prefill, 'title') ?>" required>
            </div>

            <div class="form-row">
                <label>Opis</label>
                <textarea name="description" rows="5" required><?= fieldValue($prefill, 'description') ?></textarea>
            </div>

            <div class="form-row">
                <label>Kategoria</label>
                <input type="text" name="category" value="<?= fieldValue($prefill, 'category') ?>" required>
            </div>

            <div class="form-row">
                <label>Cena netto</label>
                <input type="number" name="price_net" step="0.01" min="0" value="<?= fieldValue($prefill, 'price_net') ?>" required>
            </div>

            <div class="form-row">
                <label>Stawka podatku (%)</label>
                <input type="number" name="tax_rate" step="0.01" min="0" value="<?= fieldValue($prefill, 'tax_rate') ?>" required>
            </div>

            <div class="form-row">
                <label>Ilość na stanie</label>
                <input type="number" name="stock_qty" min="0" value="<?= fieldValue($prefill, 'stock_qty') ?>" required>
            </div>

            <div class="form-row">
                <label>Status dostępności</label>
                <select name="availability_status">
                    <?php foreach ($allowedStatuses as $key => $label): ?>
                        <option value="<?= $key ?>" <?= (isset($prefill['availability_status']) && strtoupper($prefill['availability_status']) === $key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label>Data wygaśnięcia (opcjonalnie)</label>
                <input type="date" name="expires_at" value="<?= fieldValue($prefill, 'expires_at') ?>">
            </div>

            <div class="form-row">
                <label>Gabarit</label>
                <input type="text" name="gabarit" value="<?= fieldValue($prefill, 'gabarit') ?>">
            </div>

            <div class="form-row">
                <label>Zdjęcie (URL)</label>
                <input type="text" name="image_url" value="<?= fieldValue($prefill, 'image_url') ?>">
            </div>


            <div class="form-row">
                <button type="submit"><?= $editingProduct ? 'Zapisz zmiany' : 'Dodaj produkt' ?></button>
                <?php if ($editingProduct): ?>
                    <a href="products.php" style="margin-left: 10px;">Anuluj edycję</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h2>Lista produktów</h2>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Tytuł</th>
            <th>Kategoria</th>
            <th>Cena netto</th>
            <th>Podatek</th>
            <th>Stan</th>
            <th>Status</th>
            <th>Wygasa</th>
            <th>Gabarit</th>
            <th>Zdjęcie</th>
            <th>Akcje</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products['data'] as $product): ?>
            <tr>
                <td><?= (int)$product['id'] ?></td>
                <td><?= htmlspecialchars($product['title']) ?></td>
                <td><?= htmlspecialchars($product['category']) ?></td>
                <td><?= number_format((float)$product['price_net'], 2, ',', ' ') ?> zł</td>
                <td><?= number_format((float)$product['tax_rate'], 2, ',', ' ') ?>%</td>
                <td><?= (int)$product['stock_qty'] ?></td>
                <td><?= htmlspecialchars($product['availability_status']) ?></td>
                <td><?= htmlspecialchars($product['expires_at'] ?? '-') ?></td>
                <td><?= htmlspecialchars($product['gabarit'] ?? '-') ?></td>
                <td>
                    <?php if (!empty($product['image_url'])): ?>
                        <a href="<?= htmlspecialchars($product['image_url']) ?>" target="_blank">Podgląd</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>

                <td>
                    <a href="products.php?edit=<?= (int)$product['id'] ?>">Edytuj</a>
                    <form method="post" class="inline" onsubmit="return confirm('Na pewno usunąć produkt?');">
                        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit">Usuń</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>