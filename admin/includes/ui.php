<?php
/**
 * Shared admin UI helpers (page header, stat tiles, cache-busted assets).
 *
 *   require_once __DIR__ . '/includes/ui.php';
 *   <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
 *   <?php admin_page_header('Teachers', 'Manage advisers and teaching staff', 'bi-person-badge', $actions); ?>
 *   <?php admin_stat_tiles([['label' => 'Total', 'value' => 12, 'icon' => 'bi-people']]); ?>
 */

if (!function_exists('admin_asset')) {
    /** Path relative to admin/ with a version that changes whenever the file does. */
    function admin_asset(string $path): string
    {
        $file = dirname(__DIR__) . '/' . $path;
        return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
    }

    function admin_e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Safe scalar lookup for header stats; returns $default when the query fails. */
    function admin_count(PDO $pdo, string $sql, array $params = [], $default = 0)
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            return $value === false || $value === null ? $default : $value;
        } catch (PDOException $e) {
            return $default;
        }
    }

    /**
     * Breadcrumb, title, subtitle and an actions slot (trusted HTML built by the page).
     */
    function admin_page_header(string $title, string $subtitle, string $icon, string $actionsHtml = ''): void
    {
        ?>
        <header class="ui-page-head">
            <div class="ui-page-head-main">
                <nav class="ui-crumbs" aria-label="Breadcrumb">
                    <a href="index.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span aria-current="page"><?= admin_e($title) ?></span>
                </nav>
                <div class="ui-page-title">
                    <span class="ui-page-icon"><i class="bi <?= admin_e($icon) ?>"></i></span>
                    <div>
                        <h1><?= admin_e($title) ?></h1>
                        <p><?= admin_e($subtitle) ?></p>
                    </div>
                </div>
            </div>
            <?php if ($actionsHtml !== ''): ?>
                <div class="ui-page-actions"><?= $actionsHtml ?></div>
            <?php endif; ?>
        </header>
        <?php
    }

    /**
     * Stat tiles. Each tile: label, value, icon, optional hint, tone (alert|ok|muted),
     * filter (clicking applies it to the page's DataTable; '' clears) and
     * filter_col (header text of the column to match exactly, else a global search).
     */
    function admin_stat_tiles(array $tiles): void
    {
        ?>
        <div class="ui-stats">
            <?php foreach ($tiles as $tile):
                $tone = $tile['tone'] ?? '';
                $tag = isset($tile['filter']) ? 'button' : 'div'; ?>
                <<?= $tag ?> class="ui-stat<?= $tone ? ' is-' . admin_e($tone) : '' ?>"
                    <?= $tag === 'button' ? 'type="button" data-ui-filter="' . admin_e($tile['filter']) . '"' : '' ?>
                    <?= isset($tile['filter_col']) ? 'data-ui-filter-col="' . admin_e($tile['filter_col']) . '"' : '' ?>>
                    <span class="ui-stat-icon"><i class="bi <?= admin_e($tile['icon'] ?? 'bi-dot') ?>"></i></span>
                    <span class="ui-stat-body">
                        <span class="ui-stat-value"><?= admin_e($tile['value']) ?></span>
                        <span class="ui-stat-label"><?= admin_e($tile['label']) ?></span>
                        <?php if (!empty($tile['hint'])): ?><span class="ui-stat-hint"><?= admin_e($tile['hint']) ?></span><?php endif; ?>
                    </span>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
