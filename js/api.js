/**
 * Kino Coin Laundry - Client API Service Module
 * Disiapkan khusus untuk integrasi Frontend <-> Backend
 * 
 * Pengembang Frontend: Nizar Maulana Ramzy
 * Pengembang Backend: Sarah Geiza
 * Branch Integrasi: check
 */

const API_CONFIG = {
    USE_BACKEND_API: false, // Set 'true' saat controller backend Sarah siap di branch check
    BASE_URL: '../backend/controllers', // Path controller backend Sarah
    HEADERS: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
};

const KinoAPI = {
    // 1. Autentikasi
    async login(role, username, password) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}/auth.php?action=login`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify({ role, username, password })
            });
            return await res.json();
        }
        return { success: true, role, username };
    },

    // 2. Layanan (Master)
    async getServices(category = 'ALL') {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}/layanan.php?action=list&cat=${category}`);
            return await res.json();
        }
        return window.__MOCK_SERVICES || [];
    },

    // 3. Pelanggan & Member
    async searchMember(query) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}/pelanggan.php?action=search&q=${encodeURIComponent(query)}`);
            return await res.json();
        }
        const members = window.__MOCK_MEMBERS || [];
        const q = query.toLowerCase().trim();
        return members.filter(m => 
            m.nama.toLowerCase().includes(q) || 
            m.wa.includes(q) || 
            m.kode_kartu.toLowerCase().includes(q)
        );
    },

    async registerMember(memberData) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}/pelanggan.php?action=register`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify(memberData)
            });
            return await res.json();
        }
        return { success: true, data: memberData };
    },

    // 4. Transaksi
    async createTransaction(trxData) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}/transaksi.php?action=create`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify(trxData)
            });
            return await res.json();
        }
        return { success: true, id: 'INV-2026-00' + Math.floor(Math.random() * 900 + 100), data: trxData };
    },

    // 5. Presensi & Shift
    async presensiCheckin(kasir, shift) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}/presensi.php?action=checkin`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify({ kasir, shift })
            });
            return await res.json();
        }
        return { success: true, status: 'Berjalan' };
    },

    async tutupShift(shiftId, rekapData) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}/shift.php?action=tutup`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify({ shiftId, ...rekapData })
            });
            return await res.json();
        }
        return { success: true, status: 'Closed' };
    }
};

window.KinoAPI = KinoAPI;
