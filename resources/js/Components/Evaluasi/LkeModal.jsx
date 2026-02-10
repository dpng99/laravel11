import React from 'react';
import { useForm, router } from '@inertiajs/react';
import { 
    Dialog, DialogTitle, DialogContent, DialogActions, Button, 
    Box, Typography, Table, TableBody, TableCell, TableRow, Chip, Alert, LinearProgress 
} from '@mui/material';
import CloudUploadIcon from '@mui/icons-material/CloudUpload';
import CheckCircleOutlineIcon from '@mui/icons-material/CheckCircleOutline';
import AutoAwesomeIcon from '@mui/icons-material/AutoAwesome';

export default function LkeModal({ open, onClose, data }) {
    // Setup form Inertia untuk Upload Manual
    const { data: formData, setData, post, processing, reset, progress, errors } = useForm({
        file: null,
        id_kriteria: data?.id_kriteria,
        id_komponen: data?.id_komponen,
        id_sub_komponen: data?.id_sub_komponen,
        format_nama: data?.nama_dokumen
    });

    // Handler saat file dipilih
    const handleFileChange = (e) => {
        setData('file', e.target.files[0]);
    };

    // Handler Submit Upload Manual
    const handleUploadManual = (e, bukti) => {
        e.preventDefault();
        formData.id_kriteria = data.kode_kriteria;
        formData.id_komponen = data.id_komponen;
        formData.id_sub_komponen = data.id_sub_komponen;
        formData.kode_bukti = bukti.kode_bukti;
        if (!formData.file) return alert("Pilih file terlebih dahulu!");

        post(route('upload.dokumen'), { 
            // Pastikan route ini ada di web.php
            onSuccess: () => {
                reset();
                // onClose(); // Optional: tutup modal jika sukses
            },
            preserveScroll: true
        });
    };

    // Handler Verifikasi Otomatis
    const handleVerifikasi = (bukti) => {
        if (!confirm(`Verifikasi otomatis dokumen: ${bukti.nama_dokumen}?`)) return;

        router.post(route('verifikasi.dokumen'), {
            id_kriteria: data.kode_kriteria,
            id_komponen: data.id_komponen,
            id_sub_komponen: data.id_sub_komponen,
            kode_bukti: bukti.kode_bukti,
            file: bukti.nama_dokumen,

        }, {
            onSuccess: () => {
                // Data akan refresh otomatis karena Inertia
            },
            preserveScroll: true
        });
    };

    if (!data) return null;

    return (
        <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
            <DialogTitle sx={{ bgcolor: '#f5f5f5', borderBottom: '1px solid #ddd', pb: 1 }}>
                Kelola Bukti Dukung
            </DialogTitle>
            
            <DialogContent sx={{ mt: 2 }}>
                {/* Header Info Kriteria */}
                <Alert severity="info" icon={<AutoAwesomeIcon />} sx={{ mb: 2 }}>
                    <Typography variant="body2">
                        <strong>Kriteria:</strong> {data.nama_kriteria} <br/>
                        <strong>Kode:</strong> {data.kode_kriteria}
                    </Typography>
                </Alert>

                <Table size="small">
                    <TableBody>
                        {data.bukti_list.map((bukti, idx) => (
                            <TableRow key={idx}>
                                {/* Kolom Nama Dokumen */}
                                <TableCell width="40%" sx={{ verticalAlign: 'top' }}>
                                    <Typography variant="subtitle2" fontWeight="bold">
                                        {bukti.nama_dokumen}
                                    </Typography>
                                    <Chip 
                                        label={bukti.is_manual ? "Manual Upload" : "Sistem Otomatis"} 
                                        size="small" 
                                        color={bukti.is_manual ? "warning" : "primary"} 
                                        variant="outlined"
                                        sx={{ mt: 0.5, fontSize: '0.65rem', height: 20 }}
                                    />
                                </TableCell>
                                
                                {/* Kolom Status */}
                                <TableCell width="15%" align="center" sx={{ verticalAlign: 'top' }}>
                                    {bukti.status === 'Ada' ? (
                                        <Chip label="Ada" color="success" size="small" />
                                    ) : bukti.status === 'Tersedia di Sistem (Belum Verif)' ? (
                                        <Chip label="Siap Verif" color="info" size="small" />
                                    ) : (
                                        <Chip label="Kosong" color="error" size="small" />
                                    )}
                                </TableCell>

                                {/* Kolom Aksi */}
                                <TableCell width="45%" sx={{ verticalAlign: 'top' }}>
                                    {bukti.status === 'Ada' ? (
                                        <Box sx={{ display: 'flex', alignItems: 'center', color: 'green' }}>
                                            <CheckCircleOutlineIcon fontSize="small" sx={{ mr: 1 }} />
                                            <Typography variant="caption">Terverifikasi</Typography>
                                            {/* Jika mau tombol re-upload, tambahkan logika disini */}
                                        </Box>
                                    ) : bukti.status === 'Tidak Ada' ? (
                                        // --- FORM UPLOAD MANUAL ---
                                        <Box component="form" onSubmit={(e) => handleUploadManual(e, bukti)} sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                                            <input 
                                                type="file" 
                                                onChange={handleFileChange} 
                                                accept=".pdf,.jpg,.png"
                                                style={{ fontSize: '0.75rem', width: '100%' }} 
                                            />
                                            {progress && <LinearProgress variant="determinate" value={progress.percentage} />}
                                            {errors.file && <Typography color="error" variant="caption">{errors.file}</Typography>}
                                            
                                            <Button 
                                                type="submit" 
                                                variant="contained" 
                                                color="warning"
                                                size="small" 
                                                startIcon={<CloudUploadIcon />}
                                                disabled={processing || !formData.file}
                                                sx={{ alignSelf: 'flex-start', fontSize: '0.7rem' }}
                                            >
                                                {processing ? 'Mengupload...' : 'Upload File'}
                                            </Button>
                                        </Box>
                                    ) : (
                                        // --- TOMBOL VERIFIKASI SISTEM ---
                                        <Box>
                                            <Typography variant="caption" color="text.secondary" display="block" gutterBottom>
                                                Ambil dari modul Perencanaan/Pelaporan?
                                            </Typography>
                                            <Button 
                                                variant="contained" 
                                                color="primary" 
                                                size="small"
                                                onClick={() => handleVerifikasi(bukti)}
                                                disabled={processing}
                                                startIcon={<AutoAwesomeIcon />}
                                                sx={{ fontSize: '0.7rem' }}
                                            >
                                                Verifikasi Otomatis
                                            </Button>
                                        </Box>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </DialogContent>

            <DialogActions sx={{ borderTop: '1px solid #eee', p: 2 }}>
                <Button onClick={onClose} color="inherit" variant="outlined" size="small">Tutup</Button>
            </DialogActions>
        </Dialog>
    );
}