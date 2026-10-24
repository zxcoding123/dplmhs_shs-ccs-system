<?php
/**
 * "System developed by" credit modal. Include once per page (after the
 * credit link), then open it with data-bs-toggle="modal" data-bs-target="#developerModal".
 * Leave a social URL empty to hide it.
 */
$developer = [
    'name'   => 'Ahmad Aquino',
    'role'   => 'Full-Stack Developer',
    'avatar' => 'https://github.com/zxcoding123.png?size=240',
    'bio'    => 'Designed and built this school management system — the public website, student and faculty portals, and the admin tools behind them.',
    'socials' => [
        ['label' => 'GitHub',    'icon' => 'bi-github',    'url' => 'https://github.com/zxcoding123'],
        ['label' => 'Facebook',  'icon' => 'bi-facebook',  'url' => 'https://facebook.com/ahmadaquinodev'],
        ['label' => 'LinkedIn',  'icon' => 'bi-linkedin',  'url' => ''],
        ['label' => 'Instagram', 'icon' => 'bi-instagram', 'url' => ''],
        ['label' => 'ahmadaquino.2002@gmail.com', 'icon' => 'bi-envelope-fill', 'url' => 'mailto:ahmadaquino.2002@gmail.com'],
        ['label' => '+63 935 077 1951', 'icon' => 'bi-telephone-fill', 'url' => 'tel:+639350771951'],
    ],
];
$developer_socials = array_filter($developer['socials'], fn($s) => $s['url'] !== '');
?>
<style>
    .au-dev-link { color: inherit; font-weight: 700; text-decoration: underline; text-decoration-color: var(--au-gold); text-underline-offset: 3px; cursor: pointer; }
    .au-dev-link:hover, .au-dev-link:focus { color: var(--au-gold); }
    #developerModal .modal-content { border: 0; border-radius: 12px; overflow: hidden; }
    #developerModal .au-dev-cover { height: 96px; background: linear-gradient(135deg, var(--au-navy) 0%, var(--au-navy-soft) 60%, var(--au-gold) 160%); }
    #developerModal .au-dev-avatar { width: 112px; height: 112px; border-radius: 50%; border: 4px solid #fff; margin: -56px auto 0; display: block; object-fit: cover; background: var(--au-paper); box-shadow: var(--au-shadow); }
    #developerModal .au-dev-name { font-family: var(--au-serif); color: var(--au-navy); margin: 0.75rem 0 0.25rem; }
    #developerModal .au-dev-badge { display: inline-flex; align-items: center; gap: 0.35rem; background: rgba(194, 167, 77, 0.15); color: #7a6420; border-radius: 999px; padding: 0.25rem 0.75rem; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.02em; }
    #developerModal .au-dev-bio { color: var(--au-muted); font-size: 0.95rem; margin: 1rem 0 1.25rem; }
    #developerModal .au-dev-socials { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem; }
    #developerModal .au-dev-socials a { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.5rem 0.9rem; border: 1px solid var(--au-line); border-radius: 999px; color: var(--au-navy); font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: background 0.15s, color 0.15s, border-color 0.15s; }
    #developerModal .au-dev-socials a:hover, #developerModal .au-dev-socials a:focus { background: var(--au-navy); border-color: var(--au-navy); color: #fff; }
</style>

<div class="modal fade" id="developerModal" tabindex="-1" aria-labelledby="developerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content">
            <div class="au-dev-cover position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center px-4 pb-4 pt-0">
                <img class="au-dev-avatar" src="<?= htmlspecialchars($developer['avatar']) ?>" alt="<?= htmlspecialchars($developer['name']) ?>"
                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect width=%22100%22 height=%22100%22 fill=%22%23061E4E%22/%3E%3Ctext x=%2250%22 y=%2262%22 font-size=%2236%22 text-anchor=%22middle%22 fill=%22%23c2a74d%22 font-family=%22Georgia%22%3EAA%3C/text%3E%3C/svg%3E'">
                <h4 class="au-dev-name" id="developerModalLabel"><?= htmlspecialchars($developer['name']) ?></h4>
                <span class="au-dev-badge"><i class="bi bi-code-slash"></i> Developer · <?= htmlspecialchars($developer['role']) ?></span>
                <p class="au-dev-bio"><?= htmlspecialchars($developer['bio']) ?></p>
                <?php if ($developer_socials): ?>
                    <div class="au-dev-socials">
                        <?php foreach ($developer_socials as $s): ?>
                            <a href="<?= htmlspecialchars($s['url']) ?>"<?= str_starts_with($s['url'], 'http') ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                                <i class="bi <?= $s['icon'] ?>"></i> <?= htmlspecialchars($s['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
