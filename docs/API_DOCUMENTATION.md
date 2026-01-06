# 📖 Dokumentasi API - Sistem Informasi Penelusuran Perkara

## Base URL
```
http://localhost/Sistem%20Pengadilan%20Agama1/api
```

---

## 📋 Daftar Endpoint

### **Root API**
- [GET /](#get-)

### **Perkara**
- [GET /perkara/rekap](#get-perkararekap)
- [GET /perkara/rekap/statistik](#get-perkararekapstatistik)
- [GET /perkara/detail/{id}](#get-perkaradetailid)

### **Pihak**
- [GET /pihak](#get-pihak)
- [GET /pihak/{id}](#get-pihakid)
- [PUT /pihak/{id}](#put-pihakid)

### **Penilaian Kinerja**
- [GET /penilaian-kinerja](#get-penilaian-kinerja)
- [GET /penilaian-kinerja/summary](#get-penilaian-kinerja-summary)

---

## 🔍 Detail Endpoint

### GET /

**Deskripsi:** Endpoint root untuk cek status API

**Response:**
```json
{
  "success": true,
  "message": "API is running",
  "data": {
    "version": "1.0",
    "endpoints": {
      "GET /perkara/rekap": "Get rekap perkara",
      "GET /perkara/rekap/statistik": "Get statistik perkara",
      "GET /perkara/detail/{id}": "Get detail perkara"
    }
  }
}
```

---

## 📁 PERKARA

### GET /perkara/rekap

**Deskripsi:** Mendapatkan rekap perkara berdasarkan range tanggal

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| tanggal_mulai | string | Yes | Tanggal mulai (YYYY-MM-DD) | 2024-01-01 |
| tanggal_akhir | string | Yes | Tanggal akhir (YYYY-MM-DD) | 2024-12-31 |

**Request Example:**
```
GET /perkara/rekap?tanggal_mulai=2024-01-01&tanggal_akhir=2024-12-31
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Data rekap berhasil diambil",
  "data": {
    "periode": {
      "tanggal_mulai": "2024-01-01",
      "tanggal_akhir": "2024-12-31",
      "jumlah_hari": 366
    },
    "total_records": 5753,
    "data": [
      {
        "perkara_id": "49235",
        "nomor_perkara": "4787/Pdt.G/2024/PA.Tsm",
        "tanggal_pendaftaran": "2024-12-24",
        "jenis_perkara_id": "347",
        "jenis_perkara_nama": "Cerai Gugat",
        "jenis_perkara_kode": null,
        "efiling_id": "2330655",
        "cara_daftar": "e-court",
        "hakim_nama": "Dr. Sugiri Permana, S.Ag., M.H.",
        "tanggal_putusan": "2024-12-31",
        "tanggal_minutasi": "2024-12-31",
        "status_perkara": "minutasi_selesai"
      }
    ]
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Parameter tanggal_mulai dan tanggal_akhir harus diisi"
}
```

---

### GET /perkara/rekap/statistik

**Deskripsi:** Mendapatkan statistik perkara (summary count)

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| tanggal_mulai | string | Yes | Tanggal mulai (YYYY-MM-DD) | 2024-01-01 |
| tanggal_akhir | string | Yes | Tanggal akhir (YYYY-MM-DD) | 2024-12-31 |

**Request Example:**
```
GET /perkara/rekap/statistik?tanggal_mulai=2024-01-01&tanggal_akhir=2024-12-31
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Statistik berhasil diambil",
  "data": {
    "periode": {
      "tanggal_mulai": "2024-01-01",
      "tanggal_akhir": "2024-12-31",
      "jumlah_hari": 366
    },
    "statistik": {
      "total_perkara": 5753,
      "cara_daftar": {
        "e_court": 1670,
        "manual": 4083
      },
      "status": {
        "putus": 5753,
        "minutasi": 5753,
        "dalam_proses": 0
      }
    }
  }
}
```

---

### GET /perkara/detail/{id}

**Deskripsi:** Mendapatkan detail perkara berdasarkan ID

**Path Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| id | string | Yes | ID Perkara | 49235 |

**Request Example:**
```
GET /perkara/detail/49235
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Detail perkara berhasil diambil",
  "data": {
    "perkara_id": "49235",
    "nomor_perkara": "4787/Pdt.G/2024/PA.Tsm",
    "tanggal_pendaftaran": "2024-12-24",
    "jenis_perkara_nama": "Cerai Gugat",
    "efiling_id": "2330655",
    "hakim_nama": "Dr. Sugiri Permana, S.Ag., M.H.",
    "tanggal_putusan": "2024-12-31",
    "tanggal_minutasi": "2024-12-31",
    "amar_putusan": "..."
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Data perkara tidak ditemukan"
}
```

---

## 👥 PIHAK

### GET /pihak

**Deskripsi:** Mendapatkan list pihak dengan pagination dan search

**Query Parameters:**
| Parameter | Type | Required | Description | Default | Example |
|-----------|------|----------|-------------|---------|---------|
| page | integer | No | Halaman ke- | 1 | 1 |
| limit | integer | No | Jumlah data per halaman (max: 100) | 10 | 10 |
| search | string | No | Keyword pencarian | - | Ahmad |

**Request Example:**
```
GET /pihak?page=1&limit=10&search=Ahmad
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Data pihak berhasil diambil",
  "data": [
    {
      "id": "48462",
      "jenis_pihak_id": "1",
      "nama": "Tika Media binti Iji Sutarji",
      "tempat_lahir": "Tasikmalaya",
      "tanggal_lahir": "1999-09-05",
      "jenis_kelamin": "P",
      "alamat": "...",
      "pekerjaan": "...",
      "keterangan": "..."
    }
  ],
  "pagination": {
    "total_records": 150,
    "total_pages": 15,
    "current_page": 1,
    "per_page": 10,
    "showing": 10
  }
}
```

---

### GET /pihak/{id}

**Deskripsi:** Mendapatkan detail pihak berdasarkan ID

**Path Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| id | string | Yes | ID Pihak | 48462 |

**Request Example:**
```
GET /pihak/48462
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Detail pihak berhasil diambil",
  "data": {
    "id": "48462",
    "jenis_pihak_id": "1",
    "nama": "Tika Media binti Iji Sutarji",
    "tempat_lahir": "Tasikmalaya",
    "tanggal_lahir": "1999-09-05",
    "jenis_kelamin": "P",
    "alamat": "...",
    "pekerjaan": "...",
    "keterangan": "..."
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Data pihak tidak ditemukan"
}
```

---

### PUT /pihak/{id}

**Deskripsi:** Update data pihak

**Path Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| id | string | Yes | ID Pihak | 48462 |

**Request Body (JSON):**
```json
{
  "nama": "Ahmad Updated",
  "alamat": "Jl. Baru No. 123",
  "tempat_lahir": "Jakarta",
  "tanggal_lahir": "1990-01-01",
  "pekerjaan": "Programmer",
  "keterangan": "Updated info"
}
```

**Notes:**
- Semua field optional (kirim field yang mau diupdate aja)
- Field `tanggal_lahir` harus format YYYY-MM-DD

**Request Example (cURL):**
```bash
curl -X PUT http://localhost/Sistem%20Pengadilan%20Agama1/api/pihak/48462 \
  -H "Content-Type: application/json" \
  -d '{
    "nama": "Ahmad Updated",
    "alamat": "Jl. Baru No. 123"
  }'
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Data pihak berhasil diupdate",
  "data": {
    "id": "48462",
    "nama": "Ahmad Updated",
    "alamat": "Jl. Baru No. 123",
    "tempat_lahir": "Jakarta",
    "tanggal_lahir": "1990-01-01",
    "jenis_kelamin": "L",
    "pekerjaan": "Programmer",
    "keterangan": "Updated info"
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Tidak ada field yang diupdate"
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Data pihak tidak ditemukan"
}
```

---

## 📊 PENILAIAN KINERJA

### GET /penilaian-kinerja

**Deskripsi:** Mendapatkan data penilaian kinerja lengkap (dengan cache)

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| tahun | string | Yes | Tahun (YYYY) | 2024 |
| triwulan | string | Yes | Triwulan (1, 2, 3, atau 4) | 1 |
| clear_cache | string | No | Force refresh cache (1=yes) | 1 |

**Request Example:**
```
GET /penilaian-kinerja?tahun=2024&triwulan=1
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Data penilaian kinerja berhasil diambil",
  "data": {
    "periode": {
      "tahun": 2024,
      "triwulan": 1,
      "tanggal_awal": "2024-01-01",
      "tanggal_akhir": "2024-03-31"
    },
    "cache_info": {
      "from_cache": true,
      "age_seconds": 120,
      "ttl_seconds": 3600
    },
    "data": {
      "total_perkara": 1500,
      "putus": 1450,
      "minutasi": 1400,
      "publikasi": 1350,
      "pendaftaran": 1500,
      "pmh": 800,
      "input_pmh": 750,
      "pen_pp": 1450,
      "input_pp": 1400,
      "pen_js": 1480,
      "pen_js_2": 1480,
      "pen_phs": 1480,
      "input_phs": 1480,
      "relaas": 1480,
      "mediasi": 85.5,
      "saksi": 900,
      "pbt": 1450,
      "bht": 1400,
      "sisa_panjar": 50,
      "arsip": 1400,
      "delegasi": 120,
      "petitum": 300,
      "relaas_kel": 1480,
      "bas": 1480,
      "ac": 800,
      "sidang_akhir_terlambat": 25,
      "sinkron_tidak": 10,
      "permintaan_delegasi": 120,
      "status_persidangan": 45,
      "nilai_kinerja": 48.5,
      "nilai_kepatuhan": 47.8,
      "nilai_kelengkapan": 9.2,
      "nilai_kesesuaian": -1.5,
      "nilai_akhir": 104.0
    }
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Parameter tahun dan triwulan harus diisi"
}
```

**Notes:**
- Data di-cache selama 1 jam (3600 detik)
- Gunakan `clear_cache=1` untuk force refresh
- Field `mediasi` dalam persentase (%)

---

### GET /penilaian-kinerja/summary

**Deskripsi:** Mendapatkan ringkasan nilai penilaian kinerja (tanpa detail metrik)

**Query Parameters:**
| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| tahun | string | Yes | Tahun (YYYY) | 2024 |
| triwulan | string | Yes | Triwulan (1, 2, 3, atau 4) | 1 |

**Request Example:**
```
GET /penilaian-kinerja/summary?tahun=2024&triwulan=1
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Summary penilaian kinerja berhasil diambil",
  "data": {
    "periode": {
      "tahun": 2024,
      "triwulan": 1,
      "tanggal_awal": "2024-01-01",
      "tanggal_akhir": "2024-03-31"
    },
    "total_perkara": 1500,
    "nilai": {
      "kinerja": 48.5,
      "kepatuhan": 47.8,
      "kelengkapan": 9.2,
      "kesesuaian": -1.5,
      "akhir": 104.0
    }
  }
}
```

---

## 🔧 Error Responses

### Standard Error Format
```json
{
  "success": false,
  "message": "Error message here"
}
```

### Error dengan Detail
```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "tanggal_mulai": "Format tanggal_mulai tidak valid",
    "tanggal_akhir": "Format tanggal_akhir tidak valid"
  }
}
```

### HTTP Status Codes
| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request (validasi gagal) |
| 404 | Not Found (data tidak ditemukan) |
| 405 | Method Not Allowed |
| 500 | Internal Server Error |
| 501 | Not Implemented |

---

## 📝 Notes

### Format Tanggal
- Semua tanggal menggunakan format **YYYY-MM-DD**
- Contoh: `2024-01-01`

### Pagination
- Default `page=1`, `limit=10`
- Max limit: 100 per request
- Response include metadata pagination

### Cache
- Penilaian Kinerja di-cache selama **1 jam**
- Gunakan parameter `clear_cache=1` untuk force refresh
- Cache info included di response

### Content-Type
- Request (untuk PUT): `application/json`
- Response: `application/json; charset=utf-8`

---

## 🚀 Quick Start Examples

### JavaScript (Fetch)
```javascript
// Get Rekap Perkara
fetch('http://localhost/Sistem%20Pengadilan%20Agama1/api/perkara/rekap?tanggal_mulai=2024-01-01&tanggal_akhir=2024-12-31')
  .then(response => response.json())
  .then(data => console.log(data));

// Update Pihak
fetch('http://localhost/Sistem%20Pengadilan%20Agama1/api/pihak/48462', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    nama: 'Ahmad Updated',
    alamat: 'Jl. Baru No. 123'
  })
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### PHP (cURL)
```php
// Get Penilaian Kinerja
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Sistem%20Pengadilan%20Agama1/api/penilaian-kinerja?tahun=2024&triwulan=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
print_r($data);
```

