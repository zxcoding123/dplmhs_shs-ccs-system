/* =========================================================
   Registrar portal behaviour shared by its pages.
   Loaded after admin-ui.js; needs Bootstrap and SweetAlert2.
   ========================================================= */
(function () {
    'use strict';

    // ---------- Section list follows the chosen strand + grade ----------
    function filterSections(form) {
        var strand = form.querySelector('[name=strand]');
        var grade = form.querySelector('[name=grade]');
        var section = form.querySelector('[data-section-select]');
        if (!strand || !grade || !section) return;

        Array.prototype.forEach.call(section.options, function (option) {
            if (!option.value) return;
            var match = option.dataset.strand === strand.value && option.dataset.grade === grade.value;
            option.hidden = !match;
            option.disabled = !match;
        });
        var chosen = section.options[section.selectedIndex];
        if (chosen && chosen.disabled) section.value = '';
    }

    document.querySelectorAll('form').forEach(function (form) {
        if (!form.querySelector('[data-section-select]')) return;
        ['strand', 'grade'].forEach(function (name) {
            var field = form.querySelector('[name=' + name + ']');
            if (field) field.addEventListener('change', function () { filterSections(form); });
        });
        filterSections(form);
    });

    // ---------- Enrollment modal: [data-enroll] buttons fill it in ----------
    var modalEl = document.getElementById('enrollmentModal');
    if (modalEl) {
        var form = modalEl.querySelector('form');
        var defaults = {};
        Array.prototype.forEach.call(form.elements, function (el) {
            if (el.name && el.name !== 'csrf' && el.name !== 'return') defaults[el.name] = el.value;
        });

        document.addEventListener('click', function (e) {
            var button = e.target.closest('[data-enroll]');
            if (!button) return;
            var values = JSON.parse(button.dataset.enroll || '{}');
            var locked = button.hasAttribute('data-enroll-locked');

            Object.keys(defaults).forEach(function (name) {
                var field = form.elements[name];
                if (!field) return;
                field.value = values[name] !== undefined && values[name] !== null ? values[name] : defaults[name];
            });

            // Editing an existing record: its term can't change (delete and re-add instead)
            form.querySelectorAll('[data-term-part]').forEach(function (el) {
                el.classList.toggle('pe-none', locked);
                el.classList.toggle('bg-light', locked);
                el.setAttribute('aria-readonly', locked ? 'true' : 'false');
                el.tabIndex = locked ? -1 : 0;
            });

            var picker = form.querySelector('[data-student-picker]');
            var nameEl = form.querySelector('[data-student-name]');
            var hasStudent = !!values.student_row_id;
            if (picker) picker.hidden = hasStudent;
            nameEl.hidden = !values.student_name;
            nameEl.textContent = values.student_name || '';
            form.querySelector('[data-enroll-title]').textContent = locked ? 'Edit enrollment record' : 'Enroll student';

            filterSections(form);
            if (values.section_code) form.elements.section_code.value = values.section_code;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    }

    // ---------- Delete an enrollment record ----------
    document.addEventListener('click', function (e) {
        var button = e.target.closest('[data-delete-record]');
        if (!button) return;
        Swal.fire({
            title: 'Delete this record?',
            text: button.dataset.deleteRecord + '. The student\'s account and current placement are not changed.',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: 'Delete record',
            customClass: { popup: 'ui-swal-danger' }
        }).then(function (result) {
            if (!result.isConfirmed) return;
            document.getElementById('deleteRecordId').value = button.dataset.id;
            document.getElementById('deleteRecordForm').submit();
        });
    });

    // ---------- Generate a temporary password ----------
    document.querySelectorAll('[data-generate-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var letters = 'abcdefghjkmnpqrstuvwxyz';
            var pick = function () { return letters[Math.floor(Math.random() * letters.length)]; };
            var field = document.querySelector(button.dataset.generatePassword);
            field.value = 'Dplmhs-' + (1000 + Math.floor(Math.random() * 9000)) + '-' + pick() + pick();
            field.dispatchEvent(new Event('input'));
        });
    });

    // ---------- Confirm account status changes ----------
    document.querySelectorAll('form[data-confirm-status]').forEach(function (statusForm) {
        statusForm.addEventListener('submit', function (e) {
            if (statusForm.dataset.confirmed) return;
            e.preventDefault();
            var activating = statusForm.elements.status.value === 'active';
            Swal.fire({
                title: (activating ? 'Activate ' : 'Deactivate ') + statusForm.dataset.confirmStatus + '\'s account?',
                text: activating ? 'They will be able to sign in again.' : 'They will no longer be able to sign in. Their records are kept.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: activating ? 'Activate' : 'Deactivate'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                statusForm.dataset.confirmed = '1';
                statusForm.submit();
            });
        });
    });
})();
