import sys

content = open('resources/views/konfirmasi.blade.php', 'r', encoding='utf-8').read()

old_filter = """      const filtered = confData.filter(item => {
        if (currentConfFilter === 'in_progress') return item.status !== 'selesai';
        if (currentConfFilter === 'completed') return item.status === 'selesai';
        if (currentConfFilter === 'unverified') return !item.verified;
        return true;
      });"""

new_filter = """      const filtered = confData.filter(item => {
        if (currentConfFilter === 'in_progress') return item.status === 'diterima';
        if (currentConfFilter === 'completed') return item.status === 'selesai';
        if (currentConfFilter === 'unverified') return item.status === 'selesai' && !item.verified;
        if (currentConfFilter === 'unconfirmed') return item.status === 'ditugaskan';
        return true;
      });"""

content = content.replace(old_filter, new_filter, 1)

old_badge = """      let statusBadge = '';
      if (isFinished) {
        statusBadge = `<span class="badge badge-resolved"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Selesai</span>`;
      } else {
        statusBadge = `<span class="badge badge-dispatched">${item.status}</span>`;
      }"""

new_badge = """      let statusBadge = '';
      if (isFinished) {
        statusBadge = `<span class="badge badge-resolved"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Selesai</span>`;
      } else if (item.status === 'ditugaskan') {
        statusBadge = `<span class="badge" style="background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5;">Belum Dikonfirmasi</span>`;
      } else {
        statusBadge = `<span class="badge badge-dispatched">Dalam Pengerjaan</span>`;
      }"""

content = content.replace(old_badge, new_badge, 1)

with open('resources/views/konfirmasi.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
