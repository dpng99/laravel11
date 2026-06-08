import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Alert,
    Autocomplete,
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Chip,
    CircularProgress,
    Divider,
    FormControl,
    InputLabel,
    LinearProgress,
    MenuItem,
    Paper,
    Select,
    Stack,
    Tab,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Tabs,
    TextField,
    Typography,
} from '@mui/material';

const statusChip = (status) => {
    if (status === 'success' || status === 'ada') {
        return <Chip size="small" color="success" label="Ada" />;
    }

    if (status === 'failed' || status === 'hilang') {
        return <Chip size="small" color="error" label={status === 'hilang' ? 'Hilang' : 'Gagal'} />;
    }

    return <Chip size="small" color="warning" label="Proses" />;
};

function SummaryCard({ label, value, color = 'primary' }) {
    return (
        <Paper variant="outlined" sx={{ p: 2, minWidth: 160, flex: 1 }}>
            <Typography variant="caption" color="text.secondary">{label}</Typography>
            <Typography variant="h5" color={`${color}.main`} fontWeight="bold">{value}</Typography>
        </Paper>
    );
}

function ApiCheckTab() {
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState('');

    const runCheck = async () => {
        setLoading(true);
        setError('');

        try {
            const response = await axios.get('/diagnostik/api-check');
            setResult(response.data);
        } catch (exception) {
            setError(exception.response?.data?.message || exception.message || 'Gagal menjalankan pengecekan API.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Stack spacing={2}>
            <Alert severity="info">
                Cek ini memvalidasi konfigurasi, token Google API, akses baca, tulis, dan hapus pada Google Drive.
            </Alert>

            <Box>
                <Button variant="contained" onClick={runCheck} disabled={loading}>
                    {loading ? 'Mengecek...' : 'Jalankan Cek API'}
                </Button>
            </Box>

            {loading && <LinearProgress />}
            {error && <Alert severity="error">{error}</Alert>}

            {result && (
                <Stack spacing={1.5}>
                    <Alert severity={result.status === 'success' ? 'success' : 'error'}>
                        Status akhir: <strong>{result.status === 'success' ? 'Semua pengecekan berhasil' : 'Ada pengecekan yang gagal'}</strong>
                    </Alert>

                    {result.steps.map((step) => (
                        <Paper key={step.name} variant="outlined" sx={{ p: 2 }}>
                            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5} alignItems={{ xs: 'flex-start', md: 'center' }}>
                                {statusChip(step.status)}
                                <Box sx={{ flex: 1 }}>
                                    <Typography fontWeight="bold">{step.name}</Typography>
                                    <Typography variant="body2" color="text.secondary">{step.message}</Typography>
                                    {step.meta && (
                                        <Typography variant="caption" color="text.secondary">
                                            {Object.entries(step.meta).map(([key, value]) => `${key}: ${value}`).join(' • ')}
                                        </Typography>
                                    )}
                                </Box>
                            </Stack>
                        </Paper>
                    ))}
                </Stack>
            )}
        </Stack>
    );
}

function DocumentCheckTab({ initialSatkers, currentYear }) {
    const [satkerOptions, setSatkerOptions] = useState(initialSatkers || []);
    const [satkerQuery, setSatkerQuery] = useState('');
    const [selectedSatker, setSelectedSatker] = useState(null);
    const [tahun, setTahun] = useState(String(currentYear || new Date().getFullYear()));
    const [loadingSatkers, setLoadingSatkers] = useState(false);
    const [loadingCheck, setLoadingCheck] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    useEffect(() => {
        const keyword = satkerQuery.trim();
        if (keyword.length < 2) {
            return undefined;
        }

        const timeout = setTimeout(async () => {
            setLoadingSatkers(true);

            try {
                const response = await axios.get('/diagnostik/satkers', { params: { q: keyword } });
                setSatkerOptions(response.data);
            } finally {
                setLoadingSatkers(false);
            }
        }, 350);

        return () => clearTimeout(timeout);
    }, [satkerQuery]);

    const rows = result?.rows || [];
    const filteredRows = useMemo(() => {
        if (statusFilter === 'all') {
            return rows;
        }

        return rows.filter((row) => row.status === statusFilter);
    }, [rows, statusFilter]);

    const runCheck = async () => {
        if (!selectedSatker) {
            setError('Pilih satker terlebih dahulu.');
            return;
        }

        setLoadingCheck(true);
        setError('');
        setResult(null);

        try {
            const response = await axios.get('/diagnostik/document-check', {
                params: {
                    id_satker: selectedSatker.id_satker,
                    tahun,
                },
            });
            setResult(response.data);
            setStatusFilter('all');
        } catch (exception) {
            setError(exception.response?.data?.message || exception.message || 'Gagal menjalankan pengecekan dokumen.');
        } finally {
            setLoadingCheck(false);
        }
    };

    return (
        <Stack spacing={2}>
            <Alert severity="info">
                Pengecekan dokumen berjalan async dari browser: database dibaca, folder Google Drive satker dipindai, lalu nama file dicocokkan.
            </Alert>

            <Paper variant="outlined" sx={{ p: 2 }}>
                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems="center">
                    <Autocomplete
                        fullWidth
                        options={satkerOptions}
                        value={selectedSatker}
                        loading={loadingSatkers}
                        onInputChange={(event, value) => setSatkerQuery(value)}
                        onChange={(event, value) => setSelectedSatker(value)}
                        getOptionLabel={(option) => option ? `${option.id_satker} - ${option.satkernama}` : ''}
                        isOptionEqualToValue={(option, value) => option.id_satker === value.id_satker}
                        renderInput={(params) => (
                            <TextField
                                {...params}
                                label="Cari/Pilih Satker"
                                InputProps={{
                                    ...params.InputProps,
                                    endAdornment: (
                                        <>
                                            {loadingSatkers ? <CircularProgress color="inherit" size={20} /> : null}
                                            {params.InputProps.endAdornment}
                                        </>
                                    ),
                                }}
                            />
                        )}
                    />
                    <TextField
                        label="Tahun"
                        value={tahun}
                        onChange={(event) => setTahun(event.target.value)}
                        sx={{ width: { xs: '100%', md: 140 } }}
                    />
                    <Button variant="contained" onClick={runCheck} disabled={loadingCheck} sx={{ minWidth: 180, height: 56 }}>
                        {loadingCheck ? 'Mengecek...' : 'Cek Dokumen'}
                    </Button>
                </Stack>
            </Paper>

            {loadingCheck && <LinearProgress />}
            {error && <Alert severity="error">{error}</Alert>}

            {result && (
                <Stack spacing={2}>
                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5}>
                        <SummaryCard label="Data DB" value={result.summary.total_database} />
                        <SummaryCard label="Ada di Drive" value={result.summary.ada_di_drive} color="success" />
                        <SummaryCard label="Tidak Ada di Drive" value={result.summary.tidak_ada_di_drive} color="error" />
                        <SummaryCard label="File Drive Terbaca" value={result.summary.file_drive_terbaca} color="secondary" />
                    </Stack>

                    <Paper variant="outlined" sx={{ p: 2 }}>
                        <Typography fontWeight="bold">
                            {result.satker ? `${result.satker.id_satker} - ${result.satker.satkernama}` : 'Satker tidak ditemukan'}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Tahun: {result.tahun} • Folder dicek: {result.summary.folder_dicek}
                        </Typography>
                        <Stack direction={{ xs: 'column', md: 'row' }} spacing={1} sx={{ mt: 1 }}>
                            {result.folders.map((folder) => (
                                <Chip
                                    key={folder.folder}
                                    size="small"
                                    color={folder.status === 'success' ? 'success' : 'error'}
                                    label={`${folder.folder} (${folder.count})`}
                                />
                            ))}
                        </Stack>
                    </Paper>

                    {result.skipped_sources?.length > 0 && (
                        <Alert severity="warning">
                            {result.skipped_sources.length} sumber dilewati karena tabel/kolom tidak tersedia.
                        </Alert>
                    )}

                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems={{ xs: 'stretch', md: 'center' }}>
                        <FormControl size="small" sx={{ minWidth: 220 }}>
                            <InputLabel>Filter Status</InputLabel>
                            <Select value={statusFilter} label="Filter Status" onChange={(event) => setStatusFilter(event.target.value)}>
                                <MenuItem value="all">Semua</MenuItem>
                                <MenuItem value="ada">Ada di Drive</MenuItem>
                                <MenuItem value="hilang">Tidak Ada di Drive</MenuItem>
                            </Select>
                        </FormControl>
                        <Typography variant="body2" color="text.secondary">
                            Menampilkan {filteredRows.length} dari {rows.length} data database.
                        </Typography>
                    </Stack>

                    <TableContainer component={Paper} variant="outlined">
                        <Table size="small">
                            <TableHead>
                                <TableRow>
                                    <TableCell>Dokumen</TableCell>
                                    <TableCell>File Database</TableCell>
                                    <TableCell>Periode</TableCell>
                                    <TableCell>Triwulan</TableCell>
                                    <TableCell>Versi</TableCell>
                                    <TableCell>Status</TableCell>
                                    <TableCell>Path Drive</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {filteredRows.length > 0 ? filteredRows.map((row, index) => (
                                    <TableRow key={`${row.table}-${row.row_id}-${row.filename}-${index}`} hover>
                                        <TableCell>
                                            <Typography variant="body2" fontWeight="bold">{row.document}</Typography>
                                            <Typography variant="caption" color="text.secondary">{row.table}</Typography>
                                        </TableCell>
                                        <TableCell sx={{ maxWidth: 320, wordBreak: 'break-all' }}>{row.filename}</TableCell>
                                        <TableCell>{row.period || '-'}</TableCell>
                                        <TableCell>{row.triwulan || '-'}</TableCell>
                                        <TableCell>{row.version ?? '-'}</TableCell>
                                        <TableCell>{statusChip(row.status)}</TableCell>
                                        <TableCell sx={{ maxWidth: 360, wordBreak: 'break-all' }}>{row.matched_path || '-'}</TableCell>
                                    </TableRow>
                                )) : (
                                    <TableRow>
                                        <TableCell colSpan={7} align="center">
                                            Tidak ada data pada filter ini.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                </Stack>
            )}
        </Stack>
    );
}

export default function SystemCheck() {
    const { tahun, satkers } = usePage().props;
    const [activeTab, setActiveTab] = useState(0);

    return (
        <AuthenticatedLayout>
            <Head title="Pengecekan Sistem" />

            <Card elevation={3}>
                <CardHeader
                    title="Pengecekan Sistem"
                    subheader="Cek API Google Drive dan kecocokan dokumen database dengan file Google Drive"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    subheaderTypographyProps={{ align: 'center' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white', '& .MuiCardHeader-subheader': { color: 'white' } }}
                />
                <CardContent>
                    <Tabs value={activeTab} onChange={(event, value) => setActiveTab(value)} sx={{ mb: 2 }}>
                        <Tab label="Cek API" />
                        <Tab label="Cek Dokumen per Satker" />
                    </Tabs>
                    <Divider sx={{ mb: 2 }} />

                    {activeTab === 0 && <ApiCheckTab />}
                    {activeTab === 1 && <DocumentCheckTab initialSatkers={satkers} currentYear={tahun} />}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
