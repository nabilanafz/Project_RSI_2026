/* FILKOM Connect: progressive interactions over the existing PHP HTML routes. */
'use strict';
(() => {
  const dialog = document.getElementById('error-dialog');
  let returnFocus = null;
  function showError(messages, focusTarget) {
    if (!dialog) return;
    returnFocus = focusTarget || document.activeElement;
    const list = dialog.querySelector('#error-messages');
    list.replaceChildren(...[].concat(messages).map(message => {
      const li = document.createElement('li'); li.textContent = String(message); return li;
    }));
    if (!dialog.open) dialog.showModal();
  }
  if (dialog) {
    if (dialog.hasAttribute('data-open-error')) { dialog.close(); dialog.showModal(); }
    dialog.addEventListener('close', () => {
      if (returnFocus instanceof HTMLElement && returnFocus.isConnected) returnFocus.focus();
    });
    dialog.addEventListener('click', event => {
      const box = dialog.getBoundingClientRect();
      if (event.target === dialog && (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom)) dialog.close();
    });
  }
  let invalidMessages = [], invalidTarget = null, invalidTimer;
  document.addEventListener('invalid', event => {
    event.preventDefault();
    invalidTarget ||= event.target;
    const label = event.target.closest('label')?.childNodes[0]?.textContent?.trim() || 'Isian formulir';
    invalidMessages.push(label + ': ' + event.target.validationMessage);
    clearTimeout(invalidTimer);
    invalidTimer = setTimeout(() => {showError([...new Set(invalidMessages)], invalidTarget); invalidMessages = []; invalidTarget = null;}, 0);
  }, true);
  function messagesFrom(doc) {
    return Array.from(doc.querySelectorAll('#error-messages li')).map(li => li.textContent);
  }
  async function requestHTML(address, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(address, {...options, credentials:'same-origin', cache:'no-store', signal:controller.signal,
        headers:{'X-RSI-Fragment':'1', ...(options.headers || {})}});
      const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
      const errors = messagesFrom(doc);
      if (!response.ok || errors.length) throw new Error(errors.join('\n') || 'Proses belum berhasil. Silakan coba kembali.');
      return doc;
    } finally { clearTimeout(timer); }
  }
  const application = document.querySelector('[data-application-form]');
  if (application) {
    const consent = application.elements.namedItem('persetujuan');
    const submit = application.querySelector('[data-send-application]');
    const portfolio = application.elements.namedItem('portofolio');
    let pending = 0, queue = Promise.resolve();
    const icon = complete => complete ? '✓' : '○';
    function validPortfolio() {
      try {return !!portfolio.value.trim() && ['http:', 'https:'].includes(new URL(portfolio.value.trim()).protocol) && portfolio.validity.valid;} catch {return false;}
    }
    function update() {
      let filled = 0;
      const requirements = application.querySelectorAll('[data-requirement]');
      requirements.forEach(li => {
        const key = li.dataset.requirement;
        const item = Array.from(application.querySelectorAll('[data-upload-item]')).find(el => el.dataset.uploadItem === key);
        const complete = key === 'Portofolio' ? validPortfolio() : item?.dataset.uploaded === '1';
        li.classList.toggle('complete', complete);
        li.querySelector('[data-requirement-icon]').textContent = icon(complete);
        if (complete) filled++;
      });
      application.querySelector('[data-completeness-count]').textContent = `${filled}/${requirements.length}`;
      application.querySelector('[data-completeness-progress]').value = filled;
      submit.disabled = !consent.checked || pending > 0;
      application.setAttribute('aria-busy', pending > 0 ? 'true' : 'false');
    }
    function baseData(action) {
      const data = new FormData();
      data.set('csrf', application.elements.namedItem('csrf').value);
      data.set('idLowongan', application.elements.namedItem('idLowongan').value);
      data.set('aksiForm', action);
      return data;
    }
    function enqueue(work) {
      pending++; update();
      queue = queue.then(work).catch(error => showError(error.name === 'AbortError' ? 'Unggahan melewati batas waktu. Pilih ulang berkas untuk mencoba kembali.' : error.message))
        .finally(() => {pending--; update();});
    }
    function syncItem(doc, key) {
      const source = Array.from(doc.querySelectorAll('[data-upload-item]')).find(el => el.dataset.uploadItem === key);
      const target = Array.from(application.querySelectorAll('[data-upload-item]')).find(el => el.dataset.uploadItem === key);
      if (!doc.querySelector('[data-application-form]') || !source || !target) throw new Error('Sesi formulir berakhir. Muat ulang halaman dan masuk kembali jika diperlukan.');
      target.dataset.uploaded = source.dataset.uploaded;
      target.querySelector('[data-upload-result]').replaceChildren(...Array.from(source.querySelector('[data-upload-result]').childNodes));
      const status = target.querySelector('[data-upload-status]');
      status.textContent = source.querySelector('[data-upload-status]').textContent;
      status.className = source.querySelector('[data-upload-status]').className;
    }
    application.addEventListener('change', event => {
      const input = event.target;
      if (!input.matches('[data-upload-key]') || !input.files.length) return;
      const file = input.files[0], key = input.dataset.uploadKey, item = input.closest('[data-upload-item]');
      input.value = '';
      enqueue(async () => {
        const status = item.querySelector('[data-upload-status]');
        try {
          if (!/\.pdf$/i.test(file.name) || file.size <= 0 || file.size > 5 * 1024 * 1024) throw new Error('Pilih berkas PDF maksimal 5 MB.');
          status.textContent = 'Mengunggah…'; status.className = 'badge blue';
          const data = baseData('unggah_otomatis');data.set(`dokumen[${key}]`, file);
          const doc = await requestHTML(application.action, {method:'POST', body:data});
          syncItem(doc, key);
        } catch (error) {
          status.textContent = item.dataset.uploaded === '1' ? 'Berkas sebelumnya tersimpan' : 'Unggahan gagal';
          status.className = item.dataset.uploaded === '1' ? 'badge green' : 'badge red';throw error;
        }
      });
    });
    application.addEventListener('click', event => {
      const button = event.target.closest('[data-remove-upload]');if (!button) return;
      const key = button.dataset.removeUpload;
      enqueue(async () => {const data = baseData('hapus_berkas'); data.set('jenisDokumen', key); syncItem(await requestHTML(application.action,{method:'POST',body:data}),key);});
    });
    application.addEventListener('submit', event => {
      if (pending) {event.preventDefault();showError('Tunggu pemeriksaan unggahan selesai sebelum melanjutkan.');return;}
      if (event.submitter?.value !== 'batal' && !consent.checked) {event.preventDefault();showError('Centang pernyataan kebenaran dokumen sebelum mengirim lamaran.', consent);}
    });
    consent.addEventListener('change', update);
    portfolio.addEventListener('input', update);
    update();
  }
  const job = document.querySelector('[data-job-form]');
  if (job) {
    function updateJob() {
      const value = key => (job.elements.namedItem(key)?.value || '').trim();
      const stages = Array.from(job.querySelectorAll('input[name^="tahap["]'));
      const groups = [!!value('namaLowongan') && !!value('lokasiPenempatan'),
        !!job.querySelector('input[name="targetJurusan[]"]:checked'), !!value('deskripsi'),
        !!job.querySelector('input[name="dokumenPersyaratan[]"]:checked') && stages.length > 0 && stages.every(input => input.value.trim())];
      const count = groups.filter(Boolean).length;
      job.querySelector('[data-job-count]').textContent = `${count} dari 4`;
      job.querySelector('[data-job-progress]').value = count;
      job.querySelectorAll('[data-job-requirement]').forEach((li, i) => {
        li.classList.toggle('complete', groups[i]);
        const svg = li.querySelector('svg');if (svg) svg.setAttribute('data-complete', String(groups[i]));
      });
    }
    job.addEventListener('input', updateJob);job.addEventListener('change', updateJob);updateJob();
  }
  const selection = document.querySelector('[data-selection-form]');
  if (selection) {
    const result = selection.elements.namedItem('hasil'), toggle = selection.elements.namedItem('aturJadwal');
    const area = selection.querySelector('[data-schedule-area]'), fields = selection.querySelector('[data-schedule-fields]');
    function updateSchedule(changed = false) {
      const hasNext = !!selection.dataset.nextStage;
      const final = result.value === 'Tidak Lolos' || (result.value === 'Lolos' && !hasNext);
      if (changed) toggle.checked = (result.value === 'Lolos' && hasNext) || result.value === 'Jadwal';
      if (final) toggle.checked = false;
      if (result.value === 'Jadwal') toggle.checked = true;
      area.hidden = final;toggle.disabled = final;
      fields.hidden = !toggle.checked;fields.disabled = !toggle.checked || final;
      selection.querySelectorAll('[data-schedule-required]').forEach(input => input.required = toggle.checked && !final);
      selection.querySelector('[data-schedule-stage]').textContent = result.value === 'Lolos' && hasNext ? selection.dataset.nextStage : selection.dataset.currentStage;
    }
    result.addEventListener('change', () => updateSchedule(true));toggle.addEventListener('change', () => updateSchedule());updateSchedule();
  }
  const chat = document.querySelector('[data-chat-url]');
  if (chat) {
    let stopped = false, errorShown = false;
    async function refreshChat() {
      if (stopped) return;
      if (document.hidden) {setTimeout(refreshChat, 4000);return;}
      try {
        const doc = await requestHTML(chat.dataset.chatUrl);
        if (!doc.querySelector('[data-chat-url]')) {stopped = true;throw new Error('Sesi percakapan berakhir. Silakan masuk kembali.');}
        const current = chat.querySelector('[data-chat-messages]'), fresh = doc.querySelector('[data-chat-messages]');
        if (current && fresh) {
          const oldIds = Array.from(current.querySelectorAll('[data-message-id]')).map(x => x.dataset.messageId).join(',');
          const newIds = Array.from(fresh.querySelectorAll('[data-message-id]')).map(x => x.dataset.messageId).join(',');
          if (oldIds !== newIds) {
            const atBottom = current.scrollHeight - current.scrollTop - current.clientHeight < 80;
            const top = current.scrollTop;
            current.replaceChildren(...Array.from(fresh.childNodes));
            current.scrollTop = atBottom ? current.scrollHeight : top;
          }
        }
        const list = chat.querySelector('.conversation-list'), newList = doc.querySelector('.conversation-list');
        if (list && newList && list.innerHTML !== newList.innerHTML) list.replaceChildren(...Array.from(newList.childNodes));
        errorShown = false;
      } catch (error) {
        if (!errorShown) {showError(error.name === 'AbortError' ? 'Koneksi pesan belum tersedia. Sistem akan mencoba kembali otomatis.' : error.message);errorShown = true;}
      } finally {if (!stopped) setTimeout(refreshChat, 4000);}
    }
    setTimeout(refreshChat, 4000);
  }
})();
