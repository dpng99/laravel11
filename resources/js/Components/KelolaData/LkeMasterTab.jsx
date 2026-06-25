import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Alert, Box, Button, Card, CardContent, Checkbox, Dialog, DialogActions,
    DialogContent, DialogTitle, FormControl, FormControlLabel, Grid, InputLabel,
    MenuItem, Select, Stack, Typography,
} from '@mui/material';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import CalendarMonthIcon from '@mui/icons-material/CalendarMonth';
import MasterEditorTab from './MasterEditorTab';

const labels = {
    komponen: 'Komponen',
    subkomponen: 'Subkomponen',
    kriteria: 'Kriteria',
    buktidukung: 'Bukti Dukung',
    parameter: 'Parameter Nilai',
};

export default function LkeMasterTab({ data = {} }) {
    const currentYear = Number(data.year || new Date().getFullYear());
    const years = data.years || [currentYear];
    const availableYears = data.availableYears || [];
    const counts = data.counts || {};
    const [copyOpen, setCopyOpen] = useState(false);
    const [copyForm, setCopyForm] = useState({
        source_year: availableYears.find((year) => Number(year) !== currentYear) || availableYears[0] || 2025,
        target_year: currentYear,
        replace: false,
    });
    const total = Object.values(counts).reduce((sum, value) => sum + Number(value || 0), 0);

    const changeYear = (year) => {
        router.get(route('keloladata'), { tab: 'lke', lke_year: year }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };
    const copyYear = () => {
        router.post(route('keloladata.lke.copy-year'), copyForm, {
            preserveScroll: true,
            onSuccess: () => setCopyOpen(false),
        });
    };

    return (
        <Box>
            <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems={{ md: 'center' }} sx={{ mb: 3 }}>
                <Box sx={{ flexGrow: 1 }}>
                    <Typography variant="h5" fontWeight="bold">Master LKE Dinamis</Typography>
                    <Typography color="text.secondary">
                        Setiap tahun memiliki struktur, bukti dukung, dan parameter penilaian tersendiri.
                    </Typography>
                </Box>
                <FormControl size="small" sx={{ minWidth: 180 }}>
                    <InputLabel>Tahun Master LKE</InputLabel>
                    <Select value={currentYear} label="Tahun Master LKE" onChange={(event) => changeYear(event.target.value)}>
                        {years.map((year) => <MenuItem key={year} value={Number(year)}>{year}</MenuItem>)}
                    </Select>
                </FormControl>
                <Button variant="contained" startIcon={<ContentCopyIcon />} onClick={() => setCopyOpen(true)}>
                    Salin dari Tahun Lain
                </Button>
            </Stack>

            <Grid container spacing={1.5} sx={{ mb: 3 }}>
                {Object.entries(labels).map(([key, label]) => (
                    <Grid item xs={6} md key={key}>
                        <Card variant="outlined">
                            <CardContent sx={{ py: 1.5, '&:last-child': { pb: 1.5 } }}>
                                <Typography variant="caption" color="text.secondary">{label}</Typography>
                                <Typography variant="h5" fontWeight="bold">{counts[key] || 0}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            {total === 0 ? (
                <Alert severity="info" icon={<CalendarMonthIcon />} sx={{ mb: 2 }}>
                    Master LKE tahun <strong>{currentYear}</strong> masih kosong. Salin dari tahun 2025 atau mulai menambahkan struktur baru.
                </Alert>
            ) : (
                <Alert severity="success" sx={{ mb: 2 }}>
                    Anda sedang mengelola Master LKE tahun <strong>{currentYear}</strong>. Perubahan tidak memengaruhi tahun lainnya.
                </Alert>
            )}

            <MasterEditorTab tabs={data.tabs || []} year={currentYear} />

            <Dialog open={copyOpen} onClose={() => setCopyOpen(false)} maxWidth="sm" fullWidth>
                <DialogTitle>Salin Master LKE Antar Tahun</DialogTitle>
                <DialogContent>
                    <Alert severity="info" sx={{ mb: 2 }}>
                        Seluruh komponen, subkomponen, kriteria, daftar bukti dukung, relasi, dan parameter nilai akan disalin.
                    </Alert>
                    <Stack spacing={2}>
                        <FormControl fullWidth>
                            <InputLabel>Tahun Sumber</InputLabel>
                            <Select value={copyForm.source_year} label="Tahun Sumber" onChange={(event) => setCopyForm({ ...copyForm, source_year: Number(event.target.value) })}>
                                {availableYears.filter((year) => Number(year) !== Number(copyForm.target_year)).map((year) => (
                                    <MenuItem key={year} value={Number(year)}>{year}</MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                        <FormControl fullWidth>
                            <InputLabel>Tahun Tujuan</InputLabel>
                            <Select value={copyForm.target_year} label="Tahun Tujuan" onChange={(event) => setCopyForm({ ...copyForm, target_year: Number(event.target.value) })}>
                                {Array.from({ length: 11 }, (_, index) => 2024 + index).map((year) => (
                                    <MenuItem key={year} value={year}>{year}</MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                        <FormControlLabel
                            control={<Checkbox checked={copyForm.replace} onChange={(event) => setCopyForm({ ...copyForm, replace: event.target.checked })} />}
                            label="Timpa seluruh master yang sudah ada pada tahun tujuan"
                        />
                        {copyForm.replace && <Alert severity="warning">Data master tahun tujuan akan dihapus dan diganti oleh salinan tahun sumber.</Alert>}
                    </Stack>
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setCopyOpen(false)}>Batal</Button>
                    <Button variant="contained" startIcon={<ContentCopyIcon />} onClick={copyYear}>Salin Master</Button>
                </DialogActions>
            </Dialog>
        </Box>
    );
}
