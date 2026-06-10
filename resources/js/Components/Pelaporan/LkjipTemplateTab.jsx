import React, { useState } from 'react';
import axios from 'axios';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    CircularProgress,
    FormControl,
    InputLabel,
    MenuItem,
    Select,
    Typography,
} from '@mui/material';
import DescriptionIcon from '@mui/icons-material/Description';

function downloadFilename(contentDisposition, fallback) {
    const match = contentDisposition?.match(/filename\*?=(?:UTF-8''|"?)([^";]+)/i);
    return match?.[1] || fallback;
}

export default function LkjipTemplateTab({ tahun }) {
    const [triwulan, setTriwulan] = useState('TW 1');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');

    const exportWord = async () => {
        setProcessing(true);
        setError('');

        try {
            const response = await axios.post('/pelaporan/template-lkjip/export-word', {
                triwulan,
                tahun: Number(tahun),
            }, {
                responseType: 'blob',
            });

            const blobUrl = window.URL.createObjectURL(response.data);
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = downloadFilename(
                response.headers['content-disposition'],
                `Template_LKJiP_${tahun}_${triwulan.replace(' ', '_')}.docx`
            );
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(blobUrl);
        } catch (requestError) {
            let message = 'Template LKJiP gagal dibuat. Silakan coba kembali.';

            if (requestError.response?.data instanceof Blob) {
                try {
                    const payload = JSON.parse(await requestError.response.data.text());
                    message = payload.message || message;
                } catch {
                    // Gunakan pesan umum ketika respons bukan JSON.
                }
            }

            setError(message);
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Box>
            <Typography variant="h5" sx={{ mb: 1, fontWeight: 'bold' }}>
                Unduh Template LKJiP
            </Typography>
            <Alert severity="info" sx={{ mb: 3 }}>
                Nama SS, IKSS, target, capaian, parameter pengampu, tabel, serta formula
                diambil otomatis dari data Pelaporan. Pilih triwulan untuk mengunduh dokumen Word.
            </Alert>

            {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

            <Card variant="outlined" sx={{ mb: 3 }}>
                <CardHeader
                    title={`Template LKJiP Tahun ${tahun}`}
                    titleTypographyProps={{ variant: 'subtitle1', fontWeight: 'bold' }}
                    sx={{ backgroundColor: '#f8f9fa', borderBottom: '1px solid #eee' }}
                />
                <CardContent>
                    <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                        Dokumen akan memuat seluruh IKSS yang telah memiliki data capaian pada triwulan terpilih,
                        lengkap dengan nilai capaian terhadap target, status, dan narasi otomatis.
                    </Typography>

                    <FormControl fullWidth size="small" sx={{ mb: 2 }}>
                        <InputLabel>Triwulan</InputLabel>
                        <Select
                            value={triwulan}
                            label="Triwulan"
                            onChange={(event) => setTriwulan(event.target.value)}
                        >
                            <MenuItem value="TW 1">Triwulan 1</MenuItem>
                            <MenuItem value="TW 2">Triwulan 2</MenuItem>
                            <MenuItem value="TW 3">Triwulan 3</MenuItem>
                            <MenuItem value="TW 4">Triwulan 4</MenuItem>
                        </Select>
                    </FormControl>

                    <Box>
                        <Button
                            variant="contained"
                            startIcon={processing ? <CircularProgress size={18} color="inherit" /> : <DescriptionIcon />}
                            disabled={processing}
                            onClick={exportWord}
                        >
                            {processing ? 'Membuat Template...' : 'Unduh Template Word'}
                        </Button>
                    </Box>
                </CardContent>
            </Card>

            <Alert severity="warning">
                Pastikan Capaian Kinerja dan Parameter Pengampu IKSS sudah dilengkapi sebelum mengunduh template.
            </Alert>
        </Box>
    );
}
