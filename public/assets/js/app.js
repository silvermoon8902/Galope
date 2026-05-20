/* Galope - interaccion del lado del cliente.
   Toda regla del juego se valida en el servidor; este archivo es solo apoyo. */
(function () {
  'use strict';

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  /* ---- Cuenta regresiva al cierre de predicciones ---- */
  document.querySelectorAll('[data-remaining]').forEach(function (el) {
    var remaining = parseInt(el.getAttribute('data-remaining'), 10) || 0;

    function paint() {
      if (remaining <= 0) {
        el.textContent = 'Cerradas';
        document.querySelectorAll('[data-lock-on-close]').forEach(function (n) {
          n.disabled = true;
        });
        var msg = document.getElementById('lock-msg');
        if (msg) { msg.style.display = 'block'; }
        return;
      }
      var h = Math.floor(remaining / 3600);
      var m = Math.floor((remaining % 3600) / 60);
      var s = remaining % 60;
      el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
      remaining--;
    }

    paint();
    setInterval(paint, 1000);
  });

  /* ---- Eleccion del podio en la pagina de carrera ---- */
  var form = document.getElementById('predict-form');
  if (form) {
    var picks = [null, null, null];
    var labels = ['1', '2', '3'];
    var slotClass = ['p1', 'p2', 'p3'];
    var list = document.getElementById('horse-list');
    var summary = document.getElementById('pickSummary');
    var btn = document.getElementById('confirmBtn');

    (form.getAttribute('data-pre') || '').split(',').filter(Boolean).forEach(function (id, i) {
      if (i < 3) { picks[i] = parseInt(id, 10); }
    });

    function nameOf(id) {
      var row = list.querySelector('.horse[data-id="' + id + '"]');
      return row ? row.getAttribute('data-name') : '';
    }

    function render() {
      list.querySelectorAll('.horse.selectable').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-id'), 10);
        var at = picks.indexOf(id);
        var slot = row.querySelector('[data-slot]');
        row.classList.toggle('picked', at > -1);
        slot.className = 'pick-slot' + (at > -1 ? ' filled ' + slotClass[at] : '');
        slot.textContent = at > -1 ? labels[at] : '+';
      });

      if (picks.every(function (p) { return p === null; })) {
        summary.textContent = 'Todavia no elegiste tu podio.';
      } else {
        summary.innerHTML = 'Tu podio: <b>' + picks.map(function (p, i) {
          return labels[i] + ' ' + (p ? nameOf(p) : '-');
        }).join(' &middot; ') + '</b>';
      }

      document.getElementById('pick1').value = picks[0] || '';
      document.getElementById('pick2').value = picks[1] || '';
      document.getElementById('pick3').value = picks[2] || '';
      if (btn) { btn.disabled = !picks.every(function (p) { return p !== null; }); }
    }

    list.querySelectorAll('.horse.selectable').forEach(function (row) {
      row.addEventListener('click', function () {
        var id = parseInt(row.getAttribute('data-id'), 10);
        var at = picks.indexOf(id);
        if (at > -1) {
          picks[at] = null;
        } else {
          var free = picks.indexOf(null);
          if (free === -1) { return; }
          picks[free] = id;
        }
        render();
      });
    });

    render();
  }

  /* ---- Vista previa en vivo de las reglas de puntuacion ---- */
  var rulesForm = document.getElementById('rules-form');
  if (rulesForm) {
    var ruleVal = function (key) {
      var el = rulesForm.querySelector('input[data-key="' + key + '"]');
      return el ? (parseInt(el.value, 10) || 0) : 0;
    };
    var setText = function (id, value) {
      var n = document.getElementById(id);
      if (n) { n.textContent = value; }
    };
    var refresh = function () {
      setText('pvPerfect', ruleVal('exact_1') + ruleVal('exact_2') + ruleVal('exact_3') +
        ruleVal('bonus_any_order') + ruleVal('bonus_exact_podium'));
      setText('pvWinner', ruleVal('exact_1'));
    };
    rulesForm.querySelectorAll('input[data-key]').forEach(function (i) {
      i.addEventListener('input', refresh);
    });
    refresh();
  }

  /* ---- Alta y baja de caballos en el formulario de carrera ---- */
  var addBtn = document.getElementById('addHorse');
  if (addBtn) {
    var rows = document.getElementById('horse-rows');
    addBtn.addEventListener('click', function () {
      var clone = rows.querySelector('.hrow').cloneNode(true);
      clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      rows.appendChild(clone);
    });
    rows.addEventListener('click', function (ev) {
      if (ev.target.classList.contains('row-remove') && rows.querySelectorAll('.hrow').length > 1) {
        ev.target.closest('.hrow').remove();
      }
    });
  }
})();
