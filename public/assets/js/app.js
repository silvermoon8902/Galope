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
        document.querySelectorAll('[data-lock-on-close]').forEach(function (n) { n.disabled = true; });
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

  /* ---- Eleccion de modalidad + caballos en la pagina de carrera ---- */
  var form = document.getElementById('predict-form');
  if (form) {
    var MODES = {
      full:  { label: 'Full Point',  stakes: [50] },
      dual:  { label: 'Dual Point',  stakes: [25, 25] },
      smart: { label: 'Smart Point', stakes: [30, 15, 5] }
    };

    var modeCards = document.querySelectorAll('.mode-card');
    var list = document.getElementById('horse-list');
    var slotsBlock = document.getElementById('slots');
    var slotsRow = document.getElementById('slotsRow');
    var summary = document.getElementById('pickSummary');
    var btn = document.getElementById('confirmBtn');
    var modeInput = document.getElementById('modeInput');

    var currentMode = '';
    var picks = [];

    function nameOf(id) {
      var row = list.querySelector('.horse[data-id="' + id + '"]');
      return row ? row.getAttribute('data-name') : '';
    }

    function selectMode(mode) {
      if (!MODES[mode]) { return; }
      currentMode = mode;
      modeInput.value = mode;
      modeCards.forEach(function (card) {
        card.classList.toggle('selected', card.getAttribute('data-mode') === mode);
      });
      var maxPicks = MODES[mode].stakes.length;
      if (picks.length > maxPicks) { picks = picks.slice(0, maxPicks); }
      slotsBlock.style.display = 'block';
      render();
    }

    function render() {
      var stakes = MODES[currentMode] ? MODES[currentMode].stakes : [];

      var html = '';
      for (var i = 0; i < stakes.length; i++) {
        var pid = picks[i];
        var filled = pid ? ' filled' : '';
        html += '<div class="pick-pill' + filled + '">' +
                  '<span class="pp-stake">' + stakes[i] + '</span> ' +
                  '<span class="pp-name">' + (pid ? nameOf(pid) : '...') + '</span>' +
                '</div>';
      }
      slotsRow.innerHTML = html;

      list.querySelectorAll('.horse.selectable').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-id'), 10);
        var at = picks.indexOf(id);
        var slot = row.querySelector('[data-slot]');
        row.classList.toggle('picked', at > -1);
        if (at > -1) {
          slot.className = 'pick-slot filled stake';
          slot.textContent = stakes[at] || '';
        } else {
          slot.className = 'pick-slot';
          slot.textContent = '+';
        }
      });

      var required = MODES[currentMode] ? MODES[currentMode].stakes.length : 0;
      if (!currentMode) {
        summary.textContent = 'Elegi una modalidad para empezar.';
        btn.disabled = true;
      } else if (picks.length === 0) {
        summary.innerHTML = 'Modalidad: <b>' + MODES[currentMode].label + '</b>. Elegi tus caballos.';
        btn.disabled = true;
      } else if (picks.length < required) {
        summary.innerHTML = 'Modalidad: <b>' + MODES[currentMode].label + '</b>. Te faltan ' + (required - picks.length) + ' caballo(s).';
        btn.disabled = true;
      } else {
        summary.innerHTML = 'Modalidad: <b>' + MODES[currentMode].label + '</b> &middot; jugada lista.';
        btn.disabled = false;
      }

      document.getElementById('pick1').value = picks[0] || '';
      document.getElementById('pick2').value = picks[1] || '';
      document.getElementById('pick3').value = picks[2] || '';
    }

    function togglePick(id) {
      if (!currentMode) {
        summary.textContent = 'Primero elegi una modalidad (Full / Dual / Smart Point).';
        return;
      }
      var at = picks.indexOf(id);
      if (at > -1) {
        picks.splice(at, 1);
      } else {
        var max = MODES[currentMode].stakes.length;
        if (picks.length >= max) { return; }
        picks.push(id);
      }
      render();
    }

    modeCards.forEach(function (card) {
      card.addEventListener('click', function () {
        selectMode(card.getAttribute('data-mode'));
      });
    });

    list.querySelectorAll('.horse.selectable').forEach(function (row) {
      row.addEventListener('click', function () {
        togglePick(parseInt(row.getAttribute('data-id'), 10));
      });
    });

    var preMode = form.getAttribute('data-pre-mode') || '';
    var prePicks = (form.getAttribute('data-pre-picks') || '').split(',').filter(Boolean);
    if (preMode) {
      selectMode(preMode);
      picks = prePicks.map(function (s) { return parseInt(s, 10); }).filter(function (n) { return !isNaN(n); });
    }
    render();
  }

  /* ---- Alta y baja de caballos en el formulario de carrera (admin) ---- */
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
