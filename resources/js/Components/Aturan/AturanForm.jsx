// resources/js/Components/Aturan/AturanForm.jsx
import React from 'react';
import { Link } from '@inertiajs/react';
import { Box, TextField, Button, Typography, LinearProgress, Alert } from '@mui/material';

export default function AturanForm({
    data,
    setData,
    handleSubmit,
    processing,
    errors,
    progress,
    isEditing = false,
    currentFile = null 
}) {
    return (
        <Box component="form" onSubmit={handleSubmit} noValidate>
            {/* Nama Peraturan */}
            <TextField
                margin="normal"
                required
                fullWidth
                id="id_namaproduk"
                label="Nama Peraturan"
                name="id_namaproduk"
                value={data.id_namaproduk}
                onChange={(e) => setData('id_namaproduk', e.target.value)}
                error={!!errors.id_namaproduk}
                helperText={errors.id_namaproduk}
                disabled={processing}
            />

            {/* Pemilik */}
            <TextField
                margin="normal"
                required
                fullWidth
                id="id_produsen"
                label="Pemilik"
                name="id_produsen"
                value={data.id_produsen}
                onChange={(e) => setData('id_produsen', e.target.value)}
                error={!!errors.id_produsen}
                helperText={errors.id_produsen}
                disabled={processing}
            />

            {/* Tahun */}
            <TextField
                margin="normal"
                required
                fullWidth
                id="id_tahun"
                label="Tahun"
                name="id_tahun"
                type="number"
                value={data.id_tahun}
                onChange={(e) => setData('id_tahun', e.target.value)}
                error={!!errors.id_tahun}
                helperText={errors.id_tahun}
                disabled={processing}
            />

            {/* File Input */}
            <Box sx={{ mt: 2, border: '1px dashed grey', p: 2, borderRadius: 1 }}>
                {isEditing && currentFile && (
                    <Typography variant="body2" sx={{ mb: 1 }}>
                        File saat ini: 
                        <a 
                            href={`/uploads/peraturan/${currentFile}`} 
                            target="_blank" 
                            rel="noopener noreferrer"
                            style={{ marginLeft: '8px' }}
                        >
                            {currentFile}
                        </a>
                    </Typography>
                )}
                
                <Button variant="outlined" component="label" fullWidth disabled={processing}>
                    {isEditing ? 'Upload File Baru (Opsional)' : 'Upload File (PDF)'}
                    <input 
                        type="file" 
                        hidden 
                        accept=".pdf"
                        // Wajib di 'create', opsional di 'edit'
                        required={!isEditing} 
                        // Inertia akan otomatis mendeteksi file
                        onChange={(e) => setData('file', e.target.files[0])} 
                    />
                </Button>
                
                {/* Menampilkan nama file yang baru dipilih */}
                {data.file && (
                    <Typography sx={{ mt: 1, fontStyle: 'italic' }}>
                        File terpilih: {data.file.name}
                    </Typography>
                )}
                
                {/* Menampilkan error validasi file */}
                {errors.file && (
                    <Typography color="error" variant="caption" sx={{ display: 'block', mt: 1 }}>
                        {errors.file}
                    </Typography>
                )}
            </Box>
            
            {/* Progress bar untuk file upload */}
            {progress && (
                <LinearProgress variant="determinate" value={progress.percentage} sx={{ mt: 2 }} />
            )}

            {/* Tombol Aksi */}
            <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 2, mt: 3 }}>
                <Button 
                    component={Link} 
                    href="/aturan" // URL (tanpa Ziggy) ke halaman index
                    color="inherit" 
                    disabled={processing}
                >
                    Batal
                </Button>
                <Button 
                    type="submit" 
                    variant="contained" 
                    color="primary" // Ini akan menjadi kuning sesuai tema
                    disabled={processing}
                >
                    {isEditing ? (processing ? 'Updating...' : 'Update') : (processing ? 'Menyimpan...' : 'Simpan')}
                </Button>
            </Box>
        </Box>
    );
}