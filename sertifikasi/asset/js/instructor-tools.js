document.addEventListener('DOMContentLoaded', function () {
  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  async function readJson(response) {
    var payload;
    try {
      payload = await response.json();
    } catch (error) {
      throw new Error('Respons server tidak dapat dibaca.');
    }
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Permintaan gagal diproses.');
    }
    return payload;
  }

  function showAlert(element, message, type) {
    element.className = 'alert alert-' + type;
    element.textContent = message;
  }

  var completionCanvas = document.getElementById('completion-chart');
  if (completionCanvas && window.Chart) {
    try {
      var completionLabels = JSON.parse(completionCanvas.dataset.labels || '[]');
      var completionValues = JSON.parse(completionCanvas.dataset.values || '[]');
      new window.Chart(completionCanvas, {
        type: 'bar',
        data: {
          labels: completionLabels,
          datasets: [{
            label: 'Progres materi (%)',
            data: completionValues,
            backgroundColor: 'rgba(55, 137, 99, 0.72)',
            borderColor: '#378963',
            borderWidth: 1,
            borderRadius: 5
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              beginAtZero: true,
              max: 100,
              ticks: { callback: function (value) { return value + '%'; } }
            }
          }
        }
      });
    } catch (error) {
      console.error('Grafik progres peserta tidak dapat ditampilkan.', error);
    }
  }

  var forumList = document.getElementById('forum-thread-list');
  var forumDetail = document.getElementById('forum-detail-content');
  var forumEmpty = document.getElementById('forum-empty-detail');
  var replyForm = document.getElementById('forum-reply-form');
  if (forumList && replyForm) {
    var selectedThreadId = '';
    var classFilter = document.getElementById('forum-class-filter');
    var statusFilter = document.getElementById('forum-status-filter');
    var replyFeedback = document.getElementById('forum-reply-feedback');

    async function loadThreads() {
      forumList.innerHTML = '<p class="text-secondary p-3">Memuat pertanyaan...</p>';
      var url = new URL('ForumController.php', window.location.href);
      url.searchParams.set('ajax', 'threads');
      url.searchParams.set('class_id', classFilter.value);
      url.searchParams.set('status', statusFilter.value);
      var response = await fetch(url.toString(), { credentials: 'same-origin' });
      var payload = await readJson(response);
      document.getElementById('unanswered-count').textContent = payload.unanswered;
      if (!payload.threads.length) {
        forumList.innerHTML = '<p class="text-secondary p-3 mb-0">Tidak ada pertanyaan untuk filter ini.</p>';
        return;
      }
      forumList.innerHTML = payload.threads.map(function (thread) {
        var selected = String(thread.id) === String(selectedThreadId);
        return '<button type="button" class="forum-thread-card ' + (selected ? 'active' : '') + '" data-thread-id="' + Number(thread.id) + '">' +
          '<span class="forum-thread-meta"><strong>' + escapeHtml(thread.student_name) + '</strong><span>' + escapeHtml(thread.created_at) + '</span></span>' +
          '<strong class="forum-thread-title">' + escapeHtml(thread.title) + '</strong>' +
          '<span class="forum-thread-meta"><span>' + escapeHtml(thread.class_title) + '</span><span class="forum-thread-status ' + escapeHtml(thread.status) + '">' +
          (thread.status === 'answered' ? 'Dijawab' : 'Belum dijawab') + '</span></span></button>';
      }).join('');
    }

    async function loadThread(threadId) {
      selectedThreadId = String(threadId);
      var url = new URL('ForumController.php', window.location.href);
      url.searchParams.set('ajax', 'thread');
      url.searchParams.set('id', threadId);
      var response = await fetch(url.toString(), { credentials: 'same-origin' });
      var payload = await readJson(response);
      var thread = payload.thread;
      document.getElementById('forum-question-title').textContent = thread.title;
      document.getElementById('forum-student-name').textContent = thread.student_name;
      document.getElementById('forum-student-avatar').textContent = thread.student_name.slice(0, 1).toUpperCase();
      document.getElementById('forum-class-name').textContent = thread.class_title;
      document.getElementById('forum-question-time').textContent = thread.created_at;
      document.getElementById('forum-thread-id').value = thread.id;
      var messages = '<article class="forum-message student-message"><strong>' + escapeHtml(thread.student_name) + '</strong><p>' + escapeHtml(thread.question) + '</p></article>';
      messages += thread.replies.map(function (reply) {
        var content = '<p>' + escapeHtml(reply.message) + '</p>';
        if (reply.code_snippet) content += '<pre><code>' + escapeHtml(reply.code_snippet) + '</code></pre>';
        if (reply.attachment_path) content += '<a href="' + escapeHtml(reply.attachment_path) + '" target="_blank" rel="noopener">' + escapeHtml(reply.attachment_name || 'Lampiran') + '</a>';
        return '<article class="forum-message instructor-message"><strong>' + escapeHtml(reply.author_name) + ' · Instruktur</strong>' + content + '<time>' + escapeHtml(reply.created_at) + '</time></article>';
      }).join('');
      document.getElementById('forum-messages').innerHTML = messages;
      forumEmpty.classList.add('d-none');
      forumDetail.classList.remove('d-none');
      await loadThreads();
    }

    forumList.addEventListener('click', async function (event) {
      var button = event.target.closest('[data-thread-id]');
      if (!button) return;
      try {
        await loadThread(button.dataset.threadId);
        replyFeedback.classList.add('d-none');
      } catch (error) {
        showAlert(replyFeedback, error.message, 'danger');
      }
    });

    [classFilter, statusFilter].forEach(function (select) {
      select.addEventListener('change', function () {
        loadThreads().catch(function (error) {
          forumList.textContent = error.message;
        });
      });
    });

    replyForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!selectedThreadId) {
        showAlert(replyFeedback, 'Pilih pertanyaan yang akan dibalas.', 'warning');
        return;
      }
      var button = replyForm.querySelector('[type="submit"]');
      button.disabled = true;
      try {
        var response = await fetch('ForumController.php', {
          method: 'POST',
          body: new FormData(replyForm),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        });
        var payload = await readJson(response);
        var threadId = selectedThreadId;
        replyForm.reset();
        document.getElementById('forum-thread-id').value = threadId;
        showAlert(replyFeedback, payload.message, 'success');
        await loadThread(threadId);
      } catch (error) {
        showAlert(replyFeedback, error.message, 'danger');
      } finally {
        button.disabled = false;
      }
    });

    loadThreads().catch(function (error) {
      forumList.textContent = error.message;
    });
  }
});
