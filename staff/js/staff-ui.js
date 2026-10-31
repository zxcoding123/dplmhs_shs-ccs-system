// Marks the class tab (Announcements, People, Activity …) for the current ?url= view
document.addEventListener('DOMContentLoaded', function () {
    var view = new URLSearchParams(location.search).get('url');
    if (!view) return;
    document.querySelectorAll('.linkism > a').forEach(function (link) {
        if (new URL(link.href, location.href).searchParams.get('url') === view) {
            link.parentElement.classList.add('is-current');
            link.setAttribute('aria-current', 'page');
        }
    });
});
