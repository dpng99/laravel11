// resources/js/Pages/Kelola/EvaluasiWas.jsx
import React, { useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// Import komponen Material-UI
import {
    Card, CardHeader, CardContent, Button, Typography, Paper, Box,
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
    List, ListItem, ListItemIcon, ListItemText
} from '@mui/material';

// Import Ikon
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import CancelIcon from '@mui/icons-material/Cancel';

// Helper untuk mengelompokkan data
const groupBy = (array, key) => {
    return array.reduce((result, currentValue) => {
        (result[currentValue[key]] = result[currentValue[key]] || []).push(currentValue);
        return result;
    }, {});
};

export default function EvalWas() {
    // Ambil props dari LkeWasController@listBuktiDukung
    const { komponen, satkernama, idSatker, buktiDukung } = usePage().props;

    // --- Logika RowSpan (diadaptasi dari Blade) ---
    // Kita memproses data 'komponen' sekali untuk menghitung rowspans
    const processedData = useMemo(() => {
        if (!komponen) return [];
        
        const groupedKomponen = groupBy(komponen, 'id_komponen');
        let dataWithRowspans = [];
        let noCounter = 1; // Untuk kolom "No"

        Object.values(groupedKomponen).forEach((dataKomponen) => {
            const komponenRowspan = dataKomponen.length;
            const groupedSub = groupBy(dataKomponen, 'id_subkomponen');

            Object.values(groupedSub).forEach((dataSub, subIndex) => {
                const subRowspan = dataSub.length;

                dataSub.forEach((row, rowIndex) => {
                    dataWithRowspans.push({
                        ...row,
                        // Tentukan rowspan untuk Komponen (hanya di baris pertama)
                        komponenRowspan: (subIndex === 0 && rowIndex === 0) ? komponenRowspan : 0,
                        // Tentukan Nomor Komponen (hanya di baris pertama)
                        komponenNo: (subIndex === 0 && rowIndex === 0) ? noCounter : 0,
                        // Tentukan rowspan untuk Subkomponen (di baris pertama setiap sub-grup)
                        subRowspan: (rowIndex === 0) ? subRowspan : 0,
                    });
                });
            });
            noCounter++; // Naikkan nomor komponen setelah selesai
        });
        return dataWithRowspans;
    }, [komponen]);
    // ---------------------------------------------

    return (
        <AuthenticatedLayout>
            <Head title={`LKE ${satkernama}`} />

            <Card elevation={3}>
                <CardHeader
                    title="LKE - Pengawasan"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    <Typography variant="h6">
                        Bukti Dukung pada Kejaksaan Negeri {satkernama}
                    </Typography>
                    <Typography paragraph sx={{ mt: 1 }}>
                        Halaman ini digunakan untuk melihat dokumen/bukti dukung setiap satuan kerja...
                    </Typography>
                    
                    {/* Tombol Kembali */}
                    <Button
                        component={Link}
                        href="/was-lke" // URL (tanpa Ziggy) ke halaman index
                        variant="contained"
                        color="secondary"
                        startIcon={<ArrowBackIcon />}
                        sx={{ mb: 2 }}
                    >
                        Kembali
                    </Button>

                    {/* Tabel LKE */}
                    <TableContainer component={Paper} elevation={2}>
                        <Table stickyHeader>
                            <TableHead>
                                <TableRow sx={{ '& th': { backgroundColor: 'success.main', color: 'white', fontWeight: 'bold' } }}>
                                    <TableCell>No</TableCell>
                                    <TableCell>Komponen</TableCell>
                                    <TableCell>Subkomponen</TableCell>
                                    <TableCell>Kode Kriteria</TableCell>
                                    <TableCell>Kriteria</TableCell>
                                    <TableCell>Dokumen Bukti Dukung</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {processedData.map((row) => {
                                    // Siapkan data dokumen (logika dari Blade)
                                    const needs = (row.bukti_pengisian || '').split(';').map(s => s.trim()).filter(Boolean);
                                    const formats = (row.format_nama_file || '').split(';').map(s => s.trim());
                                    const uploaded = buktiDukung[row.id_kriteria] || [];

                                    return (
                                        <TableRow key={row.id_kriteria} hover>
                                            
                                            {/* Kolom No (Render HANYA jika rowspan > 0) */}
                                            {row.komponenRowspan > 0 && (
                                                <TableCell rowSpan={row.komponenRowspan} sx={{ verticalAlign: 'top' }}>
                                                    {row.komponenNo}
                                                </TableCell>
                                            )}
                                            {/* Kolom Komponen (Render HANYA jika rowspan > 0) */}
                                            {row.komponenRowspan > 0 && (
                                                <TableCell rowSpan={row.komponenRowspan} sx={{ verticalAlign: 'top' }}>
                                                    {row.nama_komponen}
                                                </TableCell>
                                            )}
                                            {/* Kolom Subkomponen (Render HANYA jika rowspan > 0) */}
                                            {row.subRowspan > 0 && (
                                                <TableCell rowSpan={row.subRowspan} sx={{ verticalAlign: 'top' }}>
                                                    {row.nama_subkomponen}
                                                </TableCell>
                                            )}
                                            
                                            {/* Kolom Kriteria (Selalu render) */}
                                            <TableCell>{row.kode}</TableCell>
                                            <TableCell>{row.nama_kriteria}</TableCell>
                                            
                                            {/* Kolom Dokumen Bukti Dukung */}
                                            <TableCell>
                                                <List dense disablePadding>
                                                    {needs.map((need, i) => {
                                                        const pattern = formats[i] || '';
                                                        const prefix = pattern.split(/[_\s]/)[0]?.toLowerCase() || pattern;
                                                        // Cari dokumen yang cocok
                                                        const dok = uploaded.find(d => 
                                                            d.link_bukti_dukung.toLowerCase().includes(prefix)
                                                        );

                                                        return (
                                                            <ListItem key={i} disableGutters>
                                                                <ListItemIcon sx={{ minWidth: 30 }}>
                                                                    {dok ? 
                                                                        <CheckCircleIcon color="success" fontSize="small" /> : 
                                                                        <CancelIcon color="error" fontSize="small" />
                                                                    }
                                                                </ListItemIcon>
                                                                {dok ? (
                                                                    <Typography variant="body2" component="a" 
                                                                        href={`/uploads/repository/${idSatker}/${dok.link_bukti_dukung}`} 
                                                                        target="_blank" rel="noopener noreferrer"
                                                                        sx={{ textDecoration: 'none', color: 'primary.main', '&:hover': { textDecoration: 'underline' } }}
                                                                    >
                                                                        {need}
                                                                    </Typography>
                                                                ) : (
                                                                    <ListItemText 
                                                                        primary={need} 
                                                                        primaryTypographyProps={{ color: 'error.main', variant: 'body2' }} 
                                                                    />
                                                                )}
                                                            </ListItem>
                                                        );
                                                    })}
                                                </List>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </TableContainer>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}