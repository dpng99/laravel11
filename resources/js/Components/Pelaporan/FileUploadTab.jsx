import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import { 
    Box, Card, CardContent, CardHeader, Button, Typography, Paper, 
    TableContainer, Table, TableHead, TableBody, TableRow, TableCell,
    IconButton, FormControl, InputLabel, Select, MenuItem, LinearProgress, Alert
} from '@mui/material';
import DeleteIcon from '@mui/icons-material/Delete';
import CloudUploadIcon from '@mui/icons-material/CloudUpload';

// Sub-komponen Tabel File
function FilesTable({ files, fileNamePrefix, tahun, idSatker, deleteRoutePrefix }) {
    return (
        <TableContainer component={Paper} sx={{ mt: 3 }} elevation={1}>
            <Table size="small">
                <TableHead sx={{ backgroundColor: '#fff3cd' }}> {/* Warna kuning muda ala warning */}
                    <TableRow>
                        <TableCell>No</TableCell>
                        <TableCell>Nama File</TableCell>
                        <TableCell>Triwulan</TableCell>
                        <TableCell>Versi</TableCell>
                        <TableCell>Tanggal Upload</TableCell>
                        <TableCell align="center">Aksi</TableCell>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {files.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={6} align="center" sx={{ py: 3, color: 'text.secondary' }}>
                                Belum ada dokumen yang diunggah.
                            </TableCell>
                        </TableRow>
                    ) : (
                        files.map((file, index) => (
                            <TableRow key={file.id} hover>
                                <TableCell>{index + 1}</TableCell>
                                <TableCell>
                                    <a 
                                        href={`/uploads/repository/${idSatker}/${file.id_filename}`} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        style={{ textDecoration: 'none', color: '#1976d2', fontWeight: 500 }}
                                    >
                                        {fileNamePrefix} ({tahun}) - {file.id_triwulan || file.triwulan}
                                    </a>
                                </TableCell>
                                <TableCell>{file.id_triwulan || file.triwulan}</TableCell>
                                <TableCell>{file.id_perubahan}</TableCell>
                                <TableCell>{file.id_tglupload}</TableCell>
                                <TableCell align="center">
                                    <IconButton
                                        component={Link}
                                        href={`${deleteRoutePrefix}/${file.id}`}
                                        method="delete"
                                        as="button"
                                        onBefore={() => confirm('Yakin ingin menghapus file ini?')}
                                        color="error"
                                        size="small"
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
    );
}

export default function FileUploadTab({ 
    title, 
    uploadRoute, 
    deleteRoutePrefix, 
    fileInputName, 
    triwulanInputName = "id_triwulan", 
    files, 
    tahun, 
    idSatker, 
    fileNamePrefix, 
    showTriwulanSelect = true 
}) {
    
    // Inisialisasi Form Inertia
    const { data, setData, post, processing, errors, progress, reset, clearErrors } = useForm({
        [triwulanInputName]: '',
        [fileInputName]: null,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        clearErrors();
        post(uploadRoute, {
            onSuccess: () => {
                reset();
                // Alert akan ditangani oleh flash message di parent (Pelaporan.jsx)
            },
            preserveScroll: true,
        });
    };

    return (
        <Box>
            <Typography variant="h6" gutterBottom>{title}</Typography>
            
            {/* Form Upload */}
            <Card variant="outlined" sx={{ mb: 3 }}>
                <CardHeader 
                    title={`Upload Dokumen ${title}`}
                    titleTypographyProps={{ variant: 'subtitle1', fontWeight: 'bold' }}
                    sx={{ backgroundColor: '#f8f9fa', borderBottom: '1px solid #eee' }}
                />
                <CardContent>
                    <Box component="form" onSubmit={handleSubmit}>
                        {showTriwulanSelect && (
                            <FormControl fullWidth size="small" sx={{ mb: 2 }} error={!!errors[triwulanInputName]}>
                                <InputLabel>Pilih Triwulan</InputLabel>
                                <Select
                                    value={data[triwulanInputName]}
                                    label="Pilih Triwulan"
                                    onChange={(e) => setData(triwulanInputName, e.target.value)}
                                >
                                    <MenuItem value="TW 1">Triwulan 1</MenuItem>
                                    <MenuItem value="TW 2">Triwulan 2</MenuItem>
                                    <MenuItem value="TW 3">Triwulan 3</MenuItem>
                                    <MenuItem value="TW 4">Triwulan 4</MenuItem>
                                </Select>
                                {errors[triwulanInputName] && (
                                    <Typography variant="caption" color="error" sx={{ mx: 2 }}>
                                        {errors[triwulanInputName]}
                                    </Typography>
                                )}
                            </FormControl>
                        )}

                        <Button
                            component="label"
                            variant="outlined"
                            fullWidth
                            startIcon={<CloudUploadIcon />}
                            sx={{ mb: 1, borderStyle: 'dashed', py: 1.5 }}
                            color={errors[fileInputName] ? 'error' : 'primary'}
                        >
                            {data[fileInputName] ? data[fileInputName].name : "Pilih File PDF (Max 4MB)"}
                            <input
                                type="file"
                                hidden
                                accept="application/pdf"
                                onChange={(e) => setData(fileInputName, e.target.files[0])}
                            />
                        </Button>
                        {errors[fileInputName] && (
                            <Typography variant="caption" color="error" display="block" sx={{ mb: 2 }}>
                                {errors[fileInputName]}
                            </Typography>
                        )}

                        {progress && (
                            <Box sx={{ width: '100%', mb: 2 }}>
                                <LinearProgress variant="determinate" value={progress.percentage} />
                            </Box>
                        )}

                        <Button 
                            type="submit" 
                            variant="contained" 
                            fullWidth
                            disabled={processing}
                            sx={{ mt: 1, backgroundColor: '#e6bf3e', '&:hover': { backgroundColor: '#d4af37' } }}
                        >
                            {processing ? 'Mengunggah...' : 'Upload File'}
                        </Button>
                    </Box>
                </CardContent>
            </Card>

            {/* Tabel List File */}
            <FilesTable 
                files={files} 
                fileNamePrefix={fileNamePrefix} 
                tahun={tahun} 
                idSatker={idSatker} 
                deleteRoutePrefix={deleteRoutePrefix}
            />
        </Box>
    );
}