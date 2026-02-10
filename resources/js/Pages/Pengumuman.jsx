// resources/js/Pages/Pengumuman/Index.jsx
import React, { useState } from 'react';
import { Head, Link, usePage, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// Import komponen Material-UI
import {
    Card, CardHeader, CardContent, Button, Alert, Collapse,
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper,
    IconButton, Box, Dialog, DialogActions, DialogContent, DialogTitle, TextField
} from '@mui/material';

// Impor Ikon
import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

// Komponen Modal Tambah/Edit
function PengumumanModal({ open, onClose, handleSubmit, data, setData, processing, errors }) {
    return (
        <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
            <Box component="form" onSubmit={handleSubmit}>
                <DialogTitle>Tambah Pengumuman</DialogTitle>
                <DialogContent>
                    <TextField
                        autoFocus
                        margin="dense"
                        id="judul"
                        name="judul"
                        label="Judul Pengumuman"
                        type="text"
                        fullWidth
                        variant="outlined"
                        value={data.judul}
                        onChange={(e) => setData('judul', e.target.value)}
                        required
                        error={!!errors.judul}
                        helperText={errors.judul}
                    />
                    <TextField
                        margin="dense"
                        id="isi"
                        name="isi"
                        label="Isi Pengumuman"
                        type="text"
                        fullWidth
                        multiline
                        rows={4}
                        variant="outlined"
                        value={data.isi}
                        onChange={(e) => setData('isi', e.target.value)}
                        required
                        error={!!errors.isi}
                        helperText={errors.isi}
                    />
                </DialogContent>
                <DialogActions sx={{ p: 3 }}>
                    <Button onClick={onClose} color="inherit">Batal</Button>
                    <Button type="submit" variant="contained" disabled={processing}>
                        {processing ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                </DialogActions>
            </Box>
        </Dialog>
    );
}


// Komponen Halaman Utama
export default function Pengumuman() {
    // Ambil props dari controller
    const { pengumuman, flash = {} } = usePage().props;

    // State untuk modal
    const [modalOpen, setModalOpen] = useState(false);
    const [showFlash, setShowFlash] = useState(true);

    // Form handling untuk modal Tambah
    const { data, setData, post, processing, errors, reset } = useForm({
        judul: '',
        isi: '',
    });

    const handleOpenModal = () => setModalOpen(true);
    const handleCloseModal = () => {
        setModalOpen(false);
        reset(); // Reset form saat modal ditutup
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        // Ganti dengan URL (tanpa Ziggy)
        post('pengumuman/store', { 
            onSuccess: () => {
                handleCloseModal();
                setShowFlash(true);
            },
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Pengumuman" />

            <Card elevation={3}>
                <CardHeader
                    title="Daftar Pengumuman"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    {/* Tampilkan Flash Message */}
                    <Collapse in={showFlash && !!flash.success}>
                        <Alert severity="success" onClose={() => setShowFlash(false)} sx={{ mb: 2 }}>
                            {flash.success}
                        </Alert>
                    </Collapse>

                    {/* Tombol Tambah Pengumuman */}
                    <Button
                        variant="contained"
                        color="primary" // Ini akan mengambil warna kuning dari tema Anda
                        startIcon={<AddIcon />}
                        onClick={handleOpenModal}
                        sx={{ mb: 2 }}
                    >
                        Tambah Pengumuman
                    </Button>

                    {/* Tabel List Pengumuman */}
                    <TableContainer component={Paper} elevation={2}>
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableCell>Judul</TableCell>
                                    <TableCell>Isi</TableCell>
                                    <TableCell align="right">Aksi</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {pengumuman.map((item) => (
                                    <TableRow key={item.id} hover>
                                        <TableCell>{item.judul}</TableCell>
                                        {/* Gunakan whiteSpace: 'pre-line' agar newline dari textarea tampil */}
                                        <TableCell sx={{ whiteSpace: 'pre-line' }}>{item.isi}</TableCell>
                                        <TableCell align="right">
                                            {/* Tombol Edit (Link ke halaman edit) */}
                                            <IconButton
                                                component={Link}
                                                href={`/pengumuman/${item.id}/edit`} // URL tanpa Ziggy
                                                color="success"
                                                size="small"
                                                title="Edit"
                                            >
                                                <EditIcon />
                                            </IconButton>
                                            
                                            {/* Tombol Hapus (Form submit via Link) */}
                                            <IconButton
                                                component={Link}
                                                href={`/pengumuman/${item.id}`} // URL tanpa Ziggy
                                                method="delete" // Method DELETE
                                                as="button" // Render sebagai tombol
                                                onBefore={() => confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')}
                                                preserveScroll
                                                color="error"
                                                size="small"
                                                title="Hapus"
                                            >
                                                <DeleteIcon />
                                            </IconButton>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TableContainer>
                </CardContent>
            </Card>

            {/* Modal Tambah */}
            <PengumumanModal
                open={modalOpen}
                onClose={handleCloseModal}
                handleSubmit={handleSubmit}
                data={data}
                setData={setData}
                processing={processing}
                errors={errors}
            />
        </AuthenticatedLayout>
    );
}