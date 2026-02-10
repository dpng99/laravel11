// resources/js/Pages/Pengumuman/Edit.jsx
import React from 'react';
import { Head, usePage, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// Import komponen Material-UI
import {
    Card, CardHeader, CardContent, TextField, Button, Box
} from '@mui/material';

export default function PengumumanEdit() {
    // 1. Ambil data 'pengumuman' yang dikirim dari controller
    const { pengumuman } = usePage().props;

    // 2. Inisialisasi useForm dengan data yang ada
    // Ini menggantikan value="{{ $pengumuman->judul }}"
    const { data, setData, put, processing, errors } = useForm({
        judul: pengumuman.judul || '',
        isi: pengumuman.isi || '',
    });

    // 3. Buat fungsi submit
    const submit = (e) => {
        e.preventDefault();
        // Gunakan method 'put' (menggantikan @method('PUT'))
        // Arahkan ke URL update (tanpa Ziggy)
        put(`/pengumuman/${pengumuman.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Edit Pengumuman" />

            <Card elevation={3}>
                <CardHeader
                    title="Edit Pengumuman"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    {/* 4. Ganti <form> dengan <Box component="form"> */}
                    <Box component="form" onSubmit={submit} noValidate>
                        
                        {/* Judul Pengumuman */}
                        <TextField
                            margin="normal"
                            required
                            fullWidth
                            id="judul"
                            label="Judul Pengumuman"
                            name="judul"
                            autoComplete="off"
                            value={data.judul} // Bind ke state useForm
                            onChange={(e) => setData('judul', e.target.value)} // Update state
                            error={!!errors.judul} // Tampilkan error jika ada
                            helperText={errors.judul} // Pesan error
                        />
                        
                        {/* Isi Pengumuman */}
                        <TextField
                            margin="normal"
                            required
                            fullWidth
                            name="isi"
                            label="Isi Pengumuman"
                            id="isi"
                            multiline
                            rows={4}
                            value={data.isi} // Bind ke state useForm
                            onChange={(e) => setData('isi', e.target.value)} // Update state
                            error={!!errors.isi} // Tampilkan error jika ada
                            helperText={errors.isi} // Pesan error
                        />
                        
                        <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 2, mt: 3 }}>
                            {/* Tombol Batal untuk kembali ke index */}
                            <Button
                                component={Link}
                                href="/pengumuman" // URL ke halaman index (tanpa Ziggy)
                                color="inherit"
                                disabled={processing}
                            >
                                Batal
                            </Button>
                            
                            {/* Tombol Update */}
                            <Button
                                type="submit"
                                variant="contained"
                                color="primary"
                                disabled={processing} // Nonaktifkan saat submitting
                            >
                                {processing ? 'Updating...' : 'Update'}
                            </Button>
                        </Box>
                    </Box>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}