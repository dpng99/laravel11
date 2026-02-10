// resources/js/Pages/Aturan/Index.jsx
import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// Import komponen Material-UI
import {
    Card, CardHeader, CardContent, Button, Alert, Collapse,
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper,
    IconButton, Tooltip, Typography
} from '@mui/material';

// Impor Ikon
import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

export default function Aturan() {
    // Ambil props dari AturanController@index
    const { aturan, auth } = usePage().props;
    
    // Dapatkan level user dan pastikan itu angka
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);

    // State untuk mengontrol visibilitas alert
    const [showFlash, setShowFlash] = useState(true);

    // Reset state alert jika props flash berubah


    return (
        <AuthenticatedLayout>
            <Head title="Sumber Aturan" />

            <Card elevation={3}>
                <CardHeader
                    title="Sumber Aturan"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    // Gunakan 'primary.main' (kuning) dari tema Anda
                    sx={{ backgroundColor: 'primary.main', color: 'white' }} 
                />
                <CardContent>
                    {/* Tampilkan Flash Message (session('success')) */}
                    {/* <Collapse in={showFlash && !!flash.success}>
                        <Alert 
                            severity="success" 
                            onClose={() => setShowFlash(false)} 
                            sx={{ mb: 2 }}
                        >
                            {flash.success}
                        </Alert>
                    </Collapse> */}

                    {/* Tombol Tambah (Hanya untuk levelSakip 99) */}
                    {levelSakip === 99 && (
                        <Button
                            component={Link}
                            href="/aturan/create" // URL (tanpa Ziggy) ke halaman create
                            variant="contained"
                            color="primary" // Ini akan menjadi kuning
                            startIcon={<AddIcon />}
                            sx={{ mb: 2 }}
                        >
                            Tambah Peraturan
                        </Button>
                    )}

                    {/* Tabel Data (menggantikan <table class="table...">) */}
                    <TableContainer component={Paper} elevation={2}>
                        <Table stickyHeader>
                            <TableHead>
                                {/* Menggunakan sx prop untuk style header kuning */}
                                <TableRow sx={{ '& th': { backgroundColor: '#f0bb49', color: 'black' } }}>
                                    <TableCell>No</TableCell>
                                    <TableCell sx={{ width: '60%' }}>Nama Peraturan</TableCell>
                                    <TableCell>Pemilik</TableCell>
                                    <TableCell>Tahun</TableCell>
                                    {levelSakip === 99 && <TableCell align="center">Aksi</TableCell>}
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {aturan.length > 0 ? (
                                    aturan.map((item, index) => (
                                        <TableRow key={item.id} hover>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell>
                                                {/* Logika link jika file ada */}
                                                {item.id_filename ? (
                                                    <Typography 
                                                        component="a" 
                                                        href={`/uploads/peraturan/${item.id_filename}`} 
                                                        target="_blank" 
                                                        rel="noopener noreferrer"
                                                        sx={{ textDecoration: 'none', color: 'inherit', fontWeight: 'bold' }}
                                                    >
                                                        {item.id_namaproduk}
                                                    </Typography>
                                                ) : (
                                                    item.id_namaproduk
                                                )}
                                            </TableCell>
                                            <TableCell>{item.id_produsen}</TableCell>
                                            <TableCell>{item.id_tahun}</TableCell>
                                            
                                            {/* Kolom Aksi (Hanya untuk levelSakip 99) */}
                                            {levelSakip === 99 && (
                                                <TableCell align="center">
                                                    {/* Tombol Edit (Link) */}
                                                    <Tooltip title="Edit">
                                                        <IconButton 
                                                            component={Link} 
                                                            href={`/aturan/${item.id}/edit`} // URL (tanpa Ziggy)
                                                            color="success" 
                                                            size="small"
                                                            sx={{ mr: 1 }}
                                                        >
                                                            <EditIcon />
                                                        </IconButton>
                                                    </Tooltip>
                                                    
                                                    {/* Tombol Hapus (Link Inertia dengan method DELETE) */}
                                                    <Tooltip title="Hapus">
                                                        <IconButton
                                                            component={Link}
                                                            href={`/aturan/${item.id}`} // URL (tanpa Ziggy)
                                                            method="delete" // Menggantikan @method('DELETE')
                                                            as="button" // Render sebagai <button>
                                                            // Menggantikan onclick confirm
                                                            onBefore={() => confirm('Apakah Anda yakin ingin menghapus peraturan ini?')} 
                                                            preserveScroll // Agar halaman tidak scroll ke atas
                                                            color="error"
                                                            size="small"
                                                        >
                                                            <DeleteIcon />
                                                        </IconButton>
                                                    </Tooltip>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))
                                ) : (
                                    // Tampilan jika data kosong
                                    <TableRow>
                                        <TableCell colSpan={levelSakip === 99 ? 5 : 4} align="center">
                                            Tidak ada data peraturan.
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