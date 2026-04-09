import React, { useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

import {
    Card, CardHeader, CardContent, Button, Typography, Paper, Box,
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
    List, ListItem, ListItemIcon, Chip
} from '@mui/material';

import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import CancelIcon from '@mui/icons-material/Cancel';
import InfoIcon from '@mui/icons-material/Info';

export default function EvalWas() {
    // Ambil lkeGrouped dari LkeWas backend yang baru
    const { lkeGrouped, satkernama, tahun } = usePage().props;

    // === LOGIKA ROWSPAN (Dioptimalkan untuk membaca lkeGrouped) ===
    const tableRows = useMemo(() => {
        let rows = [];
        let no = 1;

        if (!lkeGrouped) return [];

        Object.values(lkeGrouped).forEach(subKomponens => {
            let isFirstKomponen = true;
            let totalKomponenRows = 0;

            Object.values(subKomponens).forEach(kriterias => {
                totalKomponenRows += kriterias.length;
            });

            Object.values(subKomponens).forEach(kriterias => {
                let isFirstSub = true;

                kriterias.forEach((kriteria) => {
                    rows.push({
                        ...kriteria,
                        rowspanKomponen: isFirstKomponen ? totalKomponenRows : 0,
                        rowspanSub: isFirstSub ? kriterias.length : 0,
                        displayNo: isFirstKomponen ? no : null,
                    });
                    
                    isFirstKomponen = false; 
                    isFirstSub = false;
                });
            });
            no++;
        });
        return rows;
    }, [lkeGrouped]);

    return (
        <AuthenticatedLayout>
            <Head title={`Evaluasi & Pengawasan - ${satkernama}`} />

            <Box sx={{ mb: 3, display: 'flex', alignItems: 'center', gap: 2 }}>
                <Button
                    component={Link}
                    href="/was-lke" 
                    variant="outlined"
                    startIcon={<ArrowBackIcon />}
                    sx={{ bgcolor: 'white' }}
                >
                    Kembali
                </Button>
                <Typography variant="h5" sx={{ fontWeight: 'bold' }}>
                    Monitoring SAKIP: {satkernama} ({tahun})
                </Typography>
            </Box>

            <Card elevation={3}>
                <CardHeader
                    title="Daftar Ketersediaan Bukti Dukung (Monitor APIP)"
                    sx={{ backgroundColor: '#e6bf3e', color: 'black' }}
                    titleTypographyProps={{ fontWeight: 'bold' }}
                />
                <CardContent>
                    <TableContainer component={Paper} sx={{ maxHeight: '75vh', mt: 2 }}>
                        <Table stickyHeader size="small">
                            <TableHead>
                                <TableRow sx={{ '& th': { bgcolor: '#f5f5f5', fontWeight: 'bold' } }}>
                                    <TableCell width="5%" align="center">No</TableCell>
                                    <TableCell width="15%">Komponen</TableCell>
                                    <TableCell width="15%">Sub Komponen</TableCell>
                                    <TableCell width="10%">Kode</TableCell>
                                    <TableCell width="25%">Kriteria</TableCell>
                                    <TableCell width="30%">Status Dokumen</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {tableRows.map((row, idx) => (
                                    <TableRow key={idx} hover>
                                        
                                        {/* KOLOM 1: NOMOR */}
                                        {row.rowspanKomponen > 0 && (
                                            <TableCell rowSpan={row.rowspanKomponen} align="center" sx={{ verticalAlign: 'top', bgcolor: '#fffdf0', borderRight: '1px solid #e0e0e0', fontWeight: 'bold' }}>
                                                {row.displayNo}
                                            </TableCell>
                                        )}

                                        {/* KOLOM 2: KOMPONEN */}
                                        {row.rowspanKomponen > 0 && (
                                            <TableCell rowSpan={row.rowspanKomponen} sx={{ verticalAlign: 'top', bgcolor: '#fffdf0', borderRight: '1px solid #e0e0e0', fontWeight: 'bold' }}>
                                                {row.nama_komponen}
                                            </TableCell>
                                        )}

                                        {/* KOLOM 3: SUB KOMPONEN */}
                                        {row.rowspanSub > 0 && (
                                            <TableCell rowSpan={row.rowspanSub} sx={{ verticalAlign: 'top', bgcolor: '#fafafa', borderRight: '1px solid #e0e0e0' }}>
                                                {row.nama_subkomponen}
                                            </TableCell>
                                        )}

                                        <TableCell sx={{ verticalAlign: 'top' }}>
                                            <Chip label={row.kode_kriteria} size="small" variant="outlined" />
                                        </TableCell>

                                        <TableCell sx={{ verticalAlign: 'top' }}>
                                            <Typography variant="body2">{row.nama_kriteria}</Typography>
                                        </TableCell>

                                        {/* KOLOM STATUS (Baca dari bukti_list backend) */}
                                        <TableCell sx={{ verticalAlign: 'top' }}>
                                            <List dense disablePadding>
                                                {row.bukti_list && row.bukti_list.map((bukti, i) => (
                                                    <ListItem key={i} disablePadding sx={{ py: 0.5, borderBottom: '1px dashed #eee' }}>
                                                        <ListItemIcon sx={{ minWidth: 30 }}>
                                                            {bukti.status === 'Ada' ? (
                                                                <CheckCircleIcon color="success" fontSize="small" />
                                                            ) : bukti.status === 'Tersedia di Sistem (Belum Verif)' ? (
                                                                <InfoIcon color="warning" fontSize="small" />
                                                            ) : (
                                                                <CancelIcon color="error" fontSize="small" />
                                                            )}
                                                        </ListItemIcon>
                                                        
                                                        <Box sx={{ display: 'flex', flexDirection: 'column' }}>
                                                            <Typography variant="body2" sx={{ fontWeight: bukti.status === 'Ada' ? 'bold' : 'normal' }}>
                                                                {bukti.nama_dokumen}
                                                            </Typography>
                                                            
                                                            {/* Tampilkan Link PDF GDrive Jika 'Ada' */}
                                                            {bukti.status === 'Ada' && bukti.file_link && (
                                                                <Typography 
                                                                    component="a" 
                                                                    href={bukti.file_link}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    variant="caption"
                                                                    sx={{ textDecoration: 'none', color: '#1976d2', cursor: 'pointer', fontWeight: 'bold', '&:hover': { textDecoration: 'underline' } }}
                                                                >
                                                                    [ Lihat Dokumen ]
                                                                </Typography>
                                                            )}

                                                            {/* Tampilkan Teks Oranye Jika ada di Sistem tapi belum jadi PDF LKE */}
                                                            {bukti.status === 'Tersedia di Sistem (Belum Verif)' && (
                                                                <Typography variant="caption" sx={{ color: '#ed6c02', fontStyle: 'italic', fontWeight: 'bold' }}>
                                                                    Terdeteksi di Database Sistem
                                                                </Typography>
                                                            )}
                                                        </Box>
                                                    </ListItem>
                                                ))}
                                            </List>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TableContainer>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}