<?php
include('processes/server/alert_system.php');
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/school-calendar.php';

calendar_ensure($pdo);
$rows = calendar_rows($pdo, true);
$today = date('Y-m-d');
$upcoming = array_slice(array_values(array_filter($rows, fn($r) => $r['end_date'] >= $today)), 0, 10);

$page_title = 'School Calendar';
$page_description = 'Holidays, DepEd school-year dates and school activities at Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School.';
$active_page = 'calendar';
include __DIR__ . '/includes/site-header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
<style>
    .au-cal-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 2rem; align-items: start; }
    .au-cal-card { background: #fff; border: 1px solid var(--au-line); border-radius: 8px; padding: 1.25rem; box-shadow: var(--au-shadow); }
    .au-cal-card h2 { font-family: var(--au-serif); font-size: 1.35rem; color: var(--au-navy); margin: 0 0 1rem; }
    .fc { --fc-border-color: var(--au-line); --fc-today-bg-color: rgba(194, 167, 77, 0.14); font-family: var(--au-sans); }
    .fc .fc-toolbar-title { font-family: var(--au-serif); font-size: 1.4rem; color: var(--au-navy); }
    .fc .fc-button { background: #fff; border: 1px solid var(--au-line); color: var(--au-ink); font-weight: 600; text-transform: none; box-shadow: none; }
    .fc .fc-button:hover { background: var(--au-paper); border-color: var(--au-gold); color: var(--au-ink); }
    .fc .fc-button-primary:not(:disabled).fc-button-active, .fc .fc-button-primary:not(:disabled):active { background: var(--au-navy); border-color: var(--au-navy); color: #fff; }
    .fc .fc-button:focus { box-shadow: 0 0 0 3px rgba(194, 167, 77, 0.35); }
    .fc .fc-col-header-cell-cushion { font-size: 0.75rem; letter-spacing: 0.06em; text-transform: uppercase; color: var(--au-muted); text-decoration: none; }
    .fc .fc-daygrid-day-number { color: var(--au-ink); text-decoration: none; font-weight: 600; }
    .fc .fc-day-sat, .fc .fc-day-sun { background: #fbfaf7; }
    .fc .fc-daygrid-event { border-radius: 4px; padding: 1px 4px; font-size: 0.8rem; font-weight: 600; cursor: pointer; }
    .fc .fc-event.is-noclass { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.14) 0 6px, transparent 6px 12px); }
    .fc .fc-list-event { cursor: pointer; }
    .au-cal-up { list-style: none; margin: 0; padding: 0; }
    .au-cal-up li { display: flex; gap: 0.85rem; padding: 0.7rem 0; border-bottom: 1px solid var(--au-line); }
    .au-cal-up li:last-child { border-bottom: 0; }
    .au-cal-date { flex: none; width: 3rem; text-align: center; line-height: 1.05; padding: 0.4rem 0; border-radius: 6px; background: var(--au-paper); }
    .au-cal-date small { display: block; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--au-muted); }
    .au-cal-date b { font-size: 1.2rem; color: var(--au-navy); }
    .au-cal-title { display: block; font-weight: 700; color: var(--au-ink); }
    .au-cal-meta { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: var(--au-muted); }
    .au-cal-dot { flex: none; width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
    .au-cal-noclass { display: inline-block; margin-top: 0.2rem; padding: 0.05rem 0.5rem; border-radius: 999px; background: rgba(163, 25, 27, 0.08); color: var(--au-crimson); font-size: 0.75rem; font-weight: 700; }
    .au-cal-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--au-line); font-size: 0.82rem; color: var(--au-muted); }
    .au-cal-legend span { display: flex; align-items: center; gap: 0.45rem; }
    .au-cal-note { font-size: 0.82rem; color: var(--au-muted); margin: 1rem 0 0; }
    @media (max-width: 991px) { .au-cal-layout { grid-template-columns: 1fr; } }
    @media (max-width: 575px) {
        .au-cal-card { padding: 0.85rem; }
        .fc .fc-toolbar { flex-wrap: wrap; gap: 0.5rem; }
        .fc .fc-toolbar-title { font-size: 1.1rem; }
    }
</style>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">School Calendar</li>
            </ol>
        </nav>
        <span class="au-eyebrow">Academic Calendar</span>
        <h1>School Calendar</h1>
        <p>Holidays, DepEd school-year dates and school activities for SY 2026–2027.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="au-cal-layout">
            <div class="au-cal-card">
                <div id="calendar"></div>
                <div class="au-cal-legend" aria-label="Colour legend">
                    <?php foreach (array_merge(array_values(array_diff_key(CALENDAR_CATEGORIES, ['custom' => 1])), calendar_custom_types($rows)) as [$label, $color]): ?>
                        <span><span class="au-cal-dot" style="background: <?= $color ?>"></span><?= au_e($label) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="au-cal-card">
                <h2>Coming up</h2>
                <?php if ($upcoming): ?>
                    <ul class="au-cal-up">
                        <?php foreach ($upcoming as $r):
                            $cat = calendar_category($r);
                            $t = strtotime($r['start_date']); ?>
                            <li>
                                <span class="au-cal-date"><small><?= date('M', $t) ?></small><b><?= date('j', $t) ?></b></span>
                                <span>
                                    <span class="au-cal-title"><?= au_e($r['title']) ?></span>
                                    <span class="au-cal-meta"><span class="au-cal-dot" style="background: <?= $cat[1] ?>"></span><?= au_e(calendar_date_range($r['start_date'], $r['end_date'])) ?></span>
                                    <?php if ($r['no_classes']): ?><span class="au-cal-noclass">No classes</span><?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No upcoming dates yet.</p>
                <?php endif; ?>
                <p class="au-cal-note"><i class="bi bi-info-circle me-1"></i>Holidays follow the official proclamations and the school year follows DepEd Order No. 009, s. 2026. Class suspensions are announced separately. Watch <a href="news.php">News &amp; Events</a> for updates.</p>
            </aside>
        </div>
    </div>
</section>

<!-- Event details -->
<div class="modal fade" id="calEventModal" tabindex="-1" aria-labelledby="calEventTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calEventTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="au-cal-meta mb-2"><span class="au-cal-dot" id="calEventDot"></span><span id="calEventMeta"></span></p>
                <p class="mb-2"><i class="bi bi-calendar3 me-1"></i><span id="calEventDates"></span></p>
                <span class="au-cal-noclass" id="calEventNoClass">No classes</span>
                <p class="mt-3 mb-0 text-muted" id="calEventDesc"></p>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script>
(function () {
    var fmt = new Intl.DateTimeFormat("en-PH", { weekday: "short", month: "long", day: "numeric", year: "numeric" });
    function day(iso) { return fmt.format(new Date(iso + "T00:00:00")); }
    var modal = new bootstrap.Modal(document.getElementById("calEventModal"));
    var calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {
        initialView: window.innerWidth < 576 ? "listMonth" : "dayGridMonth",
        headerToolbar: { left: "prev,next today", center: "title", right: "dayGridMonth,listMonth" },
        buttonText: { today: "Today", month: "Month", list: "List" },
        height: "auto",
        dayMaxEvents: 3,
        eventDisplay: "block",
        events: ' . json_encode(calendar_fullcalendar_events($rows), JSON_HEX_TAG) . ',
        eventClassNames: function (arg) { return arg.event.extendedProps.noClasses ? ["is-noclass"] : []; },
        eventClick: function (info) {
            var p = info.event.extendedProps;
            document.getElementById("calEventTitle").textContent = info.event.title;
            document.getElementById("calEventDot").style.background = info.event.backgroundColor;
            document.getElementById("calEventMeta").textContent = p.categoryLabel + " · " + p.sourceLabel;
            document.getElementById("calEventDates").textContent = p.startDate === p.endDate ? day(p.startDate) : day(p.startDate) + " – " + day(p.endDate);
            document.getElementById("calEventNoClass").hidden = !p.noClasses;
            document.getElementById("calEventDesc").textContent = p.description;
            modal.show();
        }
    });
    calendar.render();
})();
</script>';
include __DIR__ . '/includes/site-footer.php';
