import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DataGrid } from '@mui/x-data-grid';
import {
    Alert,
    Box,
    Button,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    IconButton,
    LinearProgress,
    Paper,
    Stack,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import DownloadIcon from '@mui/icons-material/Download';
import RefreshIcon from '@mui/icons-material/Refresh';
import RestartAltIcon from '@mui/icons-material/RestartAlt';
import SearchIcon from '@mui/icons-material/Search';
import VisibilityIcon from '@mui/icons-material/Visibility';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';

function parsePercent(value) {
    if (typeof value !== 'string' || !value.includes('%')) {
        return null;
    }

    const parsed = Number.parseFloat(value.replace('%', ''));
    return Number.isNaN(parsed) ? null : parsed;
}

function getErrorMessage(error, fallback = 'Terjadi kendala pada layanan SPIP.') {
    return error?.response?.data?.pesan || error?.response?.data?.message || fallback;
}

function statusColor(status) {
    if (status === 'Selesai') {
        return 'success';
    }

    if (status === 'Aktif') {
        return 'primary';
    }

    if (status === 'Menunggu Approve PK') {
        return 'warning';
    }

    return 'default';
}

function ProgressCell({ value }) {
    const numeric = parsePercent(value);

    if (numeric === null) {
        return <Chip size="small" label={value || '-'} />;
    }

    return (
        <Stack direction="row" spacing={1} alignItems="center" sx={{ width: '100%' }}>
            <LinearProgress
                variant="determinate"
                value={Math.min(numeric, 100)}
                color={numeric >= 100 ? 'success' : numeric >= 60 ? 'warning' : 'error'}
                sx={{ flex: 1, height: 8, borderRadius: 8 }}
            />
            <Typography variant="caption" fontWeight="bold" sx={{ width: 52, textAlign: 'right' }}>
                {value}
            </Typography>
        </Stack>
    );
}

function Metric({ title, value, caption, color = 'primary' }) {
    return (
        <Paper variant="outlined" sx={{ p: 2, height: '100%' }}>
            <Typography variant="caption" color="text.secondary">{title}</Typography>
            <Typography variant="h4" fontWeight="bold" color={`${color}.main`}>{value}</Typography>
            <Typography variant="body2" color="text.secondary">{caption}</Typography>
        </Paper>
    );
}

export default function AdminSpip({ tahun }) {
    const [rows, setRows] = useState([]);
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [search, setSearch] = useState('');
    const [selectedMissing, setSelectedMissing] = useState(null);

    const [intipOpen, setIntipOpen] = useState(false);
    const [intipUser, setIntipUser] = useState(null);
    const [intipRows, setIntipRows] = useState([]);
    const [intipLoading, setIntipLoading] = useState(false);

    const filteredRows = useMemo(() => {
        const keyword = search.trim().toLowerCase();

        if (!keyword) {
            return rows;
        }

        return rows.filter((row) => `${row.nama_satker} ${row.user_id} ${row.status_pk}`.toLowerCase().includes(keyword));
    }, [rows, search]);

    const metrics = useMemo(() => {
        const waiting = rows.filter((row) => row.status_pk === 'Menunggu Approve PK').length;
        const active = rows.filter((row) => row.status_pk === 'Aktif').length;
        const done = rows.filter((row) => row.status_pk === 'Selesai').length;
        const missing = rows.filter((row) => (row.missing_links || []).length > 0).length;

        return { waiting, active, done, missing };
    }, [rows]);

    const loadDashboard = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/admin/spip/dashboard');
            setRows(response.data.data || []);
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Gagal memuat dashboard SPIP.') });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadDashboard();
    }, []);

    const runAction = async (url, confirmText) => {
        if (confirmText && typeof window !== 'undefined' && !window.confirm(confirmText)) {
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post(url);
            setMessage({ severity: 'success', text: response.data.pesan || 'Aksi berhasil dijalankan.' });
            await loadDashboard();
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Aksi SPIP gagal dijalankan.') });
        } finally {
            setLoading(false);
        }
    };

    const openIntip = async (row) => {
        setIntipUser(row);
        setIntipRows([]);
        setIntipOpen(true);
        setIntipLoading(true);

        try {
            const response = await axios.get(`/admin/spip/intip/${encodeURIComponent(row.user_id)}`);
            setIntipRows(response.data.data || []);
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Gagal membuka kertas kerja satker.') });
            setIntipOpen(false);
        } finally {
            setIntipLoading(false);
        }
    };

    const handleDownload = async (row) => {
        setLoading(true);
        try {
            const response = await axios.get('/admin/spip/download', { params: { user_id: row.user_id } });
            window.open(response.data.url, '_blank', 'noopener,noreferrer');
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Tautan download belum tersedia.') });
        } finally {
            setLoading(false);
        }
    };

    const columns = [
        { field: 'no', headerName: 'No', width: 70 },
        {
            field: 'nama_satker',
            headerName: 'Satker',
            minWidth: 260,
            flex: 1,
            renderCell: ({ row }) => (
                <Box sx={{ py: 1 }}>
                    <Typography variant="body2" fontWeight="bold" sx={{ whiteSpace: 'normal', lineHeight: 1.4 }}>{row.nama_satker}</Typography>
                    <Typography variant="caption" color="text.secondary">
                        {row.id_satker ? `ID Satker: ${row.id_satker}` : row.user_id}
                    </Typography>
                </Box>
            ),
        },
        { field: 'progress_pm', headerName: 'Progress PM', minWidth: 210, flex: 0.7, renderCell: ({ value }) => <ProgressCell value={value} /> },
        { field: 'progress_pk', headerName: 'Progress PK', minWidth: 210, flex: 0.7, renderCell: ({ value }) => <ProgressCell value={value} /> },
        {
            field: 'status_pk',
            headerName: 'Status PK',
            width: 175,
            renderCell: ({ value }) => <Chip size="small" color={statusColor(value)} label={value || '-'} />,
        },
        {
            field: 'missing_links',
            headerName: 'Link',
            width: 110,
            sortable: false,
            renderCell: ({ row }) => {
                const count = (row.missing_links || []).length;

                if (!count) {
                    return <Chip size="small" color="success" label="Lengkap" />;
                }

                return (
                    <Tooltip title="Lihat uraian tanpa tautan">
                        <Button size="small" color="warning" startIcon={<WarningAmberIcon />} onClick={() => setSelectedMissing(row)}>
                            {count}
                        </Button>
                    </Tooltip>
                );
            },
        },
        {
            field: 'aksi',
            headerName: 'Aksi',
            width: 165,
            sortable: false,
            filterable: false,
            renderCell: ({ row }) => (
                <Stack direction="row" spacing={0.25}>
                    <Tooltip title="Intip kertas kerja">
                        <IconButton size="small" color="primary" onClick={() => openIntip(row)}>
                            <VisibilityIcon fontSize="small" />
                        </IconButton>
                    </Tooltip>
                    {row.status_pk === 'Menunggu Approve PK' && (
                        <Tooltip title="Approve PM dan aktifkan PK">
                            <IconButton
                                size="small"
                                color="success"
                                onClick={() => runAction(`/admin/spip/approve/${encodeURIComponent(row.user_id)}`, `Setujui PM dan aktifkan PK untuk ${row.nama_satker}?`)}
                            >
                                <CheckCircleIcon fontSize="small" />
                            </IconButton>
                        </Tooltip>
                    )}
                    <Tooltip title="Reset status ke Tidak Aktif">
                        <IconButton
                            size="small"
                            color="warning"
                            onClick={() => runAction(`/admin/spip/reset-status/${encodeURIComponent(row.user_id)}`, `Reset status SPIP untuk ${row.nama_satker}?`)}
                        >
                            <RestartAltIcon fontSize="small" />
                        </IconButton>
                    </Tooltip>
                    <Tooltip title="Download kertas kerja">
                        <IconButton size="small" color="primary" onClick={() => handleDownload(row)}>
                            <DownloadIcon fontSize="small" />
                        </IconButton>
                    </Tooltip>
                </Stack>
            ),
        },
    ];

    const intipColumns = [
        { field: 'kodeSubUnsur', headerName: 'Kode', width: 110 },
        { field: 'hurufGrade', headerName: 'Grade', width: 80, renderCell: ({ value }) => <Chip size="small" label={value} /> },
        {
            field: 'namaParameter',
            headerName: 'Parameter',
            minWidth: 280,
            flex: 1,
            renderCell: ({ value }) => (
                <Typography variant="body2" sx={{ py: 1, whiteSpace: 'normal', lineHeight: 1.4 }}>{value || '-'}</Typography>
            ),
        },
        {
            field: 'uraianF',
            headerName: 'Uraian',
            minWidth: 300,
            flex: 1.1,
            renderCell: ({ value }) => (
                <Typography variant="body2" sx={{ py: 1, whiteSpace: 'normal', lineHeight: 1.4 }}>{value || '-'}</Typography>
            ),
        },
        { field: 'gradePM', headerName: 'PM', width: 80 },
        { field: 'gradePK', headerName: 'PK', width: 80 },
        {
            field: 'aoiJ',
            headerName: 'AoI',
            minWidth: 240,
            flex: 0.8,
            renderCell: ({ value }) => (
                <Typography variant="body2" sx={{ py: 1, whiteSpace: 'normal', lineHeight: 1.4 }}>{value || '-'}</Typography>
            ),
        },
        {
            field: 'sebabL',
            headerName: 'Penyebab',
            minWidth: 240,
            flex: 0.8,
            renderCell: ({ value }) => (
                <Typography variant="body2" sx={{ py: 1, whiteSpace: 'normal', lineHeight: 1.4 }}>{value || '-'}</Typography>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Admin SPIP" />
            <Stack spacing={2.5}>
                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} justifyContent="space-between" alignItems={{ xs: 'flex-start', md: 'center' }}>
                    <Box>
                        <Typography variant="h5" fontWeight="bold">Admin SPIP {tahun}</Typography>
                        <Typography variant="body2" color="text.secondary">
                            Monitoring pengisian PM, persetujuan PK, dan kertas kerja SPIP satker.
                        </Typography>
                    </Box>
                    <Button variant="outlined" startIcon={<RefreshIcon />} onClick={loadDashboard} disabled={loading}>
                        Muat Ulang
                    </Button>
                </Stack>

                {message && <Alert severity={message.severity} onClose={() => setMessage(null)}>{message.text}</Alert>}

                <Grid container spacing={2}>
                    <Grid item xs={12} sm={6} lg={3}><Metric title="Total Satker" value={rows.length} caption="Satker pada database SPIP" /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><Metric title="Menunggu Approve" value={metrics.waiting} caption="PM siap disetujui admin" color="warning" /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><Metric title="PK Aktif" value={metrics.active} caption="Satker sedang tahapan PK" color="primary" /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><Metric title="Perlu Cek Link" value={metrics.missing} caption="Uraian berisi teks tanpa tautan" color="error" /></Grid>
                </Grid>

                <Paper variant="outlined" sx={{ p: 2 }}>
                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5} justifyContent="space-between" sx={{ mb: 2 }}>
                        <TextField
                            label="Cari satker"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            size="small"
                            sx={{ minWidth: { md: 380 } }}
                            InputProps={{ startAdornment: <SearchIcon fontSize="small" sx={{ mr: 1, color: 'text.secondary' }} /> }}
                        />
                    </Stack>
                    <DataGrid
                        autoHeight
                        rows={filteredRows}
                        columns={columns}
                        getRowId={(row) => row.user_id}
                        loading={loading}
                        disableRowSelectionOnClick
                        getRowHeight={() => 'auto'}
                        pageSizeOptions={[10, 25, 50]}
                        initialState={{ pagination: { paginationModel: { pageSize: 10 } } }}
                        sx={{
                            '& .MuiDataGrid-cell': { alignItems: 'flex-start', py: 0.75 },
                            '& .MuiDataGrid-columnHeaderTitle': { fontWeight: 'bold' },
                        }}
                    />
                </Paper>
            </Stack>

            <Dialog open={Boolean(selectedMissing)} onClose={() => setSelectedMissing(null)} maxWidth="sm" fullWidth>
                <DialogTitle>Uraian Tanpa Tautan</DialogTitle>
                <DialogContent dividers>
                    <Typography variant="subtitle2" fontWeight="bold" gutterBottom>{selectedMissing?.nama_satker}</Typography>
                    <Stack spacing={1}>
                        {(selectedMissing?.missing_links || []).map((item) => (
                            <Alert key={item} severity="warning">{item}</Alert>
                        ))}
                    </Stack>
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setSelectedMissing(null)}>Tutup</Button>
                </DialogActions>
            </Dialog>

            <Dialog open={intipOpen} onClose={() => setIntipOpen(false)} maxWidth="xl" fullWidth>
                <DialogTitle>
                    <Typography variant="h6" fontWeight="bold">Kertas Kerja SPIP</Typography>
                    <Typography variant="body2" color="text.secondary">{intipUser?.nama_satker} - {intipUser?.user_id}</Typography>
                </DialogTitle>
                <DialogContent dividers>
                    {intipLoading ? (
                        <LinearProgress />
                    ) : (
                        <DataGrid
                            autoHeight
                            rows={intipRows}
                            columns={intipColumns}
                            getRowId={(row) => `${row.kodeSubUnsur}-${row.hurufGrade}`}
                            disableRowSelectionOnClick
                            getRowHeight={() => 'auto'}
                            pageSizeOptions={[10, 25, 50]}
                            initialState={{ pagination: { paginationModel: { pageSize: 10 } } }}
                            sx={{
                                '& .MuiDataGrid-cell': { alignItems: 'flex-start', py: 0.75 },
                                '& .MuiDataGrid-columnHeaderTitle': { fontWeight: 'bold' },
                            }}
                        />
                    )}
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setIntipOpen(false)}>Tutup</Button>
                </DialogActions>
            </Dialog>
        </AuthenticatedLayout>
    );
}
