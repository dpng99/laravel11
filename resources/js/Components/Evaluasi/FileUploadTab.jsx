// resources/js/Components/Evaluasi/FileUploadTab.jsx
import React, { useState, useEffect } from 'react';
import { useForm, Link, usePage } from '@inertiajs/react';
import { 
    Box, Card, CardContent, CardHeader, Button, Alert, FormControl, 
    InputLabel, Select, MenuItem, LinearProgress, Typography, Paper, 
    TableContainer, Table, TableHead, TableBody, TableRow, TableCell, Collapse
} from '@mui/material';

// Komponen Tabel
function FilesTable({ files, fileNamePrefix, tahun, idSatker, showTriwulan }) {
    return (
        <TableContainer component={Paper} sx={{ mt: 3 }} elevation={2}>
            <Table>
                <TableHead sx={{ backgroundColor: '#f0bb49' }}>
                    <TableRow>
                        <TableCell>No</TableCell>
                        <TableCell>Nama File</TableCell>
                        {showTriwulan && <TableCell>Triwulan</TableCell>}
                        <TableCell>Versi</TableCell>
                        <TableCell>Tanggal Upload</TableCell>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {files.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={showTriwulan ? 5 : 4} align="center">
                                Belum ada file yang diupload.
                            </TableCell>
                        </TableRow>
                    ) : (
                        files.map((file, index) => (
                            <TableRow key={file.id} hover>
                                <TableCell>{index + 1}</TableCell>
                                <TableCell>
                                    <a href={`/uploads/repository/${idSatker}/${file.id_filename}`} target="_blank" rel="noopener noreferrer">
                                        {fileNamePrefix} ({tahun})
                                        {showTriwulan && file.id_triwulan && ` - ${file.id_triwulan}`}
                                    </a>
                                </TableCell>
                                {showTriwulan && <TableCell>{file.id_triwulan || '-'}</TableCell>}
                                <TableCell>{file.id_perubahan}</TableCell>
                                <TableCell>{file.id_tglupload}</TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
        </TableContainer>
    );
}

// Komponen Form
export default function FileUploadTab({ 
    title, uploadRoute, fileInputName, flashMessage, files, 
    tahun, idSatker, fileNamePrefix, 
    showTriwulanSelect = false,
    triwulanInputName = "id_triwulan" 
}) {
    
    const { data, setData, post, processing, errors, progress, reset } = useForm({
        [fileInputName]: null,
        [triwulanInputName]: '',
    });

    const [showFlash, setShowFlash] = useState(true);

    // Reset flash message visibility when prop changes
    useEffect(() => {
        setShowFlash(true);
    }, [flashMessage]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setShowFlash(true); // Tampilkan lagi jika ada flash message baru
        post(uploadRoute, {
            onSuccess: () => reset(),
            preserveScroll: true,
        });
    };

    return (
        <Box>
            <Typography variant="h5" sx={{ mb: 2 }}>{title}</Typography>
            
            <Collapse in={showFlash && !!flashMessage}>
                <Alert severity="success" onClose={() => setShowFlash(false)} sx={{ mb: 2 }}>
                    {flashMessage}
                </Alert>
            </Collapse>

            <Card component={Paper} elevation={3}>
                <CardHeader 
                    title={`Upload Dokumen ${title}`}
                    sx={{ backgroundColor: '#e6bf3e', color: 'white' }}
                />
                <CardContent>
                    <Box component="form" onSubmit={handleSubmit}>
                        {showTriwulanSelect && (
                            <FormControl fullWidth margin="normal" required>
                                <InputLabel id="triwulan-select-label">Pilih Triwulan</InputLabel>
                                <Select
                                    labelId="triwulan-select-label"
                                    id={triwulanInputName}
                                    name={triwulanInputName}
                                    value={data[triwulanInputName]}
                                    label="Pilih Triwulan"
                                    onChange={(e) => setData(triwulanInputName, e.target.value)}
                                >
                                    <MenuItem value="" disabled>Pilih Triwulan</MenuItem>
                                    <MenuItem value="TW 1">Triwulan 1</MenuItem>
                                    <MenuItem value="TW 2">Triwulan 2</MenuItem>
                                    <MenuItem value="TW 3">Triwulan 3</MenuItem>
                                    <MenuItem value="TW 4">Triwulan 4</MenuItem>
                                </Select>
                                {errors[triwulanInputName] && <Typography color="error" variant="caption">{errors[triwulanInputName]}</Typography>}
                            </FormControl>
                        )}
                        
                        <Button
                            variant="outlined"
                            component="label"
                            fullWidth
                            sx={{ mt: 2 }}
                        >
                            Pilih File PDF (Max: 4MB)
                            <input 
                                type="file"
                                hidden
                                name={fileInputName}
                                accept=".pdf"
                                required
                                onChange={(e) => setData(fileInputName, e.target.files[0])}
                            />
                        </Button>
                        {data[fileInputName] && <Typography sx={{ mt: 1, fontStyle: 'italic' }}>File: {data[fileInputName].name}</Typography>}
                        {errors[fileInputName] && <Typography color="error" variant="caption">{errors[fileInputName]}</Typography>}

                        {progress && (
                            <Box sx={{ width: '100%', mt: 2 }}>
                                <LinearProgress variant="determinate" value={progress.percentage} />
                            </Box>
                        )}
                        
                        <Button 
                            type="submit" 
                            variant="contained" 
                            color="primary" 
                            sx={{ mt: 3, display: 'block', width: '100%' }}
                            disabled={processing}
                        >
                            {processing ? "Mengunggah..." : "Upload File"}
                        </Button>
                    </Box>
                </CardContent>
            </Card>

            {/* Tampilkan Tabel File */}
            <FilesTable
                files={files}
                fileNamePrefix={fileNamePrefix}
                tahun={tahun}
                idSatker={idSatker}
                showTriwulan={showTriwulanSelect}
            />
        </Box>
    );
}