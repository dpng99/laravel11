import React, { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react'; // Gunakan router untuk submit
import { Box, Button, CircularProgress, Typography, Alert, Snackbar } from '@mui/material';
import axios from 'axios';
import IndikatorForm from './IndikatorForm';
import SaveIcon from '@mui/icons-material/Save';

export default function IndikatorContent({ rumpun, tahun, idSatker }) {
    const { flash } = usePage().props;
    const [indikators, setIndikators] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [processing, setProcessing] = useState(false);

    // GANTI useForm DENGAN useState BIASA
    // Agar re-render lebih cepat dan input tidak macet
    const [formValues, setFormValues] = useState({});

    // Efek untuk memuat data saat rumpun berubah
    useEffect(() => {
        if (!rumpun) return;

        setLoading(true);
        setError(null);
        setIndikators([]);
        setFormValues({}); // Reset form

        // 1. Ambil daftar indikator
        axios.get(`/get-subindikator/${rumpun}`)
            .then(resIndikators => {
                const fetchedIndikators = resIndikators.data;
                setIndikators(fetchedIndikators);
                
                if (fetchedIndikators.length === 0) {
                    setLoading(false);
                    return;
                }

                // 2. Ambil data pengukuran untuk semua indikator
                const indicatorIds = fetchedIndikators.map(ind => ind.id);
                const dataPromises = indicatorIds.map(id => 
                    axios.get(`/get-pengukuran/${id}`)
                );

                Promise.all(dataPromises)
                    .then(responses => {
                        const newFormValues = {};

                        responses.forEach((resPengukuran, index) => {
                            const indId = indicatorIds[index];
                            
                            // Inisialisasi object untuk indikator ini
                            newFormValues[indId] = {};

                            resPengukuran.data.forEach(item => {
                                const sub = item.sub_indikator;
                                if (!newFormValues[indId][sub]) newFormValues[indId][sub] = {};
                                
                                // Mapping Sisa Tahun Lalu
                                if (item.bulan === 1 && item.sisa_tahun_lalu !== null) {
                                    newFormValues[indId][sub].sisa_tahun_lalu = item.sisa_tahun_lalu;
                                }

                                // Mapping Perhitungan Bulanan
                                if (item.perhitungan) {
                                    newFormValues[indId][sub][item.bulan] = item.perhitungan;
                                }
                                
                                // Mapping Capaian Triwulan
                                if (item.capaian !== null) {
                                     newFormValues[indId][sub][`TW${item.bulan / 3}`] = item.capaian;
                                }
                            });
                        });
                        
                        setFormValues(newFormValues);
                        setLoading(false);
                    })
                    .catch(err => {
                        console.error("Gagal memuat data pengukuran:", err);
                        setError('Gagal memuat data pengukuran.');
                        setLoading(false);
                    });
            })
            .catch(err => {
                console.error("Gagal memuat indikator:", err);
                setError('Gagal memuat data indikator.');
                setLoading(false);
            });

    }, [rumpun]);

    // Handler Update State (Disederhanakan)
    const handleFormUpdate = (indId, sub, field, value) => {
        setFormValues(prev => ({
            ...prev,
            [indId]: {
                ...(prev[indId] || {}),
                [sub]: {
                    ...(prev[indId]?.[sub] || {}),
                    [field]: value
                }
            }
        }));
    };

    // Handle submit form utama
    const handleSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        // Transformasi data untuk dikirim ke Controller
        // Controller mengharapkan struktur: { ditangani: { sub: { bulan: val } }, ... }
        const payload = {
            sub_indikator_list: [],
            indikator_id: {},
            sisa_tahun_lalu: {},
            // key dinamis lain akan ditambahkan di bawah
        };

        const bulanList = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        const triwulanList = ['TW1', 'TW2', 'TW3', 'TW4'];

        indikators.forEach(ind => {
            if (!ind.sub_indikator) return;
            const subs = ind.sub_indikator.split(',').map(s => s.trim());
            
            // Tentukan label (ditangani/diselesaikan atau custom)
            let labels = ['ditangani', 'diselesaikan']; 
            let isTriwulan = false;
            
            if (ind.indikator_penghitungan) {
                const parts = ind.indikator_penghitungan.split(',').map(s => s.trim().toLowerCase());
                if (parts.length > 0) labels = parts;
                if (parts.length === 1) isTriwulan = true;
            }

            subs.forEach(sub => {
                payload.sub_indikator_list.push(sub);
                payload.indikator_id[sub] = ind.id;
                
                const subData = formValues[ind.id]?.[sub] || {};

                // Sisa Tahun Lalu
                if (subData.sisa_tahun_lalu) {
                    payload.sisa_tahun_lalu[sub] = subData.sisa_tahun_lalu;
                }

                if (isTriwulan) {
                    // Mode Triwulan
                    const labelKey = labels[0];
                    if (!payload[labelKey]) payload[labelKey] = {};
                    if (!payload[labelKey][sub]) payload[labelKey][sub] = {};

                    triwulanList.forEach(tw => {
                        if (subData[tw]) payload[labelKey][sub][tw] = subData[tw];
                    });
                } else {
                    // Mode Bulanan (Split value ';')
                    bulanList.forEach((bulanNama, idx) => {
                        const bulanKe = idx + 1;
                        const rawVal = subData[bulanKe]; // Value di state disimpan per ID Bulan (1-12)
                        
                        if (rawVal) {
                            const valParts = rawVal.split(';');
                            labels.forEach((labelKey, labelIdx) => {
                                if (!payload[labelKey]) payload[labelKey] = {};
                                if (!payload[labelKey][sub]) payload[labelKey][sub] = {};
                                
                                if (valParts[labelIdx] !== undefined) {
                                    payload[labelKey][sub][bulanNama] = valParts[labelIdx];
                                }
                            });
                        }
                    });
                }
            });
        });

        // Kirim menggunakan router.post
        router.post('/simpan-pengukuran', payload, {
            preserveScroll: true,
            onSuccess: () => setProcessing(false),
            onError: () => {
                setProcessing(false);
                alert('Gagal menyimpan data.');
            }
        });
    };

    if (loading) return <Box display="flex" justifyContent="center" p={4}><CircularProgress /></Box>;
    if (error) return <Alert severity="error">{error}</Alert>;
    if (indikators.length === 0) return <Typography>Tidak ada Indikator untuk bidang ini.</Typography>;

    return (
        <Box component="form" onSubmit={handleSubmit}>
            {flash?.success && <Alert severity="success" sx={{ mb: 2 }}>{flash.success}</Alert>}
            
            {indikators.map(indikator => (
                <IndikatorForm
                    key={indikator.id}
                    indikator={indikator}
                    // Pass data spesifik & handler update
                    formData={formValues[indikator.id] || {}}
                    onUpdate={(sub, field, value) => handleFormUpdate(indikator.id, sub, field, value)}
                />
            ))}
            
            <Box sx={{ textAlign: 'right', mt: 4, mb: 10 }}>
                <Button 
                    type="submit" 
                    variant="contained" 
                    color="success" 
                    disabled={processing}
                    startIcon={processing ? <CircularProgress size={20} color="inherit"/> : <SaveIcon />}
                >
                    {processing ? 'Menyimpan...' : 'Simpan Data'}
                </Button>
            </Box>
        </Box>
    );
}