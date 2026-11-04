// Desain dashboard instruktur dibuat agar ringkas dan aman: semua interaksi dipindah ke JS eksternal agar tetap kompatibel dengan CSP tanpa inline handler.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-progress-bar').forEach(function (bar) {
    var value = Number(bar.dataset.progress || 0);
    if (Number.isNaN(value)) value = 0;
    bar.style.width = value + '%';
  });

  document.querySelectorAll('[data-confirm]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      var message = button.dataset.confirm || 'Apakah Anda yakin?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('.sidebar-item[aria-disabled="true"]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      event.preventDefault();
    });
  });

  var classForm = document.getElementById('class-form');
  var classModalElement = document.getElementById('classModal');
  if (classForm && classModalElement && window.bootstrap) {
    var classModal = window.bootstrap.Modal.getOrCreateInstance(classModalElement);
    var feedback = document.getElementById('class-form-feedback');
    var submitButton = document.getElementById('class-submit');
    var submitLabel = submitButton.querySelector('.js-submit-label');
    var spinner = submitButton.querySelector('.spinner-border');
    var classIdField = classForm.querySelector('[name="class_id"]');
    var iconUrlField = classForm.querySelector('[name="icon_url"]');
    var coverHelp = document.getElementById('current-class-cover');
    var activeClassEditId = '';

    function showFeedback(message, type) {
      feedback.className = 'alert alert-' + type;
      feedback.textContent = message;
    }

    function clearFeedback() {
      feedback.className = 'alert alert-danger d-none';
      feedback.textContent = '';
    }

    function setSubmitting(isSubmitting) {
      submitButton.disabled = isSubmitting;
      spinner.classList.toggle('d-none', !isSubmitting);
      submitLabel.textContent = isSubmitting ? 'Menyimpan...' : 'Simpan';
    }

    async function readJson(response) {
      try {
        return await response.json();
      } catch (error) {
        if (error.name === 'AbortError') {
          throw error;
        }
        throw new Error('Respons server tidak dapat dibaca. Silakan coba kembali.');
      }
    }

    function updateClassRow(classData) {
      var row = document.querySelector('[data-class-row="' + classData.id + '"]');
      if (!row) {
        window.location.reload();
        return;
      }

      var previousStatus = row.dataset.classStatus;
      var title = row.querySelector('.class-name-wrap strong');
      var description = row.querySelector('.class-name-wrap small');
      var category = row.querySelector('[data-class-category]');
      var statusBadge = row.querySelector('.status-badge');
      if (!title || !description || !category || !statusBadge) {
        window.location.reload();
        return;
      }

      title.textContent = classData.judul;
      description.textContent = classData.deskripsi;
      category.textContent = classData.kategori;
      statusBadge.className = 'status-badge status-' + classData.status;
      statusBadge.textContent = classData.status === 'aktif' ? 'Publish' : (classData.status === 'ditutup' ? 'Ditutup' : 'Draft');
      if (previousStatus !== classData.status) {
        var activeClassCount = document.querySelector('[data-stat="active-classes"]');
        if (activeClassCount) {
          var count = Number(activeClassCount.textContent) || 0;
          if (previousStatus === 'aktif') count--;
          if (classData.status === 'aktif') count++;
          activeClassCount.textContent = String(Math.max(0, count));
        }
      }
      row.dataset.classStatus = classData.status;
      var image = row.querySelector('.class-thumb img');
      if (classData.icon_url) {
        if (!image) {
          image = document.createElement('img');
          image.alt = 'Thumbnail kelas ' + classData.judul;
          row.querySelector('.class-thumb').replaceChildren(image);
        }
        image.src = classData.icon_url;
        image.alt = 'Thumbnail kelas ' + classData.judul;
      } else if (image) {
        var icon = document.createElement('i');
        icon.className = 'bi bi-journal-text';
        row.querySelector('.class-thumb').replaceChildren(icon);
      }
      var editButton = row.querySelector('.js-edit-class');
      if (editButton) {
        editButton.dataset.classId = classData.id;
      }
      var deleteButton = row.querySelector('.js-delete-class');
      if (deleteButton) {
        deleteButton.dataset.classTitle = classData.judul;
      }
    }

    classModalElement.addEventListener('show.bs.modal', async function (event) {
      classForm.reset();
      classIdField.value = '';
      iconUrlField.value = '';
      classForm.querySelector('[name="aksi"]').value = 'save_class';
      classForm.querySelector('[name="status"][value="draft"]').checked = true;
      coverHelp.textContent = 'JPG, PNG, atau WebP, maksimal 2 MB. Opsional.';
      setSubmitting(false);
      clearFeedback();

      var trigger = event.relatedTarget;
      var editId = trigger && trigger.classList.contains('js-edit-class') ? trigger.dataset.classId : '';
      activeClassEditId = editId;
      if (!editId) {
        document.getElementById('class-modal-title').textContent = 'Buat kelas baru';
        return;
      }

      document.getElementById('class-modal-title').textContent = 'Edit kelas';
      showFeedback('Memuat data kelas...', 'info');
      try {
        var url = new URL(classForm.action, window.location.href);
        url.searchParams.set('ajax', 'get_class');
        url.searchParams.set('id', editId);
        var response = await fetch(url.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        });
        var payload = await readJson(response);
        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'Gagal memuat data kelas.');
        }
        if (activeClassEditId !== editId || !classModalElement.classList.contains('show')) {
          return;
        }

        classIdField.value = payload.class.id;
        classForm.querySelector('[name="judul"]').value = payload.class.judul;
        classForm.querySelector('[name="kategori"]').value = payload.class.kategori;
        classForm.querySelector('[name="deskripsi"]').value = payload.class.deskripsi;
        classForm.querySelector('[name="durasi_estimasi"]').value = payload.class.durasi_estimasi || '';
        classForm.querySelector('[name="tingkat"]').value = payload.class.tingkat || 'Beginner';
        classForm.querySelector('[name="status"][value="' + payload.class.status + '"]').checked = true;
        classForm.querySelector('[name="enroll_key"]').value = payload.class.enroll_key || '';
        iconUrlField.value = payload.class.icon_url || '';
        coverHelp.textContent = payload.class.icon_url
          ? 'Cover saat ini dipertahankan jika Anda tidak memilih file baru.'
          : 'JPG, PNG, atau WebP, maksimal 2 MB. Opsional.';
        clearFeedback();
      } catch (error) {
        if (activeClassEditId === editId && classModalElement.classList.contains('show')) {
          showFeedback(error.message, 'danger');
        }
      }
    });

    classModalElement.addEventListener('hidden.bs.modal', function () {
      activeClassEditId = '';
      classForm.reset();
      classIdField.value = '';
      iconUrlField.value = '';
      setSubmitting(false);
      document.getElementById('class-modal-title').textContent = 'Buat kelas baru';
      coverHelp.textContent = 'JPG, PNG, atau WebP, maksimal 2 MB. Opsional.';
      clearFeedback();
    });

    classForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!classForm.reportValidity()) {
        return;
      }

      clearFeedback();
      setSubmitting(true);
      var formData = new FormData(classForm);
      var isEditing = classIdField.value !== '';
      try {
        var response = await fetch(classForm.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        });
        var payload = await readJson(response);
        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'Kelas gagal disimpan.');
        }

        if (isEditing && payload.class) {
          updateClassRow(payload.class);
          classModal.hide();
        } else {
          window.location.assign(classForm.action);
        }
      } catch (error) {
        showFeedback(error.message, 'danger');
        setSubmitting(false);
      }
    });

    document.querySelectorAll('.js-delete-class').forEach(function (button) {
      button.addEventListener('click', async function () {
        var title = button.dataset.classTitle || 'kelas ini';
        if (!window.confirm('Hapus "' + title + '"? Semua materi dan data peserta di kelas ini ikut terhapus.')) {
          return;
        }

        button.disabled = true;
        var formData = new FormData();
        formData.append('aksi', 'delete_class');
        formData.append('ajax', '1');
        formData.append('class_id', button.dataset.classId);
        formData.append('csrf_token', classForm.querySelector('[name="csrf_token"]').value);
        try {
          var response = await fetch(classForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
          });
          var payload = await readJson(response);
          if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Kelas gagal dihapus.');
          }

          window.location.assign(classForm.action);
        } catch (error) {
          button.disabled = false;
          window.alert(error.message);
        }
      });
    });
  }

  var manageModal = document.getElementById('manageClassModal');
  var manageFeedback = document.getElementById('manage-class-feedback');
  var manageModuleForm = document.getElementById('manage-module-form');
  var currentManageClassId = '';
  var csrfToken = classForm ? classForm.querySelector('[name="csrf_token"]').value : '';

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function showManageFeedback(message, type) {
    if (!manageFeedback) return;
    manageFeedback.className = 'alert alert-' + type;
    manageFeedback.textContent = message;
  }

  function resetManageModuleForm() {
    if (!manageModuleForm) return;
    manageModuleForm.reset();
    manageModuleForm.querySelector('[name="module_id"]').value = '';
    manageModuleForm.querySelector('[data-module-access-key-field]').classList.add('d-none');
    document.getElementById('manage-module-form-title').textContent = 'Tambah modul';
    document.getElementById('manage-module-submit').textContent = 'Simpan modul';
    manageModuleForm.querySelector('[data-cancel-module-edit]').classList.add('d-none');
  }

  async function fetchJson(url, options) {
    var response = await fetch(url, options);
    var payload;
    try {
      payload = await response.json();
    } catch (error) {
      if (error.name === 'AbortError') {
        throw error;
      }
      throw new Error('Respons server tidak dapat dibaca.');
    }
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Permintaan gagal diproses.');
    }
    return payload;
  }

  async function postDashboardForm(formData, signal) {
    formData.append('ajax', '1');
    formData.append('class_id', currentManageClassId);
    formData.append('csrf_token', csrfToken);
    var options = {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    };
    if (signal) {
      options.signal = signal;
    }
    return fetchJson('DashboardController.php', options);
  }

  function renderManageDetails(payload) {
    var moduleSelect = manageModal.querySelector('[data-module-select]');
    var moduleList = document.getElementById('manage-module-list');
    var overviewForm = document.getElementById('manage-overview-form');
    var overviewModules = document.getElementById('manage-overview-modules');
    var quizList = document.getElementById('manage-quiz-list');
    var studentList = document.getElementById('manage-student-list');
    moduleSelect.innerHTML = '<option value="">Pilih modul</option>';
    if (overviewForm && payload.class) {
      overviewForm.querySelector('[name="kategori"]').value = payload.class.kategori || 'Umum';
      overviewForm.querySelector('[name="deskripsi"]').value = payload.class.deskripsi || '';
      overviewForm.querySelector('[name="durasi_estimasi"]').value = payload.class.durasi_estimasi || '';
      overviewForm.querySelector('[name="tingkat"]').value = payload.class.tingkat || 'Beginner';
      overviewForm.querySelector('[name="badge_label"]').value = payload.class.badge_label || '';
      overviewForm.querySelector('[name="badge_color"]').value = payload.class.badge_color || 'purple';
      overviewForm.querySelector('[name="badge_aktif"]').checked = Number(payload.class.badge_aktif) === 1;
    }

    if (!payload.modules.length) {
      moduleList.innerHTML = '<p class="text-secondary">Belum ada modul atau materi.</p>';
      overviewModules.innerHTML = '<p class="text-secondary mb-0">Belum ada modul. Tambahkan nama modul di tab Materi.</p>';
    } else {
      overviewModules.innerHTML = '<ol>' + payload.modules.map(function (module) {
        return '<li>' + escapeHtml(module.judul) + '</li>';
      }).join('') + '</ol>';
      moduleList.innerHTML = payload.modules.map(function (module) {
        moduleSelect.insertAdjacentHTML('beforeend', '<option value="' + Number(module.id) + '">' + escapeHtml(module.judul) + '</option>');
        var materials = (module.materi || []).map(function (material) {
          var link = /^(video|dokumen)$/.test(material.tipe) && String(material.isi).indexOf('../../uploads/') === 0
            ? '<a href="' + escapeHtml(material.isi) + '" target="_blank" rel="noopener">Buka file</a>'
            : '<span>' + escapeHtml(material.isi) + '</span>';
          return '<li><strong>' + escapeHtml(material.judul) + '</strong> <small>(' + escapeHtml(material.tipe) + ')</small><div class="manage-content-preview">' + link + '</div></li>';
        }).join('');
        var accessBadge = module.enroll_key
          ? '<span class="badge text-bg-secondary">Privat</span>'
          : '<span class="badge text-bg-success">Publik</span>';
        var editModuleButton = '<button type="button" class="secondary-button compact" data-edit-module' +
          ' data-id="' + Number(module.id) + '"' +
          ' data-title="' + escapeHtml(module.judul) + '"' +
          ' data-description="' + escapeHtml(module.deskripsi || '') + '"' +
          ' data-key="' + escapeHtml(module.enroll_key || '') + '">Edit modul</button>';
        return '<article class="manage-list-card"><div class="d-flex justify-content-between align-items-start gap-2"><h4>' + escapeHtml(module.judul) + '</h4>' + accessBadge + '</div><p>' + escapeHtml(module.deskripsi || '') + '</p>' + editModuleButton +
          (materials ? '<ul>' + materials + '</ul>' : '<p class="text-secondary mb-0">Belum ada materi.</p>') + '</article>';
      }).join('');
    }
    moduleList.querySelectorAll('[data-edit-module]').forEach(function (button) {
      button.addEventListener('click', function () {
        manageModuleForm.querySelector('[name="module_id"]').value = button.dataset.id;
        manageModuleForm.querySelector('[name="module_judul"]').value = button.dataset.title;
        manageModuleForm.querySelector('[name="module_deskripsi"]').value = button.dataset.description;
        manageModuleForm.querySelector('[name="module_enroll_key"]').value = button.dataset.key;
        manageModuleForm.querySelector('[data-module-access-key-field]').classList.remove('d-none');
        document.getElementById('manage-module-form-title').textContent = 'Edit modul';
        document.getElementById('manage-module-submit').textContent = 'Simpan perubahan';
        manageModuleForm.querySelector('[data-cancel-module-edit]').classList.remove('d-none');
        manageModuleForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    var quizzesMarkup = payload.quizzes.map(function (quiz) {
      return '<article class="manage-list-card"><h4>' + escapeHtml(quiz.judul) + '</h4><p>' + Number(quiz.total_soal || 0) + ' soal pilihan ganda</p></article>';
    }).join('');
    var assignmentsMarkup = payload.assignments.map(function (assignment) {
      var submissions = (assignment.submissions || []).map(function (submission) {
        return '<li>' + escapeHtml(submission.student_name) + ' · <a href="' + escapeHtml(submission.file_path) + '" target="_blank" rel="noopener">' + escapeHtml(submission.file_name) + '</a></li>';
      }).join('');
      return '<article class="manage-list-card"><h4>' + escapeHtml(assignment.title) + '</h4><p>' + escapeHtml(assignment.description) + '</p>' +
        (assignment.due_at ? '<small>Tenggat: ' + escapeHtml(assignment.due_at) + '</small>' : '') +
        '<p class="mb-1 mt-2"><strong>' + Number(assignment.submission_count || 0) + ' file dikumpulkan</strong></p>' +
        (submissions ? '<ul>' + submissions + '</ul>' : '') + '</article>';
    }).join('');
    quizList.innerHTML = quizzesMarkup + assignmentsMarkup || '<p class="text-secondary">Belum ada kuis atau tugas.</p>';

    if (!payload.students.length) {
      studentList.innerHTML = '<p class="p-3 text-secondary mb-0">Belum ada siswa yang terdaftar.</p>';
    } else {
      studentList.innerHTML = '<table class="student-table"><thead><tr><th>Nama siswa</th><th>Email</th><th>Progress</th><th>Status</th></tr></thead><tbody>' +
        payload.students.map(function (student) {
          var progress = Math.max(0, Math.min(100, Number(student.progress || 0)));
          return '<tr><td>' + escapeHtml(student.nama) + '</td><td>' + escapeHtml(student.email) + '</td><td>' +
            '<div class="mini-progress js-manage-progress" data-progress="' + progress + '"><span></span></div><small>' + progress + '%</small></td>' +
            '<td>' + escapeHtml(student.status) + '</td></tr>';
        }).join('') + '</tbody></table>';
    }
    studentList.querySelectorAll('.js-manage-progress').forEach(function (bar) {
      bar.querySelector('span').style.width = bar.dataset.progress + '%';
    });
  }

  async function refreshManageDetails(expectedRequestId) {
    if (!currentManageClassId) return;
    var requestedClassId = currentManageClassId;
    var url = new URL('DashboardController.php', window.location.href);
    url.searchParams.set('ajax', 'class_detail');
    url.searchParams.set('id', requestedClassId);
    var payload = await fetchJson(url.toString(), { credentials: 'same-origin' });
    if (currentManageClassId !== requestedClassId || !manageModal.classList.contains('show')
      || (expectedRequestId !== undefined && expectedRequestId !== manageRequestId)) {
      return;
    }
    document.getElementById('manage-class-title').textContent = 'Kelola: ' + payload.class.judul;
    renderManageDetails(payload);
  }

  if (manageModal) {
    var manageRequestId = 0;
    manageModal.addEventListener('show.bs.modal', async function (event) {
      var trigger = event.relatedTarget;
      currentManageClassId = trigger && trigger.classList.contains('js-manage-class') ? trigger.dataset.classId : '';
      var requestId = ++manageRequestId;
      showManageFeedback('Memuat data kelas...', 'info');
      try {
        await refreshManageDetails(requestId);
        if (requestId !== manageRequestId || !manageModal.classList.contains('show')) return;
        manageFeedback.classList.add('d-none');
      } catch (error) {
        if (requestId === manageRequestId && manageModal.classList.contains('show')) {
          showManageFeedback(error.message, 'danger');
        }
      }
    });

    manageModal.addEventListener('hidden.bs.modal', function () {
      manageRequestId++;
      currentManageClassId = '';
      manageModal.querySelectorAll('form').forEach(function (form) {
        form.reset();
      });
      resetManageModuleForm();
      manageModal.querySelectorAll('.manage-form-card button[type="submit"]').forEach(function (button) {
        button.disabled = false;
      });
      manageModal.querySelector('[data-module-select]').innerHTML = '<option value="">Pilih modul</option>';
      document.getElementById('manage-overview-modules').innerHTML = '<p class="text-secondary mb-0">Pilih kelas untuk melihat daftar modul.</p>';
      document.getElementById('manage-module-list').innerHTML = '<p class="text-secondary">Pilih kelas untuk melihat modul dan materi.</p>';
      document.getElementById('manage-quiz-list').innerHTML = '<p class="text-secondary">Pilih kelas untuk melihat kuis dan tugas.</p>';
      document.getElementById('manage-student-list').innerHTML = '<p class="p-3 text-secondary mb-0">Pilih kelas untuk melihat daftar siswa.</p>';
      manageFeedback.className = 'alert d-none';
      manageFeedback.textContent = '';
      var firstTabButton = manageModal.querySelector('[data-bs-toggle="tab"]');
      if (firstTabButton && window.bootstrap) {
        window.bootstrap.Tab.getOrCreateInstance(firstTabButton).show();
      }
    });

    manageModal.querySelectorAll('.js-manage-form').forEach(function (form) {
      var cancelButton = form.querySelector('[data-cancel-upload]');
      var requestController = null;
      var materialFileField = form.querySelector('[name="material_file"]');
      var materialTypeField = form.querySelector('[name="material_tipe"]');
      if (materialFileField && materialTypeField) {
        materialFileField.addEventListener('change', function () {
          var selectedFile = materialFileField.files[0];
          if (!selectedFile) return;
          if (selectedFile.type.indexOf('image/') === 0) {
            materialTypeField.value = 'dokumen';
          } else if (selectedFile.type.indexOf('video/') === 0) {
            materialTypeField.value = 'video';
          }
        });
      }

      if (cancelButton) {
        cancelButton.addEventListener('click', function () {
          if (requestController) {
            requestController.abort();
            cancelButton.disabled = true;
          }
        });
      }

      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        var button = form.querySelector('[type="submit"]');
        var isMaterialForm = form.dataset.action === 'save_material';
        requestController = isMaterialForm ? new AbortController() : null;
        button.disabled = true;
        if (isMaterialForm) {
          button.textContent = 'Mengirim...';
          cancelButton.classList.remove('d-none');
          cancelButton.disabled = false;
        }
        try {
          var formData = new FormData(form);
          formData.set('aksi', form.dataset.action);
          var payload = await postDashboardForm(formData, requestController ? requestController.signal : undefined);
          form.reset();
          if (form === manageModuleForm) resetManageModuleForm();
          await refreshManageDetails(manageRequestId);
          showManageFeedback(payload.message, 'success');
        } catch (error) {
          if (error.name === 'AbortError') {
            showManageFeedback('Pengiriman dibatalkan. Jika server sudah menerima seluruh file, materi mungkin sudah tersimpan.', 'info');
          } else {
            showManageFeedback(error.message, 'danger');
          }
        } finally {
          button.disabled = false;
          if (isMaterialForm) {
            button.textContent = 'Simpan materi';
            cancelButton.classList.add('d-none');
            cancelButton.disabled = false;
            requestController = null;
          }
        }
      });
    });

    manageModuleForm.querySelector('[data-cancel-module-edit]').addEventListener('click', resetManageModuleForm);

    var quizForm = document.getElementById('manage-quiz-form');
    quizForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!quizForm.reportValidity()) return;
      var button = quizForm.querySelector('[type="submit"]');
      button.disabled = true;
      try {
        var quizData = new FormData(quizForm);
        quizData.set('aksi', 'save_quiz_with_question');
        await postDashboardForm(quizData);
        quizForm.reset();
        await refreshManageDetails(manageRequestId);
        showManageFeedback('Kuis dan pertanyaan berhasil dibuat.', 'success');
      } catch (error) {
        showManageFeedback(error.message, 'danger');
      } finally {
        button.disabled = false;
      }
    });
  }

  var chartCanvas = document.getElementById('registration-chart');
  if (chartCanvas && window.Chart) {
    try {
      var labels = JSON.parse(chartCanvas.dataset.labels || '[]');
      var values = JSON.parse(chartCanvas.dataset.values || '[]');
      new window.Chart(chartCanvas, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Siswa terdaftar',
            data: values,
            borderColor: '#3d755c',
            backgroundColor: 'rgba(78, 143, 109, 0.14)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
    } catch (error) {
      console.error('Grafik pendaftaran tidak dapat ditampilkan.', error);
    }
  }
});
