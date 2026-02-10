// resources/js/Components/Perencanaan/FileUploadSection.jsx
import React, {useState} from 'react';
import { useForm, Link, usePage } from '@inertiajs/react';
import { 
    Box, Card, CardContent, CardHeader, Button, Alert, LinearProgress, Typography, Paper, 
    TableContainer, Table, TableHead, TableBody, TableRow, TableCell, IconButton, Collapse
} from '@mui/material';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

export default function FileUploadSection({
    title, description, uploadRoute, fileInputName, files, 
    flashMessage, tahun, idSatker, fileNamePrefix, 
    deleteRoutePrefix, onEditClick 
}) {
    
    const { data, setData, post, processing, errors, progress, reset } = useForm({
        [fileInputName]: null,
    });

    const [showFlash, setShowFlash] = useState(true);

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
            <Typography variant="h5" sx={{ mb: 1, fontWeight: 'bold' }}>{title}</Typography>
            <Paper sx={{ p: 2, backgroundColor: '#f1e022', color: 'black', mb: 2 }}>
                <Typography>{description}</Typography>
            </Paper>

            <Collapse in={showFlash && !!flashMessage}>
                <Alert severity="success" onClose={() => setShowFlash(false)} sx={{ mb: 2 }}>
                    {flashMessage}
                </Alert>
            </Collapse>

            <Card component={Paper} elevation={2} sx={{ mb: 3 }}>
                <CardHeader 
                    title={`Upload File ${fileNamePrefix}`}
                    sx={{ backgroundColor: '#e6bf3e', color: 'white' }}
                />
                <CardContent>
                    <Box component="form" onSubmit={handleSubmit}>
                        <Button
                            variant="outlined"
                            component="label"
                            fullWidth
                            sx={{ mt: 2 }}
                        >
                            Pilih File PDF (Max: 2MB)
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

                        {progress && <LinearProgress variant="determinate" value={progress.percentage} sx={{ mt: 2 }} />}
                        
                        <Button 
                            type="submit" 
                            variant="contained" 
                            color="primary" 
                            sx={{ mt: 3 }}
                            disabled={processing}
                        >
                            {processing ? "Mengunggah..." : "Upload File"}
                        </Button>
                    </Box>
                </CardContent>
            </Card>

            {/* Tabel Data */}
            <TableContainer component={Paper} elevation={2}>
                <Table>
                    <TableHead sx={{ backgroundColor: '#f0bb49' }}>
                        <TableRow>
                            <TableCell>No</TableCell>
                            <TableCell>File {fileNamePrefix}</TableCell>
                            <TableCell>Versi</TableCell>
                            <TableCell>Tanggal Upload</TableCell>
                            <TableCell>Aksi</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {files.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} align="center">Belum ada file.</TableCell>
                            </TableRow>
                        ) : (
                            files.map((file, index) => (
                                <TableRow key={file.id} hover>
                                    <TableCell>{index + 1}</TableCell>
                                    <TableCell>
                                        <a href={`/uploads/repository/${idSatker}/${file.id_filename}`} target="_blank" rel="noopener noreferrer">
                                            {fileNamePrefix} Tahun {file.id_periode}
                                        </a>
                                    </TableCell>
                                    <TableCell>{file.id_perubahan}</TableCell>
                                    <TableCell>{file.id_tglupload}</TableCell>
                                    <TableCell>
                                        {/* <IconButton color="warning" size="small" onClick={() => onEditClick(file)}>
                                            <EditIcon />
                                        </IconButton> */}
                                        <IconButton
                                            component={Link}
                                            href={`${deleteRoutePrefix}/${file.id}`}
                                            method="delete"
                                            as="button"
                                            onBefore={() => confirm('Apakah Anda yakin ingin menghapus file ini?')}
                                            preserveScroll
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
        </Box>
    );
}