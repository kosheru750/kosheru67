<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/modelo/connection.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth(); // Redirige a login si no hay sesión

$user = getCurrentUser();
$user_id = (int)$user['id'];

// Obtener compras del usuario autenticado directo de MySQL
$sql = "SELECT id, invoice_number, subtotal, tax_iva, total_price, order_date, order_time, status 
        FROM purchases 
        WHERE user_id = $user_id 
        ORDER BY id DESC";
$res = mysqli_query($conn, $sql);
$compras = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['total_formatted'] = formatCOP($row['total_price']);
        $compras[] = $row;
    }
}

$fac_param = isset($_GET['fac']) ? htmlspecialchars(trim($_GET['fac'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras & Facturas - Kosheru</title>
    <script>
        (function () {
            var saved = localStorage.getItem('kosheru_theme');
            document.documentElement.setAttribute('data-theme', saved === 'dark' ? 'dark' : 'light');
        })();
    </script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/factura.css">
    <link rel="icon" href="img/logos/Isotipo transparente.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@600;800;900&display=swap" rel="stylesheet">
        <style>
      body {
                background-color: var(--color-bg-card);
                background-image: url('img/logos/fondo_claro.jpg');
                background-size: cover;
                background-position: center top;
                background-attachment: fixed;
        color: var(--color-texto-blanco);
    }

        html[data-theme="dark"] body {
                background-image: url('img/logos/fondo_oscuro.jpg');
        }

    .section-title-wrap { text-align: center; max-width: 700px; margin: 0 auto 2.5rem auto; }
    .mis-compras-page .section-title { font-size: 2.2rem; font-weight: 800; color: var(--color-texto-blanco); }
    .mis-compras-page .section-subtitle { color: var(--color-texto-suave) !important; }

        .mis-compras-page {
            max-width: 1100px;
            margin: 2rem auto 4rem;
            padding: 0 1.25rem;
        }

        .mis-compras-panel {
            background: var(--color-bg-card);
            border: 1px solid var(--color-borde-card);
            border-radius: 22px;
            box-shadow: var(--sombra-card);
            padding: 1.5rem;
            overflow-x: auto;
        }

        .mis-compras-panel table {
            width: 100%;
            border-collapse: collapse;
            color: var(--color-texto-blanco);
        }

        .mis-compras-panel thead tr {
            background: var(--color-bg-panel);
            color: var(--color-texto-blanco);
        }

        .mis-compras-panel th,
        .mis-compras-panel td {
            padding: 1rem;
            border-bottom: 1px solid var(--color-borde-card);
        }

        .mis-compras-panel tbody tr:hover {
            background: var(--color-bg-panel);
        }

        .mis-compras-search {
            background: var(--color-bg-card);
            border: 1px solid var(--color-borde-card);
            border-radius: 18px;
            box-shadow: var(--sombra-suave);
        }

        .mis-compras-search input {
            background: transparent;
            color: var(--color-texto-blanco);
        }

        .mis-compras-search input::placeholder {
            color: var(--color-texto-muted);
        }

        .mis-compras-page .btn-primary {
            background: linear-gradient(135deg, var(--color-rojo-kosheru), var(--color-rojo-oscuro));
            color: #fff;
        }

        #invoiceModalContainer {
            background: color-mix(in srgb, var(--color-rojo-oscuro) 55%, transparent);
        }

    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="mis-compras-page">
        <div class="section-title-wrap">
            <h1 class="section-title">Mis Compras</h1>
            <p class="section-subtitle" style="text-transform: none; font-size: 1rem; color: var(--color-texto-suave); margin-top: 0.3rem;">
                Historial de pedidos y consulta de Facturas Electrónicas
            </p>
        </div>

        <!-- Buscador de Factura Especifica FAC -->
        <div class="tienda-search-bar mis-compras-search" style="margin-bottom: 2.5rem;">
            <img src="img/icons/credit-card-alt.svg" alt="Buscar factura" class="search-icon" aria-hidden="true">
            <input type="text" id="inputSearchInvoice" placeholder="Consultar número de factura, ej. FAC-91622" value="<?= $fac_param ?>">
        </div>

        <!-- Historial de Compras del Usuario -->
        <div class="mis-compras-panel">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--color-dorado); margin-bottom: 1.2rem; border-bottom: 2px solid var(--color-borde-card); padding-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">
                Historial de Pedidos Realizados
            </h3>

            <?php if (empty($compras)): ?>
                <div class="empty-state" style="padding: 2.5rem 1rem;">
                    <div class="empty-state-icon"><img src="img/icons/basket.svg" alt="No hay compras" class="inline-icon" aria-hidden="true"></div>
                    <h3 class="empty-state-title">No tienes compras aún</h3>
                    <p class="empty-state-desc">Explora nuestra tienda de superalimentos y realiza tu primer pedido.</p>
                    <a href="tienda.php" class="btn-primary" style="margin-top: 1rem; inline-size: fit-content;">Ir a la Tienda</a>
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--color-bg-panel); text-transform: uppercase; font-size: 0.85rem; font-weight: 800; color: var(--color-texto-blanco);">
                            <th style="padding: 0.8rem 1rem; text-align: left;">N° Factura</th>
                            <th style="padding: 0.8rem 1rem; text-align: left;">Fecha</th>
                            <th style="padding: 0.8rem 1rem; text-align: right;">Total</th>
                            <th style="padding: 0.8rem 1rem; text-align: center;">Estado</th>
                            <th style="padding: 0.8rem 1rem; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $c): ?>
                            <tr style="border-bottom: 1px solid var(--color-borde-card);">
                                <td style="padding: 1rem; font-weight: 800; color: var(--color-naranja-brillante);"><?= htmlspecialchars($c['invoice_number']) ?></td>
                                <td style="padding: 1rem; font-size: 0.9rem; color: var(--color-texto-suave);"><?= htmlspecialchars($c['order_date']) ?></td>
                                <td style="padding: 1rem; text-align: right; font-weight: 800; color: var(--color-texto-blanco);"><?= $c['total_formatted'] ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <span class="factura-status-badge badge-<?= htmlspecialchars($c['status']) ?>">
                                        <?= strtoupper(htmlspecialchars($c['status'])) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <button type="button" class="btn-primary" style="padding: 0.4rem 1rem; font-size: 0.85rem;" onclick="KosheruMisCompras.cargarFactura('<?= htmlspecialchars($c['invoice_number']) ?>')">
                                        Ver factura
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Modal / Vista de Factura Electrónica -->
        <div id="invoiceModalContainer" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 3500; overflow-y: auto; padding: 2rem 1rem;">
            <div style="max-width: 880px; margin: 0 auto; position: relative;">
                <button type="button" onclick="document.getElementById('invoiceModalContainer').style.display='none';" style="position: absolute; right: 15px; top: 15px; z-index: 10; background: var(--color-bg-card); border-radius: 50%; width: 40px; height: 40px; font-size: 1.5rem; font-weight: bold; border: 2px solid var(--color-rojo-kosheru); color: var(--color-rojo-kosheru);">
                    &times;
                </button>
                <div id="invoiceModalContent"></div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/app.js"></script>
    <script src="js/theme.js"></script>
    <script src="js/header.js"></script>
    <script src="js/carrito.js"></script>
    <script src="js/checkout.js"></script>
    <script src="js/mis-compras.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const facParam = '<?= $fac_param ?>';
            if (facParam) {
                KosheruMisCompras.cargarFactura(facParam);
            }
        });
    </script>
</body>
</html>
