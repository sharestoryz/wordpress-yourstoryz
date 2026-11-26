(function () {
    var form = document.getElementById('ys-settings-form');
    var config = window.yourstoryzSettings || {};

    if (!form) {
        return;
    }

    var saveBar = form.querySelector('.ys-savebar');
    var markDirty = function () {
        if (saveBar) {
            saveBar.classList.add('is-dirty');
        }
    };

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    var autoImport = document.getElementById('yourstoryz_auto_import');
    var interval = form.querySelector('[data-ys-interval]');

    if (autoImport && interval) {
        autoImport.addEventListener('change', function () {
            interval.hidden = !autoImport.checked;
        });
    }

    var slug = document.getElementById('yourstoryz_slug');
    var slugPreview = form.querySelector('[data-ys-slug-preview]');

    if (slug && slugPreview) {
        slug.addEventListener('input', function () {
            var value = slug.value.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
            var url = slugPreview.getAttribute('data-ys-home') + (value || slug.placeholder) + '/';

            slugPreview.textContent = url;
            slugPreview.href = url;
        });
    }

    var company = document.getElementById('yourstoryz_company_id');
    var department = document.getElementById('yourstoryz_department_id');
    var departmentsHint = form.querySelector('[data-ys-departments-hint]');
    var hintText = departmentsHint ? departmentsHint.textContent : '';

    var setDepartments = function (items) {
        department.replaceChildren(new Option(config.wholeCompany, ''));
        items.forEach(function (item) {
            department.add(new Option(item.name, String(item.id)));
        });
    };

    if (company && department && config.departmentsUrl) {
        company.addEventListener('change', function () {
            setDepartments([]);

            if (!company.value) {
                return;
            }

            department.disabled = true;

            fetch(config.departmentsUrl + '&company=' + encodeURIComponent(company.value), { credentials: 'same-origin' })
                .then(function (response) {
                    return response.json();
                })
                .then(function (body) {
                    if (body.success) {
                        setDepartments(body.data);
                        departmentsHint.textContent = hintText;
                    } else {
                        departmentsHint.textContent = (body.data && body.data.message) || hintText;
                    }
                })
                .catch(function () {
                    departmentsHint.textContent = hintText;
                })
                .finally(function () {
                    department.disabled = false;
                });
        });
    }

    document.querySelectorAll('[data-ys-confirm]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (!window.confirm(config.confirmDisconnect)) {
                event.preventDefault();
            }
        });
    });
})();
