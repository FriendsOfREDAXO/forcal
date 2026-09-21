/**
 * forCal-Picker: macht aus <input class="forcal-picker"> eine Auswahl von Terminen, Kategorien oder Orten.
 *
 *   data-fp-type="entry"        Termine (Standard)        Wert "12,7"
 *   data-fp-type="category"     Kategorien                Wert "1,3"
 *   data-fp-type="venue"        Orte                      Wert "4"
 *   data-fp-multiple="true"     mehrere, per Drag & Drop oder Alt + Pfeiltaste sortierbar
 *   data-fp-max="5"             höchstens so viele (nur mit multiple)
 *   data-fp-category="3"        nur Termine dieser Kategorie
 *   data-fp-public="true"       nur Termine mit Status online
 *   data-fp-past="true"         der Dialog startet mit vergangenen Terminen
 *   data-fp-create="false"      kein "Neu anlegen" im Dialog (sonst für alle, die Termine pflegen dürfen)
 *
 * Gespeichert wird eine kommagetrennte Liste von IDs im ursprünglichen Feld. Nach jeder Änderung feuert das Feld
 * "input" und "change" (der MForm-Repeater liest Textfelder über "input").
 *
 * Bewusst ohne IDs und ohne globale Listener: MBlock und der MForm-Repeater klonen das Markup. Vor dem
 * Aufbau räumt init() geklonte Reste ab.
 *
 * JS-API: ForcalPicker.init(scope), ForcalPicker.open(callback, {type, multiple, max, category, public, past, selected}),
 *         ForcalPicker.create(defaults, callback) für die Schnellanlage ohne Auswahlfeld
 */
