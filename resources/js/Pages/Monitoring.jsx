// resources/js/Pages/Monitoring.jsx
import React, { useState, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardContent, Autocomplete, TextField, Button, Box } from '@mui/material';

// Impor komponen-komponen baru
import CapaianKinerja from '@/Components/Monitoring/CapaianKinerja';
import CapaianSaspro from '@/Components/Monitoring/CapaianSaspro';

export default function Monitoring() {
    // Ambil props dari controller
    const { satkers, selectedSatker, search, tahun, id_satker, levelSakip } = usePage().props;

    // State untuk nilai Autocomplete
    const [selectedValue, setSelectedValue] = useState(
        selectedSatker ? { label: selectedSatker.satkernama, id: selectedSatker.id_satker } : null
    );

    // Format data satker untuk Autocomplete
    const satkerOptions = useMemo(() => 
        satkers.map(s => ({ label: s.satkernama.replace(/_/g, ' '), id: s.id_satker })), 
    [satkers]);

    // Handle saat tombol "Cari" diklik
    const handleSearch = () => {
        if (selectedValue) {
            router.get('/monitoring', { satker: selectedValue.id }, { preserveState: true });
        }
    };
    
    // Tentukan apakah user boleh mencari (sesuai logika Blade)
    const canSearch = levelSakip == 99 || levelSakip == 0 || !String(id_satker).startsWith('was');

    return (
        <AuthenticatedLayout>
            <Head title="Monitoring" />

            <Card elevation={3}>
                <CardHeader
                    title="Monitoring"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    {/* Bagian 1: Form Pencarian Satker (Pengganti Select2) */}
                    {canSearch && (
                        <Box sx={{ display: 'flex', gap: 2, mb: 4, alignItems: 'center' }}>
                            <Autocomplete
                                fullWidth
                                options={satkerOptions}
                                value={selectedValue}
                                onChange={(event, newValue) => {
                                    setSelectedValue(newValue);
                                }}
                                isOptionEqualToValue={(option, value) => option.id === value.id}
                                renderInput={(params) => <TextField {...params} label="Ketik Nama atau Kode Satker..." />}
                            />
                            <Button variant="contained" color="success" onClick={handleSearch} sx={{ height: 56 }}>
                                Cari
                            </Button>
                        </Box>
                    )}

                    {/* Bagian 2: Capaian Kinerja (jika Satker dipilih) */}
                    {selectedSatker && (
                        <Card elevation={2} sx={{ mb: 4 }}>
                            <CardHeader
                                title={`Capaian Kinerja - ${selectedSatker.satkernama.replace(/_/g, ' ')}`}
                                sx={{ backgroundColor: 'primary.main', color: 'white' }}
                            />
                            <CardContent>
                                <CapaianKinerja selectedSatker={selectedSatker} />
                            </CardContent>
                        </Card>
                    )}

                    {/* Bagian 3: Capaian Sasaran Strategis (selalu tampil) */}
                    <Card elevation={2}>
                        <CardHeader
                            title={`Capaian Sasaran Strategis - ${tahun}`}
                            sx={{ backgroundColor: 'primary.main', color: 'white' }}
                        />
                        <CardContent>
                            <CapaianSaspro />
                        </CardContent>
                    </Card>

                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}