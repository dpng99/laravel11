// resources/js/Components/Perencanaan/DipaTab.jsx
import React, { useState } from 'react';
import { useForm, Link, usePage } from '@inertiajs/react';
import { 
    Box, Card, CardContent, CardHeader, Button, Alert, LinearProgress, Typography, Paper, 
    TableContainer, Table, TableHead, TableBody, TableRow, TableCell, IconButton, Collapse, TextField
} from '@mui/material';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

// Helper format angka
const formatNumber = (value) => {
  if (!value) return '';
  return new Intl.NumberFormat('id-ID').format(value);
};
const parseNumber = (value) => {
  return value.replace(/\D/g, ''); // Hapus semua non-angka
};

export default function DipaTab({ dipaFiles, flashMessage, tahun, idSatker, onEditClick, deleteRoutePrefix }) {
    
    // State untuk nilai DIPA yang diformat
    const [formattedPagu, setFormattedPagu] = useState('');
    const [formattedGakyankum, setFormattedGakyankum] = useState('');
    const [formattedDukman, setFormattedDukman] = useState('');

    const { data, setData, post, processing, errors, progress, reset } = useForm({
        dipa_file: null,
        id_pagu: '',
        id_gakyankum: '',
        id_dukman: '',
    });

    const [showFlash, setShowFlash] = useState(true);

    // Handler untuk input angka kustom
    const handleNumberChange = (e, setter, fieldName) => {
        const rawValue = parseNumber(e.target.value);
        setter(formatNumber(rawValue)); // Update nilai yang terlihat
        setData(fieldName, rawValue); // Update nilai Inertia
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setShowFlash(true);
        post('/perencanaan/upload-dipa', { // URL Upload DIPA
            onSuccess: () => {
                reset();
                setFormattedPagu('');
                setFormattedGakyankum('');
                setFormattedDukman('');
            },
            preserveScroll: true,
        });
    };

    return (
        <Box>
            <Typography variant="h5" sx={{ mb: 1, fontWeight: 'bold' }}>Daftar Isian Pelaksanaan Anggaran (DIPA)</Typography>
            <Paper sx={{ p: 2, backgroundColor: '#f1e022', color: 'black', mb: 2 }}>
                <Typography>Daftar Isian Pelaksanaan Anggaran (DIPA) Kejaksaan menjadi dasar...</Typography>
            </Paper>
            
            <Collapse in={showFlash && !!flashMessage}>
                <Alert severity="success" onClose={() => setShowFlash(false)} sx={{ mb: 2 }}>{flashMessage}</Alert>
            </Collapse>

            {/* Form Upload DIPA */}
            <Card component={Paper} elevation={2} sx={{ mb: 3 }}>
                <CardHeader 
                    title="UPLOAD DIPA SATKER ANDA"
                    sx={{ backgroundColor: '#e6bf3e', color: 'white' }}
                />
                <CardContent>
                    <Box component="form" onSubmit={handleSubmit}>
                        <TextField
                            label="Total Pagu"
                            variant="outlined"
                            fullWidth
                            margin="normal"
                            value={formattedPagu}
                            onChange={(e) => handleNumberChange(e, setFormattedPagu, 'id_pagu')}
                            error={!!errors.id_pagu}
                            helperText={errors.id_pagu}
                        />
                        <TextField
                            label="Program Penegakan dan Pelayanan Hukum"
                            variant="outlined"
                            fullWidth
                            margin="normal"
                            value={formattedGakyankum}
                            onChange={(e) => handleNumberChange(e, setFormattedGakyankum, 'id_gakyankum')}
                            error={!!errors.id_gakyankum}
                            helperText={errors.id_gakyankum}
                        />
                        <TextField
                            label="Program Dukungan Manajemen"
                            variant="outlined"
                            fullWidth
                            margin="normal"
                            value={formattedDukman}
                            onChange={(e) => handleNumberChange(e, setFormattedDukman, 'id_dukman')}
                            error={!!errors.id_dukman}
                            helperText={errors.id_dukman}
                        />
                        <Button variant="outlined" component="label" fullWidth sx={{ mt: 2 }}>
                            Pilih File PDF DIPA (Max: 2MB)
                            <input 
                                type="file" hidden name="dipa_file" accept=".pdf" required
                                onChange={(e) => setData('dipa_file', e.target.files[0])}
                            />
                        </Button>
                        {data.dipa_file && <Typography sx={{ mt: 1, fontStyle: 'italic' }}>File: {data.dipa_file.name}</Typography>}
                        {errors.dipa_file && <Typography color="error" variant="caption">{errors.dipa_file}</Typography>}
                        
                        {progress && <LinearProgress variant="determinate" value={progress.percentage} sx={{ mt: 2 }} />}
                        
                        <Button type="submit" variant="contained" color="primary" sx={{ mt: 3 }} disabled={processing}>
                            {processing ? "Mengunggah..." : "Upload File"}
                        </Button>
                    </Box>
                </CardContent>
            </Card>

            {/* Tabel Data DIPA */}
            <TableContainer component={Paper} elevation={2}>
                <Table>
                    <TableHead sx={{ backgroundColor: '#f0bb49' }}>
                        <TableRow>
                            <TableCell>No</TableCell>
                            <TableCell>File DIPA</TableCell>
                            <TableCell>Total Pagu</TableCell>
                            <TableCell>Prog. Gakyankum</TableCell>
                            <TableCell>Prog. Dukman</TableCell>
                            <TableCell>Versi</TableCell>
                            <TableCell>Tgl Upload</TableCell>
                            <TableCell>Aksi</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {dipaFiles.length === 0 ? (
                            <TableRow><TableCell colSpan={8} align="center">Belum ada data DIPA.</TableCell></TableRow>
                        ) : (
                            dipaFiles.map((file, index) => (
                                <TableRow key={file.id} hover>
                                    <TableCell>{index + 1}</TableCell>
                                    <TableCell>
                                        <a href={`/uploads/repository/${idSatker}/${file.id_filename}`} target="_blank" rel="noopener noreferrer">
                                            DIPA {file.id_periode}
                                        </a>
                                    </TableCell>
                                    <TableCell>Rp. {formatNumber(file.id_pagu)}</TableCell>
                                    <TableCell>Rp. {formatNumber(file.id_gakyankum)}</TableCell>
                                    <TableCell>Rp. {formatNumber(file.id_dukman)}</TableCell>
                                    <TableCell>{file.id_perubahan}</TableCell>
                                    <TableCell>{file.id_tglupload}</TableCell>
                                    <TableCell>
                                        {/* <IconButton color="warning" size="small" onClick={() => onEditClick(file)}>
                                            <EditIcon />
                                        </IconButton> */}
                                        <IconButton
                                            component={Link}
                                            href={`${deleteRoutePrefix}/${file.id}`}
                                            method="delete" as="button"
                                            onBefore={() => confirm('Apakah Anda yakin ingin menghapus file ini?')}
                                            preserveScroll color="error" size="small"
                                        >
                                            <DeleteIcon />
                                        </IconButton>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </TableContainer>
        </Box>
    );
}