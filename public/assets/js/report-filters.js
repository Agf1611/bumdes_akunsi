(function () {
  function ensureScopeInput(form) {
    var input = form.querySelector('input[name="filter_scope"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'filter_scope';
      input.value = 'period';
      form.appendChild(input);
    }
    return input;
  }

  function selectedPeriod(select) {
    if (!select || !select.value) {
      return null;
    }
    var option = select.options[select.selectedIndex];
    if (!option) {
      return null;
    }
    return {
      id: select.value,
      start: option.dataset.startDate || '',
      end: option.dataset.endDate || '',
      year: option.dataset.fiscalYear || ''
    };
  }

  function setDate(input, value) {
    if (input && value) {
      input.value = value;
    }
  }

  function syncPeriodDates(form, mode) {
    var startSelect = form.querySelector('select[name="period_id"]');
    var endSelect = form.querySelector('select[name="period_to_id"]');
    var dateFrom = form.querySelector('input[name="date_from"]');
    var dateTo = form.querySelector('input[name="date_to"]');
    var fiscalYear = form.querySelector('select[name="fiscal_year"]');
    var scope = ensureScopeInput(form);
    var start = selectedPeriod(startSelect);
    var end = selectedPeriod(endSelect);

    if (mode === 'manual') {
      scope.value = 'manual';
      return;
    }

    if (mode === 'range' && end && !start && startSelect) {
      startSelect.value = end.id;
      start = selectedPeriod(startSelect);
    }

    if (!start) {
      scope.value = 'manual';
      return;
    }

    if (mode === 'range' && end) {
      scope.value = 'period_range';
      setDate(dateFrom, start.start);
      setDate(dateTo, end.end);
    } else {
      scope.value = 'period';
      if (endSelect) {
        endSelect.value = '';
      }
      setDate(dateFrom, start.start);
      setDate(dateTo, start.end);
    }

    if (fiscalYear && start.year && fiscalYear.value !== start.year) {
      fiscalYear.value = start.year;
    }
  }

  document.querySelectorAll('form').forEach(function (form) {
    var periodSelect = form.querySelector('select[name="period_id"]');
    var dateFrom = form.querySelector('input[name="date_from"]');
    var dateTo = form.querySelector('input[name="date_to"]');
    if (!periodSelect || (!dateFrom && !dateTo)) {
      return;
    }

    var endSelect = form.querySelector('select[name="period_to_id"]');
    ensureScopeInput(form);

    periodSelect.addEventListener('change', function () {
      syncPeriodDates(form, 'period');
    });

    if (endSelect) {
      endSelect.addEventListener('change', function () {
        syncPeriodDates(form, endSelect.value ? 'range' : 'period');
      });
    }

    [dateFrom, dateTo].forEach(function (input) {
      if (!input) {
        return;
      }
      input.addEventListener('change', function () {
        syncPeriodDates(form, 'manual');
      });
    });

    form.addEventListener('submit', function () {
      var scope = ensureScopeInput(form).value;
      if (scope === 'period_range') {
        syncPeriodDates(form, 'range');
      } else if (scope === 'period') {
        syncPeriodDates(form, 'period');
      }
    });
  });
})();
