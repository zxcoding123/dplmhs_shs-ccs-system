<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/../includes/school-calendar.php';

calendar_ensure($pdo);

$rows = calendar_rows($pdo, false, true);
$visible = array_values(array_filter($rows, fn($r) => !$r['is_hidden']));
$hidden = array_values(array_filter($rows, fn($r) => $r['is_hidden']));
$today = date('Y-m-d');

$upcoming = array_slice(array_values(array_filter($visible, fn($r) => $r['end_date'] >= $today)), 0, 8);
$holidaysAhead = count(array_filter($visible, fn($r) => $r['source'] === 'national' && $r['start_date'] >= $today));
$noClassAhead = count(array_filter($visible, fn($r) => $r['no_classes'] && $r['end_date'] >= $today));
$schoolEvents = count(array_filter($visible, fn($r) => $r['source'] === 'school'));
$edited = count(array_filter($visible, fn($r) => $r['is_modified']));

$customTypes = calendar_custom_types($visible);
$legend = array_merge(array_values(array_diff_key(CALENDAR_CATEGORIES, ['custom' => 1])), $customTypes);

$initialMonth =preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] . '-01' : $today;

$next = $upcoming[0] ?? null;
$subtitle = $next
    ? 'Next up: ' . $next['title'] . ' · ' . calendar_date_range($next['start_date'], $next['end_date'])
    : 'Holidays, DepEd dates and school activities';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>School Calendar | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        html body .cal-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1.25rem; align-items: start; }
        html body .cal-main { padding: 1rem 1.25rem 1.25rem; }
        html body .cal-side { display: grid; gap: 1.25rem; }
        html body .cal-side .ui-panel { padding: 1rem 1.15rem; }
        html body .cal-side h2 { margin: 0 0 0.75rem; font-size: 0.78rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--ui-muted); }

        /* FullCalendar, toned to the admin UI */
        html body .fc { --fc-border-color: var(--ui-line); --fc-today-bg-color: rgba(194, 167, 77, 0.12); --fc-page-bg-color: var(--ui-surface); font-size: 0.9rem; }
        html body .fc .fc-toolbar-title { font-size: 1.2rem; font-weight: 800; color: var(--ui-navy); }
        html body .fc .fc-button { background: var(--ui-surface); border: 1px solid var(--ui-line); color: var(--ui-ink); font-weight: 600; text-transform: none; box-shadow: none; }
        html body .fc .fc-button:hover { border-color: var(--ui-gold); background: var(--ui-soft); color: var(--ui-ink); }
        html body .fc .fc-button-primary:not(:disabled).fc-button-active,
        html body .fc .fc-button-primary:not(:disabled):active { background: var(--ui-navy); border-color: var(--ui-navy); color: #fff; }
        html body .fc .fc-button:focus { box-shadow: 0 0 0 3px rgba(194, 167, 77, 0.35); }
        html body .fc .fc-col-header-cell-cushion { font-size: 0.75rem; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ui-muted); text-decoration: none; }
        html body .fc .fc-daygrid-day-number { color: var(--ui-ink); text-decoration: none; font-weight: 600; }
        html body .fc .fc-daygrid-day { cursor: pointer; }
        html body .fc .fc-daygrid-day:hover { background: rgba(6, 30, 78, 0.03); }
        html body .fc .fc-day-sat, html body .fc .fc-day-sun { background: #fbfaf7; }
        html body .fc .fc-daygrid-event { border-radius: 4px; padding: 1px 4px; font-weight: 600; font-size: 0.78rem; cursor: pointer; }
        html body .fc .fc-event.is-noclass { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.14) 0 6px, transparent 6px 12px); }
        html body .fc .fc-list-event { cursor: pointer; }
        html body .fc .fc-list-day-cushion { background: var(--ui-soft); }

        /* Upcoming list */
        html body .cal-up { margin: 0; padding: 0; list-style: none; }
        html body .cal-up li { display: flex; gap: 0.7rem; padding: 0.55rem 0; border-bottom: 1px solid #f0ede4; cursor: pointer; }
        html body .cal-up li:last-child { border-bottom: 0; }
        html body .cal-up-date { flex: none; width: 2.6rem; text-align: center; line-height: 1.05; border-radius: 8px; padding: 0.3rem 0; background: var(--ui-soft); }
        html body .cal-up-date b { display: block; font-size: 1.05rem; color: var(--ui-navy); }
        html body .cal-up-date small { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ui-muted); }
        html body .cal-up-body { min-width: 0; }
        html body .cal-up-title { display: block; font-weight: 700; color: var(--ui-ink); font-size: 0.9rem; }
        html body .cal-up-meta { display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; color: var(--ui-muted); }
        html body .cal-dot { flex: none; width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

        /* Legend / filters */
        html body .cal-filter { display: flex; align-items: center; gap: 0.55rem; padding: 0.3rem 0; font-size: 0.88rem; cursor: pointer; }
        html body .cal-filter .form-check-input { margin: 0; }
        html body .cal-filter .count { margin-left: auto; font-size: 0.75rem; color: var(--ui-muted); }
        html body .cal-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem 0.75rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--ui-line); font-size: 0.78rem; color: var(--ui-muted); }
        html body .cal-legend span { display: flex; align-items: center; gap: 0.4rem; }

        /* Removed official dates */
        html body .cal-hidden { margin: 0 0 0.75rem; padding: 0; list-style: none; font-size: 0.85rem; max-height: 180px; overflow: auto; }
        html body .cal-hidden li { display: flex; justify-content: space-between; gap: 0.5rem; padding: 0.3rem 0; color: var(--ui-muted); }
        html body .cal-hidden li span:first-child { color: var(--ui-ink); text-decoration: line-through; }

        /* Modal */
        html body .cal-official { display: flex; gap: 0.6rem; align-items: flex-start; padding: 0.7rem 0.85rem; margin-bottom: 1rem; border-radius: 8px; background: rgba(47, 91, 183, 0.07); font-size: 0.85rem; color: var(--ui-ink); }
        html body .cal-official i { color: var(--ui-blue); margin-top: 0.1rem; }
        html body .cal-official.is-modified { background: rgba(194, 167, 77, 0.14); }
        html body .cal-official.is-modified i { color: var(--ui-amber); }
        html body .cal-custom { display: flex; gap: 0.75rem; align-items: flex-end; padding: 0.85rem; border: 1px dashed var(--ui-line); border-radius: 8px; background: var(--ui-soft); }
        html body .cal-custom .form-control-color { width: 3.25rem; height: calc(1.5em + 0.75rem + 2px); padding: 0.25rem; }
        html body .cal-swatches { display: flex; gap: 0.4rem; margin-top: 0.5rem; }
        html body .cal-swatch { width: 1.4rem; height: 1.4rem; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--ui-line); padding: 0; }
        html body .cal-swatch:hover, html body .cal-swatch:focus-visible { box-shadow: 0 0 0 2px var(--ui-navy); }

        @media (max-width: 1100px) {
            html body .cal-layout { grid-template-columns: 1fr; }
            html body .cal-side { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        }
        @media (max-width: 600px) {
            html body .cal-main { padding: 0.75rem; }
            html body .fc .fc-toolbar { flex-wrap: wrap; gap: 0.5rem; }
            html body .fc .fc-toolbar-title { font-size: 1rem; }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php'); ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white d-none d-md-inline"><b>DPLMHS</b> - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php'); ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
                    <?php admin_page_header('School Calendar', $subtitle, 'bi-calendar-week',
                        '<button type="button" class="btn btn-outline-secondary" data-load-presets><i class="bi bi-arrow-repeat"></i> Load official dates</button>'
                        . ' <button type="button" class="btn btn-ui-gold" data-new-event><i class="bi bi-plus-lg"></i> Add event</button>'); ?>

                    <?php admin_stat_tiles([
                        ['label' => 'Holidays ahead', 'value' => $holidaysAhead, 'icon' => 'bi-flag', 'hint' => 'National holidays from today'],
                        ['label' => 'No-class days ahead', 'value' => $noClassAhead, 'icon' => 'bi-door-closed', 'hint' => 'Holidays, breaks and suspensions'],
                        ['label' => 'School events', 'value' => $schoolEvents, 'icon' => 'bi-stars', 'hint' => 'Added by the school'],
                        ['label' => 'Adjusted official dates', 'value' => $edited, 'icon' => 'bi-pencil-square', 'hint' => $hidden ? count($hidden) . ' removed' : 'None removed', 'tone' => $edited || $hidden ? 'muted' : ''],
                    ]); ?>

                    <div class="cal-layout">
                        <section class="ui-panel cal-main">
                            <div id="calendar"></div>
                        </section>

                        <aside class="cal-side">
                            <section class="ui-panel">
                                <h2>Coming up</h2>
                                <?php if ($upcoming): ?>
                                    <ul class="cal-up">
                                        <?php foreach ($upcoming as $r):
                                            $cat = calendar_category($r);
                                            $t = strtotime($r['start_date']); ?>
                                            <li data-open-event="<?= (int) $r['id'] ?>" tabindex="0">
                                                <span class="cal-up-date"><small><?= date('M', $t) ?></small><b><?= date('j', $t) ?></b></span>
                                                <span class="cal-up-body">
                                                    <span class="cal-up-title"><?= admin_e($r['title']) ?></span>
                                                    <span class="cal-up-meta"><span class="cal-dot" style="background: <?= $cat[1] ?>"></span><?= admin_e($cat[0]) ?><?= $r['no_classes'] ? ' · No classes' : '' ?></span>
                                                    <?php if ($r['start_date'] !== $r['end_date']): ?><span class="cal-up-meta"><?= admin_e(calendar_date_range($r['start_date'], $r['end_date'])) ?></span><?php endif; ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted small mb-0">Nothing scheduled ahead. Add an event or load the official dates.</p>
                                <?php endif; ?>
                            </section>

                            <section class="ui-panel">
                                <h2>Show</h2>
                                <?php foreach (CALENDAR_SOURCES as $key => $label):
                                    $n = count(array_filter($visible, fn($r) => $r['source'] === $key)); ?>
                                    <label class="cal-filter">
                                        <input class="form-check-input" type="checkbox" value="<?= $key ?>" data-source-filter checked>
                                        <?= admin_e($label) ?>s <span class="count"><?= $n ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <div class="cal-legend" aria-label="Colour legend">
                                    <?php foreach ($legend as [$label, $color]): ?>
                                        <span><span class="cal-dot" style="background: <?= $color ?>"></span><?= admin_e($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-muted small mt-3 mb-0"><i class="bi bi-hand-index"></i> Click a day to add an event. Drag an event to move it, or drag its edge to change its length.</p>
                            </section>

                            <?php if ($hidden): ?>
                                <section class="ui-panel">
                                    <h2>Removed official dates</h2>
                                    <ul class="cal-hidden">
                                        <?php foreach ($hidden as $r): ?>
                                            <li><span><?= admin_e($r['title']) ?></span><span><?= date('M j, Y', strtotime($r['start_date'])) ?></span></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <form method="POST" action="processes/admin/calendar/presets.php">
                                        <input type="hidden" name="action" value="restore">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Restore all</button>
                                    </form>
                                </section>
                            <?php endif; ?>
                        </aside>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add / edit event -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="processes/admin/calendar/save.php" id="eventForm">
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-calendar-plus" id="eventModalIcon"></i></span>
                    <h1 class="modal-title" id="eventModalLabel">Add event</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="eventId">
                    <div class="cal-official" id="eventOfficial" hidden>
                        <i class="bi bi-patch-check-fill"></i>
                        <span id="eventOfficialText"></span>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="eventTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="eventTitle" name="title" required maxlength="150" placeholder="e.g. Foundation Day, First Quarter Exams">
                        </div>
                        <div class="col-12">
                            <label for="eventCategory" class="form-label">Type</label>
                            <select class="form-select" id="eventCategory" name="category" required>
                                <?php foreach (CALENDAR_CATEGORIES as $key => [$label]): ?>
                                    <option value="<?= $key ?>"><?= $key === 'custom' ? 'Custom… (type your own)' : admin_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12" id="customTypeFields" hidden>
                            <div class="cal-custom">
                                <div class="flex-grow-1">
                                    <label for="eventCustomLabel" class="form-label">Type name</label>
                                    <input type="text" class="form-control" id="eventCustomLabel" name="custom_label" maxlength="50" list="customTypeList" placeholder="e.g. Intramurals, PTA Meeting, Brigada Eskwela">
                                    <datalist id="customTypeList">
                                        <?php foreach ($customTypes as [$label, $color]): ?>
                                            <option value="<?= admin_e($label) ?>" data-color="<?= $color ?>"></option>
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div>
                                    <label for="eventColor" class="form-label">Colour</label>
                                    <input type="color" class="form-control form-control-color" id="eventColor" name="color" value="<?= CALENDAR_CATEGORIES['custom'][1] ?>" title="Pick a colour">
                                </div>
                            </div>
                            <div class="cal-swatches" role="group" aria-label="Quick colours">
                                <?php foreach (['#475569', '#0e7490', '#15803d', '#a16207', '#be185d', '#7c3aed', '#1d4ed8', '#b91c1c'] as $swatch): ?>
                                    <button type="button" class="cal-swatch" style="background: <?= $swatch ?>" data-swatch="<?= $swatch ?>" aria-label="Use colour <?= $swatch ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="eventStart" class="form-label">Starts</label>
                            <input type="date" class="form-control" id="eventStart" name="start_date" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="eventEnd" class="form-label">Ends</label>
                            <input type="date" class="form-control" id="eventEnd" name="end_date" required>
                        </div>
                        <div class="col-12">
                            <label for="eventDescription" class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea class="form-control" id="eventDescription" name="description" rows="3" maxlength="2000" placeholder="Details people should know"></textarea>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="eventNoClasses" name="no_classes" value="1">
                                <label class="form-check-label" for="eventNoClasses">No classes</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="eventPublic" name="is_public" value="1" checked>
                                <label class="form-check-label" for="eventPublic">Show on the public website</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger me-auto" id="eventDelete" hidden><i class="bi bi-trash"></i> <span>Delete</span></button>
                    <button type="button" class="btn btn-outline-secondary" id="eventReset" hidden><i class="bi bi-arrow-counterclockwise"></i> Reset to official</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="eventSubmit">Add event</button>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteEventForm" method="POST" action="processes/admin/calendar/delete.php" hidden>
        <input type="hidden" name="id" id="deleteEventId">
    </form>
    <form id="presetForm" method="POST" action="processes/admin/calendar/presets.php" hidden>
        <input type="hidden" name="action" id="presetAction" value="load">
        <input type="hidden" name="id" id="presetId">
    </form>

    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        const categoryNoClasses = <?= json_encode(array_map(fn($c) => $c[2], CALENDAR_CATEGORIES)) ?>;
        const modalEl = document.getElementById('eventModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const form = document.getElementById('eventForm');
        const byId = id => document.getElementById(id);

        function addDays(iso, n) {
            const d = new Date(iso + 'T00:00:00');
            d.setDate(d.getDate() + n);
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        // ---- Calendar ----
        const calendar = new FullCalendar.Calendar(byId('calendar'), {
            initialView: window.innerWidth < 600 ? 'listMonth' : 'dayGridMonth',
            initialDate: <?= json_encode($initialMonth) ?>,
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
            buttonText: { today: 'Today', month: 'Month', list: 'List' },
            height: 'auto',
            dayMaxEvents: 3,
            editable: true,
            eventDisplay: 'block',
            events: <?= json_encode(calendar_fullcalendar_events($visible), JSON_HEX_TAG) ?>,
            eventClassNames: arg => arg.event.extendedProps.noClasses ? ['is-noclass'] : [],
            eventDidMount: function (arg) {
                const p = arg.event.extendedProps;
                arg.el.title = arg.event.title + '\n' + p.categoryLabel + (p.noClasses ? ' · No classes' : '') + (p.description ? '\n' + p.description : '');
            },
            dateClick: info => openEvent(null, info.dateStr),
            eventClick: info => openEvent(info.event),
            eventDrop: moveEvent,
            eventResize: moveEvent,
        });
        calendar.render();

        function moveEvent(info) {
            const start = info.event.startStr;
            const end = info.event.end ? addDays(info.event.endStr.slice(0, 10), -1) : start;
            const body = new FormData();
            body.append('ajax', '1');
            body.append('id', info.event.id);
            body.append('start_date', start);
            body.append('end_date', end);
            fetch('processes/admin/calendar/save.php', { method: 'POST', body })
                .then(r => r.json())
                .then(function (data) {
                    if (!data.success) throw new Error(data.message);
                    info.event.setExtendedProp('startDate', start);
                    info.event.setExtendedProp('endDate', end);
                    if (info.event.extendedProps.isPreset) info.event.setExtendedProp('isModified', true);
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: info.event.title + ' moved', showConfirmButton: false, timer: 1800 });
                })
                .catch(function (err) {
                    info.revert();
                    Swal.fire('Not moved', err.message || 'The event could not be moved. Please try again.', 'error');
                });
        }

        // ---- Source filters ----
        document.querySelectorAll('[data-source-filter]').forEach(function (box) {
            box.addEventListener('change', function () {
                const shown = Array.from(document.querySelectorAll('[data-source-filter]:checked')).map(b => b.value);
                calendar.getEvents().forEach(ev => ev.setProp('display', shown.includes(ev.extendedProps.source) ? 'block' : 'none'));
            });
        });

        // ---- Add / edit modal ----
        let noClassesTouched = false;
        byId('eventNoClasses').addEventListener('change', () => { noClassesTouched = true; });
        byId('eventCategory').addEventListener('change', function () {
            if (!noClassesTouched) byId('eventNoClasses').checked = !!categoryNoClasses[this.value];
            toggleCustom();
            if (this.value === 'custom') byId('eventCustomLabel').focus();
        });

        // ---- Custom type: own name + colour ----
        const customColors = {};
        document.querySelectorAll('#customTypeList option').forEach(o => { customColors[o.value.toLowerCase()] = o.dataset.color; });
        function toggleCustom() {
            const isCustom = byId('eventCategory').value === 'custom';
            byId('customTypeFields').hidden = !isCustom;
            byId('eventCustomLabel').required = isCustom;
        }
        // Picking a type used before reuses its colour, so the same type looks the same everywhere
        byId('eventCustomLabel').addEventListener('input', function () {
            const known = customColors[this.value.trim().toLowerCase()];
            if (known) byId('eventColor').value = known;
        });
        document.querySelectorAll('[data-swatch]').forEach(b => b.addEventListener('click', () => { byId('eventColor').value = b.dataset.swatch; }));
        byId('eventStart').addEventListener('change', function () {
            if (!byId('eventEnd').value || byId('eventEnd').value < this.value) byId('eventEnd').value = this.value;
            byId('eventEnd').min = this.value;
        });

        let current = null;
        function openEvent(ev, dateStr) {
            current = ev;
            form.reset();
            noClassesTouched = !!ev;
            const p = ev ? ev.extendedProps : null;

            byId('eventId').value = ev ? ev.id : '';
            byId('eventTitle').value = ev ? ev.title : '';
            byId('eventCategory').value = p ? p.category : 'school_event';
            byId('eventStart').value = p ? p.startDate : dateStr;
            byId('eventEnd').value = p ? p.endDate : dateStr;
            byId('eventEnd').min = byId('eventStart').value;
            byId('eventDescription').value = p ? p.description : '';
            byId('eventNoClasses').checked = p ? p.noClasses : !!categoryNoClasses.school_event;
            byId('eventPublic').checked = p ? p.isPublic : true;
            byId('eventCustomLabel').value = p ? p.customLabel : '';
            byId('eventColor').value = p && p.category === 'custom' ? p.color : <?= json_encode(CALENDAR_CATEGORIES['custom'][1]) ?>;
            toggleCustom();

            byId('eventModalLabel').textContent = ev ? 'Edit ' + ev.title : 'Add event';
            byId('eventModalIcon').className = 'bi ' + (ev ? 'bi-pencil' : 'bi-calendar-plus');
            byId('eventSubmit').textContent = ev ? 'Save changes' : 'Add event';

            const official = byId('eventOfficial');
            official.hidden = !(p && p.isPreset);
            official.classList.toggle('is-modified', !!(p && p.isModified));
            if (p && p.isPreset) {
                byId('eventOfficialText').textContent = p.isModified
                    ? 'Official ' + p.sourceLabel.toLowerCase() + ', adjusted by the school. Reset it to bring back the official date.'
                    : 'Official ' + p.sourceLabel.toLowerCase() + '. You can adjust it, for example when a holiday is moved by proclamation.';
            }
            byId('eventReset').hidden = !(p && p.isPreset && p.isModified);
            byId('eventDelete').hidden = !ev;
            byId('eventDelete').querySelector('span').textContent = p && p.isPreset ? 'Remove' : 'Delete';

            modal.show();
        }
        modalEl.addEventListener('shown.bs.modal', () => byId('eventTitle').focus());

        document.querySelectorAll('[data-new-event]').forEach(b => b.addEventListener('click', () => openEvent(null, <?= json_encode($today) ?>)));
        document.querySelectorAll('[data-open-event]').forEach(function (li) {
            const open = () => { const ev = calendar.getEventById(li.dataset.openEvent); if (ev) openEvent(ev); };
            li.addEventListener('click', open);
            li.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
        });

        form.addEventListener('submit', function (e) {
            if (byId('eventEnd').value < byId('eventStart').value) {
                e.preventDefault();
                Swal.fire('Check the dates', 'The end date cannot be before the start date.', 'warning');
            }
        });

        byId('eventDelete').addEventListener('click', function () {
            const preset = current.extendedProps.isPreset;
            Swal.fire({
                title: (preset ? 'Remove ' : 'Delete ') + current.title + '?',
                text: preset
                    ? 'It will no longer show on the calendar. You can bring it back later with "Restore all".'
                    : 'This event will be deleted from the calendar.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: preset ? 'Remove' : 'Delete event',
                customClass: { popup: 'ui-swal-danger' }
            }).then(function (result) {
                if (result.isConfirmed) {
                    byId('deleteEventId').value = current.id;
                    byId('deleteEventForm').submit();
                }
            });
        });

        byId('eventReset').addEventListener('click', function () {
            Swal.fire({
                title: 'Reset ' + current.title + '?',
                text: 'Its title, dates and notes go back to the official values.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Reset'
            }).then(function (result) {
                if (result.isConfirmed) {
                    byId('presetAction').value = 'reset';
                    byId('presetId').value = current.id;
                    byId('presetForm').submit();
                }
            });
        });

        document.querySelector('[data-load-presets]').addEventListener('click', function () {
            Swal.fire({
                title: 'Load official dates?',
                html: 'Adds any Philippine holidays (2026–2027) and DepEd SY 2026–2027 dates missing from the calendar.<br><br><small class="text-muted">Dates you adjusted stay as they are. Removed dates stay removed unless you restore them.</small>',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Load dates'
            }).then(function (result) {
                if (result.isConfirmed) {
                    byId('presetAction').value = 'load';
                    byId('presetForm').submit();
                }
            });
        });
    </script>

    <?php
    $changed = (int) ($_SESSION['CALENDAR_CHANGED'] ?? 0);
    unset($_SESSION['CALENDAR_CHANGED']);
    $calendarAlerts = [
        'ADMIN_CALENDAR_ADD_SUCCESS' => ['Event added', 'It is now on the school calendar.', 'success'],
        'ADMIN_CALENDAR_UPDATE_SUCCESS' => ['Saved', 'The calendar has been updated.', 'success'],
        'ADMIN_CALENDAR_DELETE_SUCCESS' => ['Removed', 'The date is no longer on the calendar.', 'success'],
        'ADMIN_CALENDAR_RESET_SUCCESS' => ['Reset', 'The date is back to its official values.', 'success'],
        'ADMIN_CALENDAR_PRESETS_LOADED' => ['Official dates loaded', $changed . ' date' . ($changed === 1 ? '' : 's') . ' added or restored.', 'success'],
        'ADMIN_CALENDAR_PRESETS_CURRENT' => ['Already up to date', 'Every official date is already on the calendar.', 'info'],
        'ADMIN_CALENDAR_INVALID' => ['Check the details', 'Give the event a title and type (a custom type needs a name), and make sure it ends on or after its start date.', 'warning'],
        'ADMIN_CALENDAR_ERROR' => ['Error', 'The calendar could not be updated. Please try again.', 'error'],
    ];
    if (isset($_SESSION['STATUS'], $calendarAlerts[$_SESSION['STATUS']])) {
        [$title, $text, $icon] = $calendarAlerts[$_SESSION['STATUS']];
        echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
        unset($_SESSION['STATUS']);
    }
    ?>
    <?php include('processes/server/alerts.php'); ?>
</body>

</html>
