// resources/js/Pages/Aturan/Edit.jsx
import React from 'react';
import { Head, usePage, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardContent } from '@mui/material';
import AturanForm from '@/Components/Aturan/AturanForm'; // Impor form reusable

export default function AturanEdit() {
    // Ambil data 'aturan' yang dikirim dari AturanController@edit
    const { aturan } = usePage().props;

    // Inisialisasi useForm dengan data yang ada
    const { data, setData, post, processing, errors, progress } = useForm({
        id_namaproduk: aturan.id_namaproduk || '',
        id_produsen: aturan.id_produsen || '',
        id_tahun: aturan.id_tahun || '',
        file: null, // File baru (opsional)
        _method: 'PUT', // <-- Wajib untuk method spoofing (menggantikan @method('PUT'))
    });

    // Fungsi submit untuk 'update'
    const submit = (e) => {
        e.preventDefault();
        // POST ke /aturan/{id}. Inertia akan handle _method dan file upload
        post(`/aturan/${aturan.id}`, {
            preserveScroll: true,
            forceFormData: true, // Paksa multipart/form-data bahkan jika file null
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Edit Aturan" />
            <Card elevation={3}>
                <CardHeader
                    title="Edit Peraturan"
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
                        isEditing={true} // Beri tahu form ini adalah mode 'edit'
                        currentFile={aturan.id_filename} // Kirim nama file yang ada
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}