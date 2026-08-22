(function () {
  'use strict';

  function formatMoney(amount) {
    var num = parseFloat(amount) || 0;
    return '$ ' + num.toLocaleString('es-UY', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function validateAndCalculateInput(input) {
    if (!input || !input.hasAttribute('data-den-valor')) return true;

    var denValor = parseFloat(input.getAttribute('data-den-valor')) || 0;
    var campo = input.getAttribute('data-campo');
    var rawVal = input.value.trim().replace(',', '.');
    var table = input.closest('table[data-money-table]');
    var row = input.closest('tr');

    if (rawVal === '') {
      handleValid(input, row);
      updateRowSibling(row, campo, 0, denValor);
      if (table) updateTableTotals(table);
      return true;
    }

    var numVal = parseFloat(rawVal);
    if (isNaN(numVal) || numVal < 0) {
      handleInvalid(input, row, 'Debe ingresar un número válido no negativo.');
      return false;
    }

    if (campo === 'cantidad') {
      if (!Number.isInteger(numVal) || numVal < 0) {
        handleInvalid(input, row, 'La cantidad debe ser un número entero no negativo.');
        return false;
      }
      handleValid(input, row);
      updateRowSibling(row, 'cantidad', numVal, denValor);
      if (table) updateTableTotals(table);
      return true;
    }

    if (campo === 'total') {
      if (numVal > 0) {
        var quotient = numVal / denValor;
        var diff = Math.abs(quotient - Math.round(quotient));
        if (diff > 0.0001) {
          handleInvalid(input, row, 'El monto ingresado ($ ' + rawVal + ') no es divisible exactamente por el valor de la denominación ($ ' + denValor + ').');
          return false;
        }
      }
      handleValid(input, row);
      updateRowSibling(row, 'total', numVal, denValor);
      if (table) updateTableTotals(table);
      return true;
    }

    return true;
  }

  function handleInvalid(input, row, message) {
    if (row) row.classList.add('table-warning');
    input.classList.add('is-invalid');

    if (window.Swal) {
      Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        icon: 'warning',
        title: 'Valor no exacto',
        text: message
      });
    }

    setTimeout(function () {
      input.focus();
      if (input.select) input.select();
    }, 10);
  }

  function handleValid(input, row) {
    if (row) row.classList.remove('table-warning');
    input.classList.remove('is-invalid');
  }

  function updateRowSibling(row, sourceCampo, value, denValor) {
    if (!row) return;
    if (sourceCampo === 'cantidad') {
      var totalInput = row.querySelector('input[data-campo="total"]');
      if (totalInput && totalInput.readOnly) {
        totalInput.value = (value * denValor).toFixed(2).replace(/\.00$/, '');
      }
    } else if (sourceCampo === 'total') {
      var cantInput = row.querySelector('input[data-campo="cantidad"]');
      if (cantInput && cantInput.readOnly) {
        cantInput.value = value > 0 ? Math.floor(value / denValor) : 0;
      }
    }
  }

  function updateTableTotals(table) {
    if (!table) return;
    var totalSum = 0;
    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function (tr) {
      var totalInp = tr.querySelector('input[data-campo="total"]');
      var cantInp = tr.querySelector('input[data-campo="cantidad"]');
      var denValor = 0;
      if (cantInp) {
        denValor = parseFloat(cantInp.getAttribute('data-den-valor')) || 0;
      }
      if (totalInp && !totalInp.readOnly && totalInp.value) {
        totalSum += parseFloat(totalInp.value) || 0;
      } else if (cantInp && !cantInp.readOnly && cantInp.value) {
        totalSum += (parseFloat(cantInp.value) || 0) * denValor;
      }
    });

    var displayEl = table.querySelector('[data-total-display]');
    if (displayEl) {
      displayEl.textContent = formatMoney(totalSum);
    }

    // Buscar balance card: subir hasta el componente Livewire (div[wire:id]) o el body
    var root = table;
    var balanceCard = null;
    while (root && root !== document.body) {
      root = root.parentElement;
      if (!root) break;
      balanceCard = root.querySelector('[data-balance-card]');
      if (balanceCard) break;
    }

    if (balanceCard) {
      var saldoEsperado = parseFloat(balanceCard.getAttribute('data-saldo-esperado')) || 0;
      var diferencia = totalSum - saldoEsperado;

      var efectivoEl = balanceCard.querySelector('[data-balance-efectivo]');
      if (efectivoEl) efectivoEl.textContent = formatMoney(totalSum);

      var difEl = balanceCard.querySelector('[data-balance-diferencia]');
      if (difEl) {
        difEl.textContent = formatMoney(diferencia);
        difEl.className = 'text-right align-middle py-1 h6 mb-0 ' +
          (diferencia === 0 ? 'text-success' : (Math.abs(diferencia) <= 0.50 ? 'text-info' : 'text-danger'));
      }

      // Actualizar clases de la tarjeta
      balanceCard.classList.remove('caja-balance-cuadrado', 'caja-balance-tolerancia', 'caja-balance-descuadre');
      if (diferencia === 0) {
        balanceCard.classList.add('caja-balance-cuadrado');
      } else if (Math.abs(diferencia) <= 0.50) {
        balanceCard.classList.add('caja-balance-tolerancia');
      } else {
        balanceCard.classList.add('caja-balance-descuadre');
      }
    }
  }

  // Interceptar keydown (Enter / Tab) con capture: true ANTES de que el control pierda el enfoque
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== 'Tab') return;
    var target = e.target;
    if (!target || !target.hasAttribute('data-den-valor')) return;

    var isValid = validateAndCalculateInput(target);
    if (!isValid) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }, true);

  // Validar en change antes de que se complete el cambio
  document.addEventListener('change', function (e) {
    var target = e.target;
    if (!target || !target.hasAttribute('data-den-valor')) return;
    validateAndCalculateInput(target);
  }, true);

})();
