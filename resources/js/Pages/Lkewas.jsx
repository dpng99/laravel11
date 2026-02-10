// resources/js/Pages/LkeWas/Index.jsx
import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// Import komponen Material-UI
import {
    Card, CardHeader, CardContent, Button, Typography, Paper,
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow
} from '@mui/material';
import InfoIcon from '@mui/icons-material/Info'; // Ikon untuk tombol Detail

export default function Lkewas() {
    // Ambil props dari LkeWasController@index
    const { list_kejari } = usePage().props;

    return (
        <AuthenticatedLayout>
            <Head title="LKE Pengawasan" />

            <Card elevation={3}>
                <CardHeader
                    title="Bukti Dukung LKE AKIP Internal Tahun 2025"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    <Typography paragraph>
                        Halaman ini digunakan untuk melihat dokumen/bukti dukung setiap satuan kerja sebagaimana tercantum pada Lembar Kerja Evaluasi (LKE) AKIP Tahun 2025. Adapun untuk memberikan nilai akhir evaluasi AKIP setiap satker tetap menggunakan LKE yg telah disampaikan oleh Bidang Pembinaan (format excel)
                    </Typography>

                    {/* Tabel Daftar Satuan Kerja */}
                    <TableContainer component={Paper} elevation={2} sx={{ mt: 2 }}>
                        <Table>
                            <TableHead sx={{ backgroundColor: '#f0bb49' }}>
                                <TableRow>
                                    <TableCell sx={{ width: '40px' }}>No</TableCell>
                                    <TableCell>Nama Satuan Kerja</TableCell>
                                    <TableCell sx={{ width: '150px' }} align="center">Aksi</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {list_kejari && list_kejari.length > 0 ? (
                                    list_kejari.map((kejari, index) => (
                                        <TableRow key={kejari.id_satker} hover>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell>{kejari.satkernama.replace(/_/g, ' ')}</TableCell>
                                            <TableCell align="center">
                                                {/* Tombol Detail (Link Inertia) */}
                                                <Button
                                                    component={Link}
                                                    // Gunakan URL string (tanpa Ziggy)
                                                    href={`/was-lke/${kejari.id_satker}`} 
                                                    variant="contained"
                                                    color="info"
                                                    size="small"
                                                    startIcon={<InfoIcon />}
                                                >
                                                    Detail
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={3} align="center">
                                            Tidak ada data satuan kerja.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>

                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}