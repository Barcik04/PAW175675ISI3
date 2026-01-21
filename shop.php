<?php
global $conn;
require_once __DIR__ . '/cfg.php';

$errors = [];
$messages = [];

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * @return array<int, array<string, mixed>>
 */
function fetchActiveProducts(mysqli $conn): array
{
    $sql = "SELECT id, title, price_net, tax_rate, availability_status FROM products WHERE availability_status != 'DISCONTINUED' ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        return [];
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function findProduct(mysqli $conn, int $id): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, title, price_net, tax_rate, availability_status FROM products WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    return $product;
}

$action = $_POST['action'] ?? '';
if ($action !== '') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    switch ($action) {
        case 'add':
            $product = findProduct($conn, $productId);
            if ($product === null || $product['availability_status'] === 'DISCONTINUED') {
                $errors[] = 'Nie znaleziono produktu.';
                break;
            }

            if ($product['availability_status'] === 'OUT_OF_STOCK') {
                $errors[] = 'Produkt jest niedostępny.';
                break;
            }

            $existingQty = $_SESSION['cart'][$productId]['qty'] ?? 0;
            $_SESSION['cart'][$productId] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'price_net' => (float)$product['price_net'],
                'tax_rate' => (float)$product['tax_rate'],
                'qty' => $existingQty + $quantity,
            ];
            $messages[] = 'Dodano produkt do koszyka.';
            break;

        case 'update':
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['qty'] = $quantity;
                $messages[] = 'Zmieniono ilość produktu.';
            }
            break;

        case 'remove':
            unset($_SESSION['cart'][$productId]);
            $messages[] = 'Produkt usunięty z koszyka.';
            break;

        case 'clear':
            $_SESSION['cart'] = [];
            $messages[] = 'Koszyk został wyczyszczony.';
            break;
    }
}

$products = fetchActiveProducts($conn);
$cart = $_SESSION['cart'];

function renderMoney(float $value): string
{
    return number_format($value, 2, ',', ' ');
}
?>

<section class="shop-cart">
    <h1>Sklep — koszyk</h1>

    <?php if ($errors): ?>
        <div class="notice notice--error">

        <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($messages): ?>
        <div class="notice notice--success">
        <?php foreach ($messages as $msg): ?>
                <div><?php echo htmlspecialchars($msg, ENT_QUOTES); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2>Dostępne produkty</h2>
    <div class="table-wrap">
    <table style="width: 100%; border-collapse: collapse;">
            <thead>
            <tr style="text-align: left; border-bottom: 1px solid #ddd;">
                <th>ID</th>
                <th>Nazwa</th>
                <th>Cena netto</th>
                <th>VAT %</th>
                <th>Status</th>
                <th>Dodaj</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td><?php echo (int)$product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['title'], ENT_QUOTES); ?></td>
                    <td><?php echo renderMoney((float)$product['price_net']); ?> zł</td>
                    <td><?php echo renderMoney((float)$product['tax_rate']); ?>%</td>
                    <td>
                        <?php
                        $st = $product['availability_status'];
                        $badgeClass = ($st === 'ACTIVE') ? 'badge badge--ok' : (($st === 'OUT_OF_STOCK') ? 'badge badge--warn' : 'badge');
                        ?>
                        <span class="<?= $badgeClass ?>"><?= htmlspecialchars($st, ENT_QUOTES) ?></span>
                    </td>

                    <td>
                        <?php if ($product['availability_status'] === 'OUT_OF_STOCK'): ?>
                            Niedostępny
                        <?php else: ?>
                            <form method="post" class="row-form">
                            <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" style="width: 70px;">
                                <button type="submit">Dodaj</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <h2 style="margin-top: 30px;">Twój koszyk</h2>
    <?php if (count($cart) === 0): ?>
        <p>Koszyk jest pusty.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                <tr style="text-align: left; border-bottom: 1px solid #ddd;">
                    <th>Produkt</th>
                    <th>Ilość</th>
                    <th>Cena netto</th>
                    <th>VAT</th>
                    <th>Cena brutto</th>
                    <th>Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $totalNet = 0;
                $totalGross = 0;
                foreach ($cart as $item):
                    $lineNet = $item['price_net'] * $item['qty'];
                    $lineVat = $lineNet * ($item['tax_rate'] / 100);
                    $lineGross = $lineNet + $lineVat;
                    $totalNet += $lineNet;
                    $totalGross += $lineGross;
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td><?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?></td>
                        <td>
                            <form method="post" style="display: flex; gap: 6px; align-items: center;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo (int)$item['qty']; ?>" min="1" style="width: 70px;">
                                <button type="submit">Aktualizuj</button>
                            </form>
                        </td>
                        <td><?php echo renderMoney($lineNet); ?> zł</td>
                        <td><?php echo renderMoney($lineVat); ?> zł</td>
                        <td><?php echo renderMoney($lineGross); ?> zł</td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                                <button type="submit">Usuń</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr style="font-weight: bold;">
                    <td>Razem</td>
                    <td></td>
                    <td><?php echo renderMoney($totalNet); ?> zł</td>
                    <td><?php echo renderMoney($totalGross - $totalNet); ?> zł</td>
                    <td><?php echo renderMoney($totalGross); ?> zł</td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>

        <form method="post" style="margin-top: 12px;">
            <input type="hidden" name="action" value="clear">
            <button type="submit" style="padding: 8px 12px;">Wyczyść koszyk</button>
        </form>
    <?php endif; ?>
</section>
