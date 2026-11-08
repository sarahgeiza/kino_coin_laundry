/**
 * Kino Coin Laundry - Client API Service Module
 * Terintegrasi penuh dengan Supabase PostgreSQL & REST API Sarah
 *
 * Pengembang Frontend: Nizar Maulana Ramzy (zyy)
 * Pengembang Backend: Sarah Geiza
 * Router Backend: api/index.php?module={module}&action={action}
 */

const API_CONFIG = {
    // Mode Live Database Supabase Aktif 100%
    USE_BACKEND_API: true,
    
    // Auto-detect base path ke router api/index.php
    BASE_URL: (window.location.pathname.includes('/kasir/') || 
               window.location.pathname.includes('/admin/') || 
               window.location.pathname.includes('/pelanggan/'))
               ? '../api/index.php' 
               : 'api/index.php',

    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        const csrf = window.__CSRF_TOKEN || '';
        if (csrf) {
            headers['X-CSRF-Token'] = csrf;
        }
        return headers;
    }
};

const KinoAPI = {
    // 1. Autentikasi Pengguna & Staf
    async login(username, password) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=auth&action=login`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders(),
                body: JSON.stringify({ username, password })
            });
            const json = await res.json();
            if (json.csrf_token) {
                window.__CSRF_TOKEN = json.csrf_token;
            }
            return json;
        }
        return { success: true, username };
    },

    async registerCustomer(customerData) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=auth&action=register`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders(),
                body: JSON.stringify(customerData)
            });
            return await res.json();
        }
        return { success: true, data: customerData };
    },

    async getMe() {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=auth&action=me`, {
                headers: API_CONFIG.getHeaders()
            });
            return await res.json();
        }
        return { user: { nama: 'Sarah', role: 'kasir' } };
    },

    async logout() {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=auth&action=logout`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders()
            });
            return await res.json();
        }
        return { success: true };
    },

    // 2. Katalog Layanan
    async getServices() {
        if (API_CONFIG.USE_BACKEND_API) {
            try {
                const res = await fetch(`${API_CONFIG.BASE_URL}?module=layanan&action=list`, {
                    headers: API_CONFIG.getHeaders()
                });
                const json = await res.json();
                if (json.data) return json.data;
            } catch (e) {
                console.warn('API getServices fallback to live session', e);
            }
        }
        return window.__MOCK_SERVICES || window.__LIVE_SERVICES || [];
    },

    // 3. Pelanggan & Member
    async searchMember(query = '') {
        if (API_CONFIG.USE_BACKEND_API) {
            try {
                const res = await fetch(`${API_CONFIG.BASE_URL}?module=pelanggan&action=list&q=${encodeURIComponent(query)}`, {
                    headers: API_CONFIG.getHeaders()
                });
                const json = await res.json();
                if (json.data) return json.data;
            } catch (e) {
                console.warn('API searchMember fallback to live session', e);
            }
        }
        const members = window.__LIVE_MEMBERS || window.__MOCK_MEMBERS || [];
        const q = query.toLowerCase().trim();
        return members.filter(m => 
            (m.nama && m.nama.toLowerCase().includes(q)) || 
            (m.wa && m.wa.includes(q)) || 
            (m.kode_kartu && m.kode_kartu.toLowerCase().includes(q))
        );
    },

    async createMember(nama, noHp) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=pelanggan&action=create`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders(),
                body: JSON.stringify({ nama_pelanggan: nama, no_hp: noHp })
            });
            return await res.json();
        }
        return { success: true, data: { nama_pelanggan: nama, no_hp: noHp } };
    },

    // 4. Transaksi
    async getTransactions(page = 1) {
        if (API_CONFIG.USE_BACKEND_API) {
            try {
                const res = await fetch(`${API_CONFIG.BASE_URL}?module=transaksi&action=list&page=${page}`, {
                    headers: API_CONFIG.getHeaders()
                });
                const json = await res.json();
                if (json.data) return json.data;
            } catch (e) {
                console.warn('API getTransactions fallback to live session', e);
            }
        }
        return window.__LIVE_TRANSACTIONS || window.__MOCK_TRANSACTIONS || [];
    },

    async createTransaction(payload) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=transaksi&action=create`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders(),
                body: JSON.stringify(payload)
            });
            return await res.json();
        }
        return { success: true, id: 'INV-2026-00' + Math.floor(Math.random() * 900 + 100), data: payload };
    },

    // 5. Shift Operasional (Buka / Tutup / Presensi)
    async openShift(kasirId, shiftId) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=shift-operasional&action=open`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders(),
                body: JSON.stringify({ id_kasir: kasirId, id_shift: shiftId })
            });
            return await res.json();
        }
        return { success: true };
    },

    async closeShift(shiftOperasionalId, totalFisik, selisih) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=shift-operasional&action=close`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders(),
                body: JSON.stringify({
                    id_shift_operasional: shiftOperasionalId,
                    total_fisik: totalFisik,
                    selisih: selisih
                })
            });
            return await res.json();
        }
        return { success: true };
    },

    async getCurrentShift() {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=shift-operasional&action=current`, {
                headers: API_CONFIG.getHeaders()
            });
            return await res.json();
        }
        return { data: null };
    },

    // 6. Dashboard & Rekapitulasi Shift (Audit Pemilik)
    async getDashboardMetrics(startDate = '', endDate = '') {
        if (API_CONFIG.USE_BACKEND_API) {
            let url = `${API_CONFIG.BASE_URL}?module=dashboard&action=index`;
            if (startDate) url += `&start=${startDate}`;
            if (endDate) url += `&end=${endDate}`;
            const res = await fetch(url, { headers: API_CONFIG.getHeaders() });
            return await res.json();
        }
        return { data: { jumlah_transaksi: 1, pendapatan: 21500, total_koin: 2 } };
    },

    // 7. Tracking Mandiri Pelanggan
    async trackOrder(noHp) {
        if (API_CONFIG.USE_BACKEND_API) {
            const res = await fetch(`${API_CONFIG.BASE_URL}?module=riwayat&action=tracking`, {
                method: 'POST',
                headers: API_CONFIG.getHeaders(),
                body: JSON.stringify({ no_hp: noHp })
            });
            return await res.json();
        }
        return { success: true, data: [] };
    }
};

window.KinoAPI = KinoAPI;
