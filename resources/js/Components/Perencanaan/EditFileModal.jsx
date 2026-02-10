// resources/js/Components/Perencanaan/EditFileModal.jsx
import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { 
    Dialog, DialogTitle, DialogContent, DialogActions, Button, 
    TextField, Box, Typography, LinearProgress 
} from '@mui/material';

// Helper format angka
const formatNumber = (value) => {
  if (!value) return '';
  return new Intl.NumberFormat('id-ID').format(value);
};
const parseNumber = (value) => {
  if (!value) return '';
  return value.replace(/\D/g, ''); // Hapus semua non-angka
};

export default function EditFileModal({ open, onClose, file, type, actionUrl }) {
    
    // State untuk nilai DIPA yang diformat
    const [formattedPagu, setFormattedPagu] = useState('');
    const [formattedGakyankum, setFormattedGakyankum] = useState('');
    const [formattedDukman, setFormattedDukman] = useState('');

    const { data, setData, post, processing, errors, progress, reset } = useForm({
        file: null,
        id_pagu: '',
        id_gakyankum: '',
        id_dukman: '',
    });

    // Isi form saat 'file' atau 'type' berubah
    useEffect(() => {
        if (file) {
            if (type === 'dipa') {
                setData({
                    id_pagu: file.id_pagu || '',
                    id_gakyankum: file.id_gakyankum || '',
                    id_dukman: file.id_dukman || '',
                    file: null,
                });
                // Set nilai terformat juga
                setFormattedPagu(formatNumber(file.id_pagu));
                setFormattedGakyankum(formatNumber(file.id_gakyankum));
                setFormattedDukman(formatNumber(file.id_dukman));
            } else {
                reset(); // Reset untuk tipe file lain
            }
        }
    }, [file, type]);

    // Handler untuk input angka kustom DIPA
    const handleDipaChange = (e, setter, fieldName) => {
        const rawValue = parseNumber(e.target.value);
        setter(formatNumber(rawValue)); // Update nilai yang terlihat
        setData(fieldName, rawValue); // Update nilai Inertia
    };
    
    const handleSubmit = (e) => {
        e.preventDefault();
        // Inertia 'post' otomatis menangani file upload dan method spoofing
        post(actionUrl, {
            onSuccess: () => onClose(), // Tutup modal saat sukses
            preserveScroll: true,
        });
    };

    return (
        <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
            <DialogTitle>Edit Dokumen</DialogTitle>
            <Box component="form" onSubmit={handleSubmit}>
                <DialogContent>
                    <Typography gutterBottom>
                        File saat ini: <strong>{file?.id_filename || 'N/A'}</strong>
                    </Typography>
                    
                    <Button
                        variant="outlined"
                        component="label"
                        fullWidth
                        sx={{ mt: 2 }}
                    >
                        Upload File Baru (PDF, Max 5MB)
                        <input 
                            type="file"
                            hidden
                            name="file"
                            accept=".pdf"
                            onChange={(e) => setData('file', e.target.files[0])}
                        />
                    </Button>
                    {data.file && <Typography sx={{ mt: 1, fontStyle: 'italic' }}>File baru: {data.file.name}</Typography>}
                    <Typography variant="caption" color="textSecondary">
                        Kosongkan jika hanya ingin update data Pagu (khusus DIPA).
                    </Typography>
                    {errors.file && <Typography color="error" variant="caption">{errors.file}</Typography>}

                    {/* === Bidang Khusus DIPA === */}
                    {type === 'dipa' && (
                        <Box sx={{ mt: 2, borderTop: '1px solid #ddd', pt: 2 }}>
                            <Typography variant="subtitle1" sx={{ fontWeight: 'bold' }}>Data DIPA:</Typography>
                            <TextField
                                label="Total Pagu"
                                variant="outlined"
                                fullWidth
                                margin="normal"
                                value={formattedPagu}
                                onChange={(e) => handleDipaChange(e, setFormattedPagu, 'id_pagu')}
                                error={!!errors.id_pagu}
                                helperText={errors.id_pagu}
                            />
                            <TextField
                                label="Program Penegakan dan Pelayanan Hukum"
                                variant="outlined"
                                fullWidth
                                margin="normal"
                                value={formattedGakyankum}
                                onChange={(e) => handleDipaChange(e, setFormattedGakyankum, 'id_gakyankum')}
                                error={!!errors.id_gakyankum}
                                helperText={errors.id_gakyankum}
                            />
                            <TextField
                                label="Program Dukungan Manajemen"
                                variant="outlined"
                                fullWidth
                                margin="normal"
                                value={formattedDukman}
                                onChange={(e) => handleDipaChange(e, setFormattedDukman, 'id_dukman')}
                                error={!!errors.id_dukman}
                                helperText={errors.id_dukman}
                            />
                        </Box>
                    )}
                    
                    {progress && <LinearProgress variant="determinate" value={progress.percentage} sx={{ mt: 2 }} />}
                    
                </DialogContent>
                <DialogActions sx={{ p: 3 }}>
                    <Button onClick={onClose} color="inherit">Batal</Button>
                    <Button type="submit" variant="contained" disabled={processing}>
                        {processing ? "Menyimpan..." : "Simpan Perubahan"}
                    </Button>
                </DialogActions>
            </Box>
        </Dialog>
    );
}