/**
 * SafeEdu Store & State Management
 * Persistent mock database with LocalStorage support for reports, technicians, and confirmation messages.
 */

const SafeEduStore = (() => {
  const STORAGE_KEY_REPORTS = 'safeedu_reports_v1';
  const STORAGE_KEY_CONFIRMATIONS = 'safeedu_confirmations_v1';

  // Initial Seed Data
  const initialReports = [
    {
      id: 'REP-2026-001',
      type: 'fasilitas', // 'fasilitas' | 'kode_etik'
      title: 'AC Bocor & Tidak Dingin di Lab Komputer 2',
      category: 'Sarana & Prasarana',
      location: 'Gedung B, Lantai 3, Lab 302',
      reporterName: 'Ahmad Fauzi (NISN: 20240911)',
      reporterClass: 'XII RPL 1',
      date: '2026-09-07 08:30',
      urgency: 'Tinggi',
      status: 'Menunggu Verifikasi', // 'Menunggu Verifikasi', 'Diteruskan ke Teknisi', 'Selesai'
      description: 'Pendingin ruangan meneteskan air deras tepat di atas meja PC server unit 4. Berisiko konsleting listrik.',
      assignedTechnician: null,
      adminNotes: '',
      isAnonymous: false
    },
    {
      id: 'REP-2026-002',
      type: 'kode_etik',
      title: 'Dugaan Perundungan Verbal Berulang di Koridor',
      category: 'Perundungan / Bullying',
      location: 'Koridor Utama Lantai 2',
      reporterName: 'Siswa Terlindungi (Anonim)',
      reporterClass: 'Kelas XI',
      date: '2026-09-07 10:15',
      urgency: 'Mendesak',
      status: 'Menunggu Verifikasi',
      description: 'Terdapat kelompok siswa yang sering mengintimidasi adik kelas saat jam istirahat kedua secara terstruktur.',
      assignedTechnician: null,
      adminNotes: '',
      isAnonymous: true
    },
    {
      id: 'REP-2026-003',
      type: 'fasilitas',
      title: 'Kran Air Wastafel Toilet Siswa Patah',
      category: 'Sanitasi & Air',
      location: 'Gedung A, Toilet Pria Lantai 1',
      reporterName: 'Rian Pratama (NISN: 20241005)',
      reporterClass: 'X TKJ 2',
      date: '2026-09-06 14:00',
      urgency: 'Sedang',
      status: 'Diteruskan ke Teknisi',
      description: 'Kran air patah dan terus mengalir sehingga membanjiri lantai koridor wudhu musholla.',
      assignedTechnician: 'Bambang Sudirjo (Divisi Perpipaan)',
      adminNotes: 'Prioritaskan penutupan katup utama dahulu.',
      isAnonymous: false
    },
    {
      id: 'REP-2026-004',
      type: 'fasilitas',
      title: 'Proyektor LCD Mati Total Saat Presentasi',
      category: 'Multimedia & IT',
      location: 'Gedung C, Ruang Kelas 104',
      reporterName: 'Dewi Lestari (NISN: 20241289)',
      reporterClass: 'XI MIPA 3',
      date: '2026-09-06 09:20',
      urgency: 'Sedang',
      status: 'Selesai',
      description: 'Lampu indikator berkedip merah dan unit tidak memancarkan cahaya layar.',
      assignedTechnician: 'Eko Prasetyo (Divisi IT & Multimedia)',
      adminNotes: 'Ganti lampu proyektor cadangan dari gudang.',
      isAnonymous: false
    },
    {
      id: 'REP-2026-005',
      type: 'kode_etik',
      title: 'Merokok & Vandalisme di Belakang Kantin Sekolah',
      category: 'Pelanggaran Ketertiban',
      location: 'Area Parkir Belakang Kantin',
      reporterName: 'Anonim',
      reporterClass: 'Siswa Kelas XII',
      date: '2026-09-05 15:45',
      urgency: 'Tinggi',
      status: 'Diteruskan ke Teknisi', // Diteruskan ke Tim Keamanan / Teknisi Pengawas CCTV
      description: 'Ditemukan puntung rokok dan coretan spidol di dinding panel listrik.',
      assignedTechnician: 'Suryono (Divisi Pengawas & Sarpras)',
      adminNotes: 'Periksa rekaman CCTV koridor 5 dan catat jam kejadian.',
      isAnonymous: true
    }
  ];

  const initialConfirmations = [
    {
      id: 'CONF-001',
      ticketId: 'REP-2026-003',
      taskTitle: 'Kran Air Wastafel Toilet Siswa Patah',
      technicianName: 'Bambang Sudirjo',
      division: 'Divisi Perpipaan & Sanitasi',
      actionStatus: 'Dalam Pengerjaan',
      timestamp: '2026-09-07 11:30',
      estimatedFinish: 'Hari ini pukul 16:00',
      notes: 'Katup utama sudah dimatikan sementara. Pipa fitting 1/2 inch sedang diambil dari logistik sarpras.',
      verifiedByAdmin: false
    },
    {
      id: 'CONF-002',
      ticketId: 'REP-2026-004',
      taskTitle: 'Proyektor LCD Mati Total Saat Presentasi',
      technicianName: 'Eko Prasetyo',
      division: 'Divisi IT & Multimedia',
      actionStatus: 'Pekerjaan Selesai',
      timestamp: '2026-09-06 13:45',
      estimatedFinish: 'Selesai',
      notes: 'Unit proyektor telah diganti bola lampu halogen dan kabel HDMI baru. Sudah dites uji display resolusi 1080p normal.',
      verifiedByAdmin: true
    },
    {
      id: 'CONF-003',
      ticketId: 'REP-2026-005',
      taskTitle: 'Merokok & Vandalisme di Belakang Kantin',
      technicianName: 'Suryono',
      division: 'Divisi Keamanan & CCTV',
      actionStatus: 'Investigasi Lapangan',
      timestamp: '2026-09-06 16:10',
      estimatedFinish: '2026-09-08 10:00',
      notes: 'Rekaman CCTV pukul 15:20-15:45 telah diekstrak dan diserahkan ke ruang kesiswaan/BK untuk identifikasi.',
      verifiedByAdmin: false
    }
  ];

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