(function () {
  'use strict';

  var FLAG = 'data-fp-initialized';
  var TYPES = ['entry', 'category', 'venue'];
  var cache = {}; // Einträge nach Art und Schlüssel

  function t(key) {
    var text = (window.rex && window.rex.forcal_picker_i18n && window.rex.forcal_picker_i18n[key]) || key;
    for (var i = 1; i < arguments.length; i++) text = text.replace('{' + (i - 1) + '}', arguments[i]);
    return text;
  }

  function el(tag, attributes, children) {
    var node = document.createElement(tag);
    Object.keys(attributes || {}).forEach(function (name) {
      var value = attributes[name];
      if (value === null || value === undefined || value === false) return;
      if (name === 'text') node.textContent = value;
      else if (name.slice(0, 2) === 'on') node.addEventListener(name.slice(2), value);
      else node.setAttribute(name, value === true ? '' : value);
    });
    (children || []).forEach(function (child) { if (child) node.append(child); });
    return node;
  }

  function icon(name) {
    return el('i', { class: 'rex-icon ' + name, 'aria-hidden': 'true' });
  }

  function fetchJson(params) {
    var url = new URL((window.rex && window.rex.forcal_picker_api) || 'index.php', window.location.href);
    Object.keys(params).forEach(function (key) {
      if (params[key] !== null && params[key] !== undefined && params[key] !== '' && params[key] !== false) url.searchParams.set(key, params[key] === true ? 1 : params[key]);
    });
    return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(function (response) {
      if (!response.ok) throw new Error(t(response.status === 403 ? 'denied' : 'failed'));
      return response.json();
    });
  }

  function postForm(params) {
    var body = new FormData();
    Object.keys(params).forEach(function (key) { body.set(key, params[key] === true ? '1' : (params[key] === false ? '0' : params[key])); });
    body.set('_csrf_token', (window.rex && window.rex.forcal_picker_token) || '');
    var url = new URL((window.rex && window.rex.forcal_picker_api) || 'index.php', window.location.href);
    return fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) throw new Error(data.error || t('failed'));
        return data;
      });
    });
  }

  function remember(type, items) {
    cache[type] = cache[type] || {};
    items.forEach(function (item) { cache[type][item.key] = item; });
    return items;
  }

  /** Farbfeld des Kalenders mit dem Symbol der Art. */
  function badge(item) {
    return el('span', { class: 'fp-badge', style: item.color ? '--fp-color:' + item.color : null }, [icon(item.icon || 'fa-calendar-day')]);
  }

  function flags(item) {
    return [
      item.cancelled ? el('span', { class: 'fp-flag fp-flag-warn', text: t('cancelled') }) : null,
      item.offline ? el('span', { class: 'fp-flag', text: t('offline') }) : null,
    ];
  }

  // ------------------------------------------------------------------ Termin anlegen

  /**
   * Formular für einen neuen Termin. defaults: {name, date, endDate, allDay, start, end, category}.
   * onBack darf fehlen; onCreated bekommt den angelegten Eintrag.
   */
  function createForm(defaults, categories, submitLabel, onBack, onCreated) {
    var pad = function (number) { return String(number).padStart(2, '0'); };
    var today = new Date();
    var field = function (label, control) { return el('label', { class: 'fp-field' }, [el('span', { text: label }), control]); };
    var times = function (selected) {
      var select = el('select', { class: 'form-control' });
      var values = [];
      for (var minutes = 0; minutes < 1440; minutes += 15) values.push(pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60));
      if (selected && values.indexOf(selected) === -1) values.push(selected);
      values.sort().forEach(function (value) { select.append(el('option', { value: value, text: value, selected: value === selected })); });
      return select;
    };
    var isAllDay = defaults.allDay !== false;
    var name = el('input', { type: 'text', class: 'form-control', required: true, value: defaults.name || '', placeholder: t('create_name_placeholder') });
    var date = el('input', { type: 'date', class: 'form-control', required: true, value: defaults.date || today.getFullYear() + '-' + pad(today.getMonth() + 1) + '-' + pad(today.getDate()) });
    var endDate = el('input', { type: 'date', class: 'form-control', value: defaults.endDate || '' });
    var allDay = el('input', { type: 'checkbox', checked: isAllDay });
    var start = times(defaults.start || '09:00');
    var end = times(defaults.end || '10:00');
    var timeRow = el('div', { class: 'fp-field-row', hidden: isAllDay }, [field(t('create_from'), start), field(t('create_to'), end)]);
    var target = el('select', { class: 'form-control' }, categories.map(function (entry) { return el('option', { value: entry.id, text: entry.name, selected: String(entry.id) === String(defaults.category || '') }); }));
    var error = el('p', { class: 'fp-create-error', role: 'alert' });
    var submit = el('button', { type: 'submit', class: 'btn btn-save', text: submitLabel });
    var duration = 60;
    var minutesOf = function (value) { return Number(value.slice(0, 2)) * 60 + Number(value.slice(3)); };

    allDay.addEventListener('change', function () { timeRow.hidden = allDay.checked; });
    // Verschiebt man den Beginn, wandert das Ende mit: Die Dauer bleibt.
    start.addEventListener('change', function () {
      var minutes = Math.min(minutesOf(start.value) + duration, 1425);
      end.value = pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60);
    });
    end.addEventListener('change', function () { duration = Math.max(15, minutesOf(end.value) - minutesOf(start.value)); });

    var form = el('form', { class: 'fp-create-form', novalidate: true }, [
      el('h3', { class: 'fp-create-title', text: t('create_entry') }),
      field(t('create_title'), name),
      el('div', { class: 'fp-field-row' }, [field(t('create_date'), date), field(t('create_end_date'), endDate)]),
      el('label', { class: 'fp-option fp-option-inline' }, [allDay, ' ' + t('create_all_day')]),
      timeRow,
      field(t('category'), target),
      error,
      el('div', { class: 'fp-create-actions' }, [
        onBack ? el('button', { type: 'button', class: 'btn btn-default', text: t('create_back'), onclick: onBack }) : null,
        defaults.detailsUrl ? el('button', { type: 'button', class: 'btn btn-default', text: t('create_details'), onclick: function () {
          var url = new URL(defaults.detailsUrl, window.location.href);
          url.searchParams.set('itemdate', date.value);
          if (endDate.value) url.searchParams.set('itemenddate', endDate.value);
          if (name.value.trim()) url.searchParams.set('itemname', name.value.trim());
          url.searchParams.set('itemcategory', target.value);
          url.searchParams.set('itemfulltime', allDay.checked ? '1' : '0');
          if (!allDay.checked) { url.searchParams.set('itemtime', start.value); url.searchParams.set('itemendtime', end.value); }
          window.location.assign(url);
        } }) : null,
        submit,
      ]),
    ]);
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (name.value.trim() === '') { error.textContent = t('create_title_required'); name.focus(); return; }
      submit.disabled = true;
      error.textContent = '';
      postForm({ action: 'create', type: 'entry', name: name.value, date: date.value, end_date: endDate.value, all_day: allDay.checked, start: start.value, end: end.value, category: target.value })
        .then(function (data) { onCreated(data.item); })
        .catch(function (problem) { error.textContent = problem.message; submit.disabled = false; });
    });
    return form;
  }

  /**
   * Schnellanlage ohne Auswahlfeld, etwa aus dem Kalenderblatt: öffnet einen Dialog nur mit dem Formular.
   * defaults wie bei createForm, dazu detailsUrl für "Weitere Angaben …". callback bekommt den neuen Termin.
   * Liefert false, wenn der Benutzer nichts anlegen darf; der Aufrufer kann dann wie bisher zum Editor wechseln.
   */
  function create(defaults, callback) {
    return fetchJson({ type: 'entry', offset: 100000 }).then(function (data) {
      if (!data.create || !data.create.length) return false;
      var dialog = el('dialog', { class: 'fp-dialog fp-dialog-create', 'aria-label': t('create_entry') });
      var form = createForm(defaults || {}, data.create, t('create_save'), null, function (item) { dialog.close(); if (callback) callback(item); });
      dialog.append(
        el('header', { class: 'fp-head' }, [el('h2', { class: 'fp-title', text: t('create_entry') }), el('button', { type: 'button', class: 'fp-close', 'aria-label': t('close'), onclick: function () { dialog.close(); } }, [icon('fa-xmark')])]),
        el('div', { class: 'fp-create-panel' }, [form, el('p', { class: 'fp-note fp-note-inline', text: t('create_help') })])
      );
      form.querySelector('.fp-create-title').remove();
      dialog.addEventListener('close', function () { dialog.remove(); });
      dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
      document.body.append(dialog);
      dialog.showModal();
      form.querySelector('input[type=text]').focus();
      return true;
    });
  }

  // ------------------------------------------------------------------ Dialog

  function open(callback, options) {
    options = options || {};
    var type = TYPES.indexOf(options.type) === -1 ? 'entry' : options.type;
    var multiple = Boolean(options.multiple);
    var max = Number(options.max) || 0;
    var chosen = (options.selected || []).map(String);
    var dated = type === 'entry';
    var state = { q: '', category: options.category || '', past: Boolean(options.past), offset: 0 };

    var search = el('input', { type: 'search', class: 'form-control', placeholder: t('search_' + type), 'aria-label': t('search_' + type), autocomplete: 'off' });
    var category = el('select', { class: 'form-control', 'aria-label': t('category'), hidden: !dated || Boolean(options.category) });
    var past = el('input', { type: 'checkbox', checked: state.past });
    var results = el('ul', { class: 'fp-results', role: 'listbox', 'aria-label': t('title_' + type), 'aria-multiselectable': multiple ? 'true' : null });
    var status = el('p', { class: 'fp-status', role: 'status' });
    var more = el('button', { type: 'button', class: 'btn btn-default btn-sm fp-more', text: t('more'), hidden: true });
    var count = el('span', { class: 'fp-count' });
    var apply = el('button', { type: 'button', class: 'btn btn-save', text: t('apply'), hidden: !multiple });
    // Schnell anlegen: erscheint nur, wenn der Server meldet, dass der Benutzer das darf (data.create).
    var createButton = el('button', { type: 'button', class: 'btn btn-default fp-create', hidden: true, title: t('create_' + type) }, [icon('fa-plus'), ' ' + t('create_new')]);
    var createPanel = el('div', { class: 'fp-create-panel', hidden: true });
    var dialog = el('dialog', { class: 'fp-dialog', 'aria-label': t('title_' + type) }, [
      el('header', { class: 'fp-head' }, [
        el('h2', { class: 'fp-title', text: t('title_' + type + (multiple ? '_multiple' : '')) }),
        el('button', { type: 'button', class: 'fp-close', 'aria-label': t('close'), onclick: function () { dialog.close(); } }, [icon('fa-xmark')]),
      ]),
      el('div', { class: 'fp-filters' }, [search, category, createButton]),
      dated ? el('label', { class: 'fp-option' }, [past, ' ' + t('include_past')]) : null,
      options.public && dated ? el('p', { class: 'fp-note fp-note-public' }, [icon('fa-globe'), ' ' + t('public_only')]) : null,
      createPanel, results, status, more,
      el('footer', { class: 'fp-foot' }, [count, el('button', { type: 'button', class: 'btn btn-default', text: t('cancel'), onclick: function () { dialog.close(); } }), apply]),
    ]);

    function syncCount() {
      count.textContent = multiple ? t('selected', chosen.length) + (max ? ' · ' + t('max', max) : '') : '';
    }

    function finish(keys) {
      var items = keys.map(function (key) { return (cache[type] || {})[key]; }).filter(Boolean);
      dialog.close();
      callback(multiple ? items : items[0]);
    }

    function row(item) {
      var selected = chosen.indexOf(item.key) !== -1;
      var option = el('li', { class: 'fp-row' + (selected ? ' is-selected' : ''), role: 'option', tabindex: '0', 'aria-selected': String(selected) }, [
        multiple ? el('span', { class: 'fp-check', 'aria-hidden': 'true' }, [icon('fa-check')]) : null,
        badge(item),
        el('span', { class: 'fp-text' }, [el('span', { class: 'fp-name', text: item.title }), item.subtitle ? el('span', { class: 'fp-sub', text: item.subtitle }) : null]),
      ].concat(flags(item), [el('span', { class: 'fp-meta', text: item.meta || '' })]));
      function toggle() {
        if (!multiple) { finish([item.key]); return; }
        var index = chosen.indexOf(item.key);
        if (index !== -1) chosen.splice(index, 1);
        else if (max && chosen.length >= max) { status.textContent = t('max_reached', max); return; }
        else chosen.push(item.key);
        var now = chosen.indexOf(item.key) !== -1;
        option.classList.toggle('is-selected', now);
        option.setAttribute('aria-selected', String(now));
        status.textContent = '';
        syncCount();
      }
      option.addEventListener('click', toggle);
      option.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggle(); }
        if (event.key === 'ArrowDown') { event.preventDefault(); (option.nextElementSibling || option).focus(); }
        if (event.key === 'ArrowUp') { event.preventDefault(); (option.previousElementSibling || search).focus(); }
      });
      return option;
    }

    /** Formular zum Anlegen. Der Suchtext wird zum Titel: Man hat ja gerade danach gesucht. */
    function showCreate(categories) {
      var form = createForm({ name: search.value.trim(), category: state.category }, categories, t(multiple ? 'create_and_add' : 'create_and_choose'), hideCreate, function (item) {
        remember(type, [item]);
        if (!multiple) { finish([item.key]); return; }
        if (!max || chosen.length < max) chosen.push(item.key);
        hideCreate();
        syncCount();
        search.value = '';
        state.q = '';
        load(false);
      });
      createPanel.replaceChildren(form);
      createPanel.hidden = false;
      results.hidden = status.hidden = true;
      more.hidden = true;
      form.querySelector('input[type=text]').focus();
    }

    function hideCreate() {
      createPanel.hidden = true;
      results.hidden = status.hidden = false;
      search.focus();
    }

    var request = 0;
    function load(append) {
      var current = ++request;
      if (!append) state.offset = 0;
      status.textContent = t('loading');
      fetchJson({ type: type, q: state.q, category: state.category, past: state.past, public: options.public, offset: state.offset }).then(function (data) {
        if (current !== request) return; // eine neuere Suche ist schon unterwegs
        remember(type, data.items);
        if (!append) results.replaceChildren();
        data.items.forEach(function (item) { results.append(row(item)); });
        state.offset += data.items.length;
        more.hidden = state.offset >= data.total;
        status.textContent = data.total === 0 ? t(state.q ? 'no_results' : (dated && !state.past ? 'empty_upcoming' : 'empty')) : t('found', data.total);
        var creatable = data.create && data.create.length ? data.create : null;
        createButton.hidden = !creatable || options.create === false;
        createButton.onclick = creatable ? function () { showCreate(creatable); } : null;
        if (category.options.length === 0) {
          category.append(el('option', { value: '', text: t('all_categories') }));
          (data.categories || []).forEach(function (entry) { category.append(el('option', { value: entry.id, text: entry.name })); });
          category.value = state.category;
        }
      }).catch(function (error) { if (current === request) status.textContent = error.message; });
    }

    var timer = null;
    search.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () { state.q = search.value.trim(); load(false); }, 220);
    });
    search.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown') { event.preventDefault(); if (results.firstElementChild) results.firstElementChild.focus(); }
      if (event.key === 'Enter') event.preventDefault();
    });
    category.addEventListener('change', function () { state.category = category.value; load(false); });
    past.addEventListener('change', function () { state.past = past.checked; load(false); });
    more.addEventListener('click', function () { load(true); });
    apply.addEventListener('click', function () { finish(chosen.slice()); });
    dialog.addEventListener('close', function () { dialog.remove(); });
    dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });

    document.body.append(dialog);
    dialog.showModal();
    syncCount();
    load(false);
    search.focus();
  }

  // ------------------------------------------------------------------ Widget

  function Widget(input) {
    this.input = input;
    this.type = TYPES.indexOf(input.getAttribute('data-fp-type')) === -1 ? 'entry' : input.getAttribute('data-fp-type');
    this.multiple = input.getAttribute('data-fp-multiple') === 'true';
    this.max = this.multiple ? Number(input.getAttribute('data-fp-max')) || 0 : 1;
    this.options = {
      type: this.type, multiple: this.multiple, max: this.multiple ? this.max : 0,
      category: input.getAttribute('data-fp-category') || null,
      public: input.getAttribute('data-fp-public') === 'true',
      past: input.getAttribute('data-fp-past') === 'true',
      create: input.getAttribute('data-fp-create') !== 'false',
    };

    input.setAttribute(FLAG, 'true');
    input.classList.add('fp-source');
    this.items = el('ul', { class: 'fpw-items' });
    this.pickButton = el('button', { type: 'button', class: 'btn btn-default btn-sm fpw-pick', onclick: this.pick.bind(this) });
    this.clearButton = el('button', { type: 'button', class: 'btn btn-default btn-sm fpw-clear', title: t('clear'), 'aria-label': t('clear'), onclick: this.set.bind(this, []) }, [icon('fa-trash')]);
    this.container = el('div', { class: 'fpw' + (this.multiple ? ' fpw-multiple' : '') }, [this.items, el('div', { class: 'fpw-toolbar' }, [this.pickButton, this.clearButton])]);
    input.parentNode.insertBefore(this.container, input.nextSibling);
    this.bindSort();
    this.render();
  }

  Widget.prototype.keys = function () {
    return this.input.value.split(',').map(function (key) { return key.trim(); }).filter(function (key) { return /^[1-9]\d*(:\d{8}(T\d{6})?)?$/.test(key); });
  };

  /** Einziger Schreibweg: Wert setzen, Ereignisse feuern, neu zeichnen. */
  Widget.prototype.set = function (keys) {
    keys = keys.filter(function (key, index) { return keys.indexOf(key) === index; });
    if (this.max) keys = keys.slice(0, this.max);
    this.input.value = keys.join(',');
    this.input.dispatchEvent(new Event('input', { bubbles: true }));
    this.input.dispatchEvent(new Event('change', { bubbles: true }));
    this.render();
  };

  Widget.prototype.pick = function () {
    var self = this;
    open(function (result) {
      if (!result) return;
      self.set(self.multiple ? result.map(function (item) { return item.key; }) : [result.key]);
    }, Object.assign({}, this.options, { selected: this.multiple ? this.keys() : [] }));
  };

  Widget.prototype.render = function () {
    var self = this;
    var keys = this.keys();
    var known = cache[this.type] = cache[this.type] || {};
    var unknown = keys.filter(function (key) { return !known[key]; });
    var draw = function () {
      self.items.replaceChildren();
      keys.forEach(function (key) { self.items.append(self.chip(key, known[key])); });
      var suffix = self.type + (self.multiple ? '_multiple' : '');
      self.pickButton.replaceChildren(icon(self.type === 'entry' ? 'fa-calendar-plus' : 'fa-plus'), ' ' + t((keys.length === 0 ? 'choose_' : 'change_') + suffix));
      self.clearButton.hidden = keys.length === 0;
      self.container.classList.toggle('is-empty', keys.length === 0);
    };
    draw();
    if (unknown.length > 0) {
      fetchJson({ type: this.type, keys: unknown.join(',') }).then(function (data) {
        remember(self.type, data.items);
        unknown.forEach(function (key) { if (!known[key]) known[key] = { key: key, missing: true, title: t('missing_' + self.type, key), icon: 'fa-circle-question' }; });
        draw();
      }).catch(function () { /* die Einträge bleiben mit ihrer Nummer stehen */ });
    }
  };

  Widget.prototype.chip = function (key, item) {
    var self = this;
    item = item || { key: key, title: '#' + key };
    return el('li', { class: 'fpw-item' + (item.missing ? ' is-missing' : ''), 'data-key': key, draggable: this.multiple ? 'true' : null, tabindex: this.multiple ? '0' : null }, [
      this.multiple ? el('span', { class: 'fpw-grip', 'aria-hidden': 'true' }, [icon('fa-grip-vertical')]) : null,
      badge(item),
      el('span', { class: 'fp-text' }, [
        item.url && !item.missing ? el('a', { class: 'fp-name', href: item.url, target: '_blank', rel: 'noopener', text: item.title, draggable: 'false' }) : el('span', { class: 'fp-name', text: item.title }),
        item.subtitle ? el('span', { class: 'fp-sub', text: item.subtitle + (item.meta ? ' · ' + item.meta : '') }) : (item.meta ? el('span', { class: 'fp-sub', text: item.meta }) : null),
      ]),
    ].concat(flags(item), [
      el('button', { type: 'button', class: 'fpw-remove', title: t('remove'), 'aria-label': t('remove_name', item.title), onclick: function () { self.set(self.keys().filter(function (other) { return other !== key; })); } }, [icon('fa-xmark')]),
    ]));
  };

  /** Reihenfolge per Drag & Drop; die Tastatur nutzt Alt + Pfeiltaste auf dem Eintrag. */
  Widget.prototype.bindSort = function () {
    if (!this.multiple) return;
    var self = this;
    var dragged = null;
    this.items.addEventListener('dragstart', function (event) {
      dragged = event.target.closest('.fpw-item');
      if (!dragged) return;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', dragged.getAttribute('data-key'));
      dragged.classList.add('is-dragging');
    });
    this.items.addEventListener('dragover', function (event) {
      var over = event.target.closest('.fpw-item');
      if (!dragged || !over || over === dragged) return;
      event.preventDefault();
      var box = over.getBoundingClientRect();
      over.parentNode.insertBefore(dragged, event.clientY > box.top + box.height / 2 ? over.nextSibling : over);
    });
    this.items.addEventListener('dragend', function () {
      if (!dragged) return;
      dragged.classList.remove('is-dragging');
      dragged = null;
      self.set([].map.call(self.items.children, function (node) { return node.getAttribute('data-key'); }));
    });
    this.items.addEventListener('keydown', function (event) {
      var item = event.target.closest('.fpw-item');
      if (!item || event.target !== item || !event.altKey || (event.key !== 'ArrowUp' && event.key !== 'ArrowDown')) return;
      event.preventDefault();
      var keys = self.keys();
      var from = keys.indexOf(item.getAttribute('data-key'));
      var to = from + (event.key === 'ArrowUp' ? -1 : 1);
      if (to < 0 || to >= keys.length) return;
      keys.splice(to, 0, keys.splice(from, 1)[0]);
      self.set(keys);
      var moved = self.items.children[to];
      if (moved) moved.focus();
    });
  };

  // ------------------------------------------------------------------ Aufbau

  function init(scope) {
    var root = scope && scope.querySelectorAll ? scope : document;
    // Geklonte Felder (MBlock, MForm-Repeater) bringen ein fertiges Widget mit, das auf das Original zeigt.
    [].forEach.call(root.querySelectorAll('input.forcal-picker[' + FLAG + ']'), function (input) {
      var next = input.nextElementSibling;
      if (next && next.classList.contains('fpw')) next.remove();
      input.removeAttribute(FLAG);
    });
    [].forEach.call(root.querySelectorAll('input.forcal-picker'), function (input) {
      if (!input.getAttribute(FLAG)) new Widget(input);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(document); });
  else init(document);

  if (window.jQuery) {
    window.jQuery(document).on('rex:ready', function (event, container) {
      init(container && container.length ? container[0] : document);
    });
  }

  window.ForcalPicker = { init: init, open: open, create: create };
})();
