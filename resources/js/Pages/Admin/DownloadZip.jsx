import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Container, Card, CardHeader, CardContent, Button, Typography, 
    Box, Autocomplete, TextField, Alert, CircularProgress 
} from '@mui/material';
import CloudDownloadIcon from '@mui/icons-material/CloudDownload';
import BusinessIcon from '@mui/icons-material/Business';

export default function DownloadZip({ kejatiList }) {
    const [selectedKejati, setSelectedKejati] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleDownload = () => {
        if (!selectedKejati) return;

        setLoading(true);
        setError(null);

        // Gunakan fetch/axios untuk trigger download atau window.location
        // window.location lebih aman untuk file download stream
        const url = route('download.kejati', selectedKejati.id);
        
        // Trik untuk mendeteksi download selesai agak tricky dengan window.location
        // Kita gunakan timeout sederhana atau iframe hidden, tapi window.location paling stabil
        window.location.href = url;

        // Reset loading setelah beberapa detik (asumsi request terkirim)
        setTimeout(() => {
            setLoading(false);
        }, 3000);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Admin - Download ZIP" />

            <Container maxWidth="md" sx={{ mt: 8 }}>
                <Card elevation={4} sx={{ borderRadius: 3 }}>
                    <CardHeader 
                        title="Download Arsip Kejati"
                        subheader="Fitur Khusus Admin Pusat"
                        sx={{ 
                            backgroundColor: '#2c3e50', 
                            color: 'white',
                            '& .MuiCardHeader-subheader': { color: '#cfd8dc' }
                        }}
                    />
                    
                    <CardContent sx={{ p: 4 }}>
                        <Box sx={{ mb: 4, textAlign: 'center' }}>
                            <BusinessIcon sx={{ fontSize: 60, color: '#e6bf3e', mb: 2 }} />
                            <Typography variant="body1" color="text.secondary">
                                Pilih Kejaksaan Tinggi (Kejati) di bawah ini untuk mengunduh seluruh dokumen 
                                (Renstra, PK, LKJiP, dll) dari seluruh satuan kerja di wilayah tersebut dalam satu file ZIP.
                            </Typography>
                        </Box>

                        {error && <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>}

                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
                            
                            {/* SEARCHABLE DROPDOWN */}
                            <Autocomplete
                                options={kejatiList} // Data dari controller
                                getOptionLabel={(option) => option.label}
                                value={selectedKejati}
                                onChange={(event, newValue) => {
                                    setSelectedKejati(newValue);
                                }}
                                renderInput={(params) => (
                                    <TextField 
                                        {...params} 
                                        label="Cari Satuan Kerja / Kejati..." 
                                        variant="outlined"
                                        placeholder="Ketik nama kejati..."
                                    />
                                )}
                                noOptionsText="Satker tidak ditemukan"
                                isOptionEqualToValue={(option, value) => option.id === value.id}
                            />

                            {/* DOWNLOAD BUTTON */}
                            <Button
                                variant="contained"
                                size="large"
                                startIcon={loading ? <CircularProgress size={20} color="inherit" /> : <CloudDownloadIcon />}
                                onClick={handleDownload}
                                disabled={!selectedKejati || loading}
                                sx={{ 
                                    py: 1.5, 
                                    backgroundColor: '#e6bf3e', 
                                    fontSize: '1.1rem',
                                    fontWeight: 'bold',
                                    '&:hover': { backgroundColor: '#d4af37' }
                                }}
                            >
                                {loading ? 'Memproses ZIP...' : 'Download Dokumen (.ZIP)'}
                            </Button>

                            {selectedKejati && (
                                <Typography variant="caption" align="center" color="text.secondary">
                                    File ZIP akan berisi folder dokumen dari semua satker di bawah naungan: <br/>
                                    <b>{selectedKejati.label}</b>
                                </Typography>
                            )}
                        </Box>
                    </CardContent>
                </Card>
            </Container>
        </AuthenticatedLayout>
    );
}