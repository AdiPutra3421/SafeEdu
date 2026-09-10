/**
 * SafeEdu Store & State Management
 * Persistent mock database with LocalStorage support for reports, technicians, and confirmation messages.
 */

const SafeEduStore = (() => {
  const STORAGE_KEY_REPORTS = 'safeedu_reports_v2';
  const STORAGE_KEY_CONFIRMATIONS = 'safeedu_confirmations_v2';

  // Initial Seed Data
  const initialReports = [];
  const initialConfirmations = [];

  function getReports() {
    const raw = localStorage.getItem(STORAGE_KEY_REPORTS);
    if (!raw) {
      localStorage.setItem(STORAGE_KEY_REPORTS, JSON.stringify(initialReports));
      return [...initialReports];
    }
    try {
      return JSON.parse(raw);
    } catch (e) {
      return [...initialReports];
    }
  }

  function saveReports(reports) {
    localStorage.setItem(STORAGE_KEY_REPORTS, JSON.stringify(reports));
  }

  function getConfirmations() {
    const raw = localStorage.getItem(STORAGE_KEY_CONFIRMATIONS);
    if (!raw) {
      localStorage.setItem(STORAGE_KEY_CONFIRMATIONS, JSON.stringify(initialConfirmations));
      return [...initialConfirmations];
    }
    try {
      return JSON.parse(raw);
    } catch (e) {
      return [...initialConfirmations];
    }
  }

  function saveConfirmations(confs) {
    localStorage.setItem(STORAGE_KEY_CONFIRMATIONS, JSON.stringify(confs));
  }

  function dispatchToTechnician(reportId, technicianName, adminNotes) {
    const reports = getReports();
    const idx = reports.findIndex(r => r.id === reportId);
    if (idx !== -1) {
      reports[idx].status = 'Diteruskan ke Teknisi';
      reports[idx].assignedTechnician = technicianName;
      reports[idx].adminNotes = adminNotes || '';
      saveReports(reports);

      // Otomatis buat entri konfirmasi awal dari teknisi
      const confirmations = getConfirmations();
      const newConf = {
        id: 'CONF-' + Math.floor(100 + Math.random() * 900),
        ticketId: reports[idx].id,
        taskTitle: reports[idx].title,
        technicianName: technicianName,
        division: technicianName.includes('IT') ? 'Divisi IT & Multimedia' : (technicianName.includes('Perpipaan') ? 'Divisi Sanitasi' : 'Divisi Sarpras'),
        actionStatus: 'Tugas Diterima',
        timestamp: new Date().toLocaleString('id-ID', { hour12: false }),
        estimatedFinish: 'Estimasi 24 Jam Kerja',
        notes: 'Tiket berhasil didisposisi oleh Admin dengan instruksi: "' + (adminNotes || 'Segera tindak lanjuti.') + '"',
        verifiedByAdmin: false
      };
      confirmations.unshift(newConf);
      saveConfirmations(confirmations);
      return true;
    }
    return false;
  }

  function approveConfirmation(confId) {
    const confs = getConfirmations();
    const conf = confs.find(c => c.id === confId);
    if (conf) {
      conf.verifiedByAdmin = true;
      saveConfirmations(confs);

      // Jika selesai, update juga status report
      const reports = getReports();
      const report = reports.find(r => r.id === conf.ticketId);
      if (report && conf.actionStatus === 'Pekerjaan Selesai') {
        report.status = 'Selesai';
        saveReports(reports);
      }
      return true;
    }
    return false;
  }

  function getStats() {
    const reports = getReports();
    const total = reports.length;
    const fasilitasCount = reports.filter(r => r.type === 'fasilitas').length;
    const ethicsCount = reports.filter(r => r.type === 'kode_etik').length;
    const pendingCount = reports.filter(r => r.status === 'Menunggu Verifikasi').length;
    const dispatchedCount = reports.filter(r => r.status === 'Diteruskan ke Teknisi').length;
    const resolvedCount = reports.filter(r => r.status === 'Selesai').length;

    return {
      total,
      fasilitasCount,
      ethicsCount,
      pendingCount,
      dispatchedCount,
      resolvedCount,
      responseRate: total > 0 ? Math.round(((dispatchedCount + resolvedCount) / total) * 100) : 100
    };
  }

  return {
    getReports,
    saveReports,
    getConfirmations,
    saveConfirmations,
    dispatchToTechnician,
    approveConfirmation,
    getStats
  };
})();
