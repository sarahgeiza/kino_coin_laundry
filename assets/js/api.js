/**
 * Kino Coin Laundry - Client API Service Module
 * Siap untuk integrasi penuh Frontend <-> Backend Sarah & PostgreSQL
 *
 * Pengembang Frontend: Nizar Maulana Ramzy (zyy)
 * Pengembang Backend: Sarah Geiza
 * Router Backend: api/index.php?module={module}&action={action}
 */

const API_CONFIG = {
    // Ubah USE_BACKEND_API ke 'true' untuk mengaktifkan koneksi live ke PostgreSQL backend Sarah
    USE_BACKEND_API: false,
    
    // Auto-detect base path ke api/index.php
    BASE_URL: (window.location.pathname.includes('/kasir/') || 
               window.location.pathname.includes('/admin/') || 
               window.location.pathname.includes('/pelanggan/'))
               ? '../api/index.php' 
               : 'api/index.php',

    HEADERS: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
};

const KinoAPI = {
    // 1. Autentikasi Pengguna & Staf
    async login(username, password) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=auth&action=login`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify({ username, password })
            });
            return await res.json();
        }
        return { success: true, username };
    },

    async getMe() {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=auth&action=me`);
            return await res.json();
        }
        return { user: { nama: 'Daniel Afifi', role: 'kasir' } };
    },

    async logout() {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=auth&action=logout`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS
            });
            return await res.json();
        }
        return { success: true };
    },

    // 2. Katalog Layanan
    async getServices() {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=layanan&action=list`);
            return await res.json();
        }
        return window.__MOCK_SERVICES || [];
    },

    // 3. Pelanggan & Member
    async searchMember(query = '') {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=pelanggan&action=list&q=${encodeURIComponent(query)}`);
            const json = await res.json();
            return json.data || [];
        }
        const members = window.__MOCK_MEMBERS || [];
        const q = query.toLowerCase().trim();
        return members.filter(m => 
            m.nama.toLowerCase().includes(q) || 
            m.wa.includes(q) || 
            m.kode_kartu.toLowerCase().includes(q)
        );
    },

    async createMember(nama, noHp) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=pelanggan&action=create`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify({ nama_pelanggan: nama, no_hp: noHp })
            });
            return await res.json();
        }
        return { success: true, data: { nama_pelanggan: nama, no_hp: noHp } };
    },

    // 4. Transaksi
    async getTransactions(page = 1) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=transaksi&action=list&page=${page}`);
            const json = await res.json();
            return json.data || [];
        }
        return window.__MOCK_TRANSACTIONS || [];
    },

    async createTransaction(payload) {
        /**
         * Payload format sesuai controller Sarah:
         * {
         *   no_hp: '08123456789',
         *   nama_pelanggan: 'Budi Santoso',
         *   id_shift: 2,
         *   jenis_pembayaran: 'cash' | 'qris',
         *   detail_transaksi: [
         *     { id_layanan: 1, jumlah_koin: 1, berat: 5.0 }
         *   ]
         * }
         */
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=transaksi&action=create`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify(payload)
            });
            return await res.json();
        }
        return { success: true, id: 'INV-2026-00' + Math.floor(Math.random() * 900 + 100), data: payload };
    },

    // 5. Dashboard & Rekapitulasi Shift (Audit Pemilik)
    async getDashboardMetrics(startDate = '', endDate = '') {
        if (API_CONFIG.USE_BACKEND_API) {
            let url = `${API_CONFIG.BASE_URL}?module=dashboard&action=index`;
            if (startDate) url += `&start=${startDate}`;
            if (endDate) url += `&end=${endDate}`;
            const res = await fetch(url);
            return await res.json();
        }
        return { data: { jumlah_transaksi: 18, pendapatan: 425000, total_koin: 50 } };
    },

    // 6. Tracking Mandiri Pelanggan
    async trackOrder(noHp) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=riwayat&action=tracking`, {
                method: 'POST',
                headers: API_CONFIG.HEADERS,
                body: JSON.stringify({ no_hp: noHp })
            });
            return await res.json();
        }
        return { success: true, data: [] };
    }
};

window.KinoAPI = KinoAPI;
