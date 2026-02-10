// resources/js/Pages/Aturan/Create.jsx
import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardContent, Typography } from '@mui/material';
import AturanForm from '@/Components/Aturan/AturanForm'; // Impor form reusable

export default function AturanCreate() {
    // Inisialisasi useForm untuk data baru
    const { data, setData, post, processing, errors, progress } = useForm({
        id_namaproduk: '',
        id_produsen: '',
        id_tahun: new Date().getFullYear(), // Default ke tahun ini
        file: null,
    });

    // Fungsi submit untuk 'store'
    const submit = (e) => {
        e.preventDefault();
        // POST ke /aturan. Inertia akan otomatis menangani 'multipart/form-data'
        post('/aturan', { 
            preserveScroll: true,
            onSuccess: () => {
                // Form akan di-reset otomatis jika sukses
            }
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Tambah Aturan" />
            <Card elevation={3}>
                <CardHeader
                    title="Tambah Peraturan"
                    sx={{ backgroundColor: '#e3e2e2' }} // Warna abu-abu dari Blade
                />
                <CardContent>
                    <AturanForm
                        data={data}
                        setData={setData}
                        handleSubmit={submit}
                        processing={processing}
                        errors={errors}
                        progress={progress}
                        isEditing={false} // Beri tahu form ini adalah mode 'create'
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}