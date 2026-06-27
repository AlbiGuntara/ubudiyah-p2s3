# Task Progress Checklist

## 1. Database Migration
- [ ] Create migration to add `sisa_sanksi` column to pembinaan table
- [ ] Update Pembinaan model with new fillable fields and casts

## 2. Logika Sanksi & Sisa Sanksi (Backend)
- [ ] Update PembinaanController.index to handle anonymous santri (tanpa nama) + filters
- [ ] Update PembinaanController.update logic
- [ ] Rename `bayarShalawat` → `setorSanksi`, update logic to deduct from sisa_sanksi
- [ ] Add `pemutihan` endpoint for multiplying sanksi

## 3. PelanggaranController - syncPembinaan
- [ ] Fix syncPembinaan to handle anonymous (tanpa nama) grouping by asrama
- [ ] Update to set both sanksi (fixed) and sisa_sanksi (dynamic)

## 4. Routes
- [ ] Update route name `bayar-shalawat` → `setor-sanksi`
- [ ] Add route for `pemutihan`

## 5. Frontend UI (Pembinaan index.tsx)
- [ ] Rename column 'shalawat dibayar' → 'sanksi disetor'
- [ ] Rename modal title 'Bayar Shalawat' → 'Setor sanksi'
- [ ] Unify button styling for "Setor Sanksi" button
- [ ] Add search box and filter by daerah/asrama
- [ ] Add "Lakukan Pemutihan" button with modal and multiplier input
- [ ] Display sisa_sanksi column dynamically

## 6. Frontend Routes
- [ ] Update route definitions for new endpoint names

## 7. Testing & Verification
- [ ] Verify all functionality works end-to-end