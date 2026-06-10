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
    Divider,
    FormControl,
    FormControlLabel,
    Grid,
    IconButton,
    InputLabel,
    LinearProgress,
    MenuItem,
    Paper,
    Radio,
    RadioGroup,
    Select,
    Stack,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import DownloadIcon from '@mui/icons-material/Download';
import SaveIcon from '@mui/icons-material/Save';
import SearchIcon from '@mui/icons-material/Search';
import SendIcon from '@mui/icons-material/Send';
import VisibilityIcon from '@mui/icons-material/Visibility';

const TAHAPAN_PM = 'Penilaian Mandiri';
const TAHAPAN_PK = 'Penjaminan Kualitas';
const GRADES = ['A', 'B', 'C', 'D', 'E'];

function gradeColor(value) {
    if (!value || value === '-') {
        return 'default';
    }

    return value === 'A' || value === 'B' ? 'success' : value === 'C' ? 'warning' : 'error';
}

function filledGrade(value) {
    return value && value !== '-';
}

function percent(value, total) {
    if (!total) {
        return 0;
    }

    return Math.round((value / total) * 1000) / 10;
}

function getErrorMessage(error, fallback = 'Terjadi kendala pada layanan SPIP.') {
    return error?.response?.data?.pesan || error?.response?.data?.message || fallback;
}

function ProgressPanel({ title, filled, total }) {
    const value = percent(filled, total);

    return (
        <Paper variant="outlined" sx={{ p: 2, height: '100%' }}>
            <Stack spacing={1}>
                <Typography variant="caption" color="text.secondary">{title}</Typography>
                <Typography variant="h5" fontWeight="bold">{value}%</Typography>
                <LinearProgress
                    variant="determinate"
                    value={Math.min(value, 100)}
                    color={value >= 100 ? 'success' : value >= 60 ? 'warning' : 'error'}
                    sx={{ height: 8, borderRadius: 8 }}
                />
                <Typography variant="caption" color="text.secondary">{filled} dari {total} parameter terisi</Typography>
            </Stack>
        </Paper>
    );
}

function GradeChip({ value }) {
    const empty = !filledGrade(value);

    return <Chip size="small" color={gradeColor(value)} label={empty ? 'Belum diisi' : value} sx={{ minWidth: empty ? 86 : 42 }} />;
}

export default function SpipIndex({ tahun, spipSession: initialSession, spipAccessError }) {
    const [spipSession, setSpipSession] = useState(initialSession);
    const [accessError, setAccessError] = useState(spipAccessError);
    const [rows, setRows] = useState([]);
    const [klusters, setKlusters] = useState({ aoi: [], penyebab: [] });
    const [loadingRows, setLoadingRows] = useState(false);
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState(null);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('semua');

    const [detailOpen, setDetailOpen] = useState(false);
    const [detailLoading, setDetailLoading] = useState(false);
    const [selectedRow, setSelectedRow] = useState(null);
    const [criteriaRows, setCriteriaRows] = useState([]);
    const [selectedGrade, setSelectedGrade] = useState('');
    const [uraianMap, setUraianMap] = useState({});
    const [aoiData, setAoiData] = useState({ kAoI: '', uAoI: '', kSebab: '', uSebab: '' });

    const gradeField = spipSession?.tahapan === TAHAPAN_PM ? 'grade_pm' : 'grade_pk';
    const isReadOnly = Boolean(spipSession?.isReadOnly);
    const activeGradeIndex = selectedGrade ? GRADES.indexOf(selectedGrade) : -1;
    const visibleUraianGrades = activeGradeIndex >= 0 ? GRADES.slice(activeGradeIndex) : [];
    const needsAoi = selectedGrade === 'D' || selectedGrade === 'E' || spipSession?.tahapan === TAHAPAN_PK;
    const canUsePk = spipSession?.availableTahapan?.includes(TAHAPAN_PK);

    const filteredRows = useMemo(() => {
        const keyword = search.trim().toLowerCase();

        return rows.filter((row) => {
            const currentGrade = row[gradeField];
            const matchKeyword = !keyword || `${row.kode} ${row.parameter}`.toLowerCase().includes(keyword);
            const matchStatus = statusFilter === 'semua'
                || (statusFilter === 'terisi' && filledGrade(currentGrade))
                || (statusFilter === 'belum' && !filledGrade(currentGrade))
                || (statusFilter === 'perlu_aoi' && ['D', 'E'].includes(currentGrade));

            return matchKeyword && matchStatus;
        });
    }, [rows, search, statusFilter, gradeField]);

    const pmFilled = rows.filter((row) => filledGrade(row.grade_pm)).length;
    const pkFilled = rows.filter((row) => filledGrade(row.grade_pk)).length;

    const loadRows = async () => {
        if (!spipSession) {
            return;
        }

        setLoadingRows(true);
        try {
            const response = await axios.get('/spip/sub-unsur');
            setRows(response.data.data || []);
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Gagal memuat data SPIP.') });
        } finally {
            setLoadingRows(false);
        }
    };

    const refreshSession = async (tahapan = spipSession?.tahapan || TAHAPAN_PM) => {
        const response = await axios.get('/spip/session', { params: { tahapan } });
        setSpipSession(response.data);
        setAccessError(null);

        return response.data;
    };

    useEffect(() => {
        if (spipSession) {
            loadRows();
        }
    }, [Boolean(spipSession)]);

    useEffect(() => {
        axios.get('/spip/klusters')
            .then((response) => setKlusters(response.data.data || { aoi: [], penyebab: [] }))
            .catch(() => setKlusters({ aoi: [], penyebab: [] }));
    }, []);

    const handleTahapanChange = async (event) => {
        const tahapan = event.target.value;
        setBusy(true);
        setMessage(null);

        try {
            await refreshSession(tahapan);
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Tahapan SPIP belum dapat dibuka.') });
        } finally {
            setBusy(false);
        }
    };

    const openDetail = async (row) => {
        setSelectedRow(row);
        setDetailOpen(true);
        setDetailLoading(true);
        setCriteriaRows([]);
        setSelectedGrade('');
        setUraianMap({});
        setAoiData({ kAoI: '', uAoI: '', kSebab: '', uSebab: '' });

        try {
            const response = await axios.get('/spip/detail', {
                params: { kode_sub: row.kode },
            });
            const detail = response.data.data || {};
            const detailGrade = gradeField === 'grade_pm' ? detail.selected_grade_pm : detail.selected_grade_pk;
            const currentGrade = filledGrade(detailGrade)
                ? detailGrade
                : filledGrade(row[gradeField]) ? row[gradeField] : '';
            const map = {};

            (detail.kriteria || []).forEach((item) => {
                map[item.grade] = item.uraian || '';
            });

            setCriteriaRows(detail.kriteria || []);
            setSelectedGrade(currentGrade);
            setUraianMap(map);
            setAoiData({
                kAoI: detail.aoi?.kAoI || '',
                uAoI: detail.aoi?.uAoI || '',
                kSebab: detail.aoi?.kSebab || '',
                uSebab: detail.aoi?.uSebab || '',
            });
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Gagal membuka detail kriteria.') });
            setDetailOpen(false);
        } finally {
            setDetailLoading(false);
        }
    };

    const handleSaveDetail = async () => {
        if (!selectedRow || !selectedGrade) {
            setMessage({ severity: 'warning', text: 'Pilih grade terlebih dahulu.' });
            return;
        }

        setBusy(true);
        try {
            const response = await axios.post('/spip/kertas-kerja', {
                kodeSub: selectedRow.kode,
                tahapan: spipSession.tahapan,
                gradeTerpilih: selectedGrade,
                uraianMap,
                aoiData,
            });

            setMessage({ severity: 'success', text: response.data.pesan || 'Data berhasil disimpan.' });
            setDetailOpen(false);
            await loadRows();
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Data gagal disimpan.') });
        } finally {
            setBusy(false);
        }
    };

    const handleStatusChange = async () => {
        const nextStatus = spipSession.tahapan === TAHAPAN_PM ? 'Menunggu Approve PK' : 'Selesai';
        const confirmText = spipSession.tahapan === TAHAPAN_PM
            ? 'Ajukan Penilaian Mandiri untuk disetujui admin dan membuka tahapan PK?'
            : 'Tandai Penjaminan Kualitas sebagai selesai?';

        if (typeof window !== 'undefined' && !window.confirm(confirmText)) {
            return;
        }

        setBusy(true);
        try {
            const response = await axios.post('/spip/status', {
                status: nextStatus,
                tahapan: spipSession.tahapan,
            });

            setMessage({ severity: 'success', text: response.data.pesan });
            await refreshSession(spipSession.tahapan);
            await loadRows();
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Status SPIP gagal diperbarui.') });
        } finally {
            setBusy(false);
        }
    };

    const handleDownload = async () => {
        setBusy(true);
        try {
            const response = await axios.get('/spip/download');
            window.open(response.data.url, '_blank', 'noopener,noreferrer');
        } catch (error) {
            setMessage({ severity: 'error', text: getErrorMessage(error, 'Tautan download belum tersedia.') });
        } finally {
            setBusy(false);
        }
    };

    const columns = [
        { field: 'kode', headerName: 'Kode', width: 120 },
        {
            field: 'parameter',
            headerName: 'Parameter',
            minWidth: 360,
            flex: 1,
            renderCell: ({ value }) => (
                <Typography variant="body2" sx={{ py: 1, whiteSpace: 'normal', lineHeight: 1.45 }}>
                    {value || '-'}
                </Typography>
            ),
        },
        { field: 'spip', headerName: 'SPIP', width: 90, renderCell: ({ value }) => <Chip size="small" label={value || '-'} /> },
        { field: 'mri', headerName: 'MRI', width: 90, renderCell: ({ value }) => <Chip size="small" label={value || '-'} /> },
        { field: 'iepk', headerName: 'IEPK', width: 90, renderCell: ({ value }) => <Chip size="small" label={value || '-'} /> },
        { field: 'grade_pm', headerName: 'Grade PM', width: 115, renderCell: ({ value }) => <GradeChip value={value} /> },
        { field: 'grade_pk', headerName: 'Grade PK', width: 115, renderCell: ({ value }) => <GradeChip value={value} /> },
        {
            field: 'aksi',
            headerName: '',
            width: 90,
            sortable: false,
            filterable: false,
            renderCell: ({ row }) => (
                <Tooltip title={isReadOnly ? 'Lihat detail' : 'Isi kertas kerja'}>
                    <IconButton size="small" color="primary" onClick={() => openDetail(row)}>
                        <VisibilityIcon fontSize="small" />
                    </IconButton>
                </Tooltip>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="SPIP" />
            <Stack spacing={2.5}>
                <Box>
                    <Typography variant="h5" fontWeight="bold">SPIP {tahun}</Typography>
                    <Typography variant="body2" color="text.secondary">
                        Pengisian kertas kerja SPIP memakai akun aplikasi yang sedang login.
                    </Typography>
                </Box>

                {message && <Alert severity={message.severity} onClose={() => setMessage(null)}>{message.text}</Alert>}

                {!spipSession ? (
                    <Paper variant="outlined" sx={{ p: 3 }}>
                        <Alert severity="warning">
                            {accessError || 'Akun aplikasi ini belum memiliki data SPIP.'}
                        </Alert>
                    </Paper>
                ) : (
                    <>
                        <Paper variant="outlined" sx={{ p: 2.5 }}>
                            <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} justifyContent="space-between">
                                <Box>
                                    <Typography variant="h6" fontWeight="bold">{spipSession.nama}</Typography>
                                    <Stack direction="row" spacing={1} flexWrap="wrap" sx={{ mt: 1 }}>
                                        <Chip label={spipSession.userId} />
                                        <Chip color={spipSession.statusPK === 'Selesai' ? 'success' : spipSession.statusPK === 'Aktif' ? 'primary' : 'default'} label={spipSession.statusPK} />
                                        {isReadOnly && <Chip color="warning" label="Terkunci" />}
                                    </Stack>
                                </Box>
                                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} alignItems={{ xs: 'stretch', sm: 'center' }}>
                                    <FormControl size="small" sx={{ minWidth: 220 }}>
                                        <InputLabel id="spip-tahapan-select">Tahapan</InputLabel>
                                        <Select
                                            labelId="spip-tahapan-select"
                                            label="Tahapan"
                                            value={spipSession.tahapan}
                                            onChange={handleTahapanChange}
                                            disabled={busy}
                                        >
                                            <MenuItem value={TAHAPAN_PM}>Penilaian Mandiri</MenuItem>
                                            <MenuItem value={TAHAPAN_PK} disabled={!canUsePk}>Penjaminan Kualitas</MenuItem>
                                        </Select>
                                    </FormControl>
                                    {!isReadOnly && (
                                        <Button variant="contained" startIcon={<SendIcon />} onClick={handleStatusChange} disabled={busy}>
                                            {spipSession.tahapan === TAHAPAN_PM ? 'Siap PK' : 'Selesai PK'}
                                        </Button>
                                    )}
                                    <Button variant="outlined" startIcon={<DownloadIcon />} onClick={handleDownload} disabled={busy}>
                                        Download
                                    </Button>
                                </Stack>
                            </Stack>
                        </Paper>

                        <Grid container spacing={2}>
                            <Grid item xs={12} md={6}><ProgressPanel title="Progress Penilaian Mandiri" filled={pmFilled} total={rows.length} /></Grid>
                            <Grid item xs={12} md={6}><ProgressPanel title="Progress Penjaminan Kualitas" filled={pkFilled} total={rows.length} /></Grid>
                        </Grid>

                        <Paper variant="outlined" sx={{ p: 2 }}>
                            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5} justifyContent="space-between" sx={{ mb: 2 }}>
                                <TextField
                                    label="Cari parameter"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    size="small"
                                    sx={{ minWidth: { md: 360 } }}
                                    InputProps={{ startAdornment: <SearchIcon fontSize="small" sx={{ mr: 1, color: 'text.secondary' }} /> }}
                                />
                                <FormControl size="small" sx={{ minWidth: 180 }}>
                                    <InputLabel id="spip-filter-status">Status</InputLabel>
                                    <Select
                                        labelId="spip-filter-status"
                                        label="Status"
                                        value={statusFilter}
                                        onChange={(event) => setStatusFilter(event.target.value)}
                                    >
                                        <MenuItem value="semua">Semua</MenuItem>
                                        <MenuItem value="terisi">Terisi</MenuItem>
                                        <MenuItem value="belum">Belum terisi</MenuItem>
                                        <MenuItem value="perlu_aoi">Grade D/E</MenuItem>
                                    </Select>
                                </FormControl>
                            </Stack>
                            <DataGrid
                                autoHeight
                                rows={filteredRows}
                                columns={columns}
                                getRowId={(row) => row.kode}
                                loading={loadingRows}
                                disableRowSelectionOnClick
                                getRowHeight={() => 'auto'}
                                pageSizeOptions={[10, 25, 50]}
                                initialState={{ pagination: { paginationModel: { pageSize: 10 } } }}
                                sx={{
                                    '& .MuiDataGrid-cell': { alignItems: 'flex-start' },
                                    '& .MuiDataGrid-columnHeaderTitle': { fontWeight: 'bold' },
                                }}
                            />
                        </Paper>
                    </>
                )}
            </Stack>

            <Dialog open={detailOpen} onClose={() => setDetailOpen(false)} maxWidth="lg" fullWidth>
                <DialogTitle>
                    <Typography variant="h6" fontWeight="bold">{selectedRow?.kode} - Kertas Kerja SPIP</Typography>
                    <Typography variant="body2" color="text.secondary">{selectedRow?.parameter}</Typography>
                </DialogTitle>
                <DialogContent dividers>
                    {detailLoading ? (
                        <LinearProgress />
                    ) : (
                        <Stack spacing={2.5}>
                            <Box>
                                <Typography variant="subtitle2" fontWeight="bold" gutterBottom>Pilih Grade</Typography>
                                <RadioGroup
                                    row
                                    value={selectedGrade}
                                    onChange={(event) => setSelectedGrade(event.target.value)}
                                >
                                    {GRADES.map((grade) => (
                                        <FormControlLabel
                                            key={grade}
                                            value={grade}
                                            disabled={isReadOnly}
                                            control={<Radio />}
                                            label={grade}
                                        />
                                    ))}
                                </RadioGroup>
                            </Box>

                            <Divider />

                            <Box>
                                <Typography variant="subtitle2" fontWeight="bold" gutterBottom>Kriteria Grade</Typography>
                                <Grid container spacing={1.5}>
                                    {criteriaRows.map((item) => (
                                        <Grid key={item.grade} item xs={12} md={6}>
                                            <Paper variant="outlined" sx={{ p: 1.5, height: '100%' }}>
                                                <Stack spacing={1}>
                                                    <Stack direction="row" spacing={1} alignItems="center">
                                                        <GradeChip value={item.grade} />
                                                        <Typography variant="subtitle2" fontWeight="bold">Grade {item.grade}</Typography>
                                                    </Stack>
                                                    <Typography variant="body2">{item.kriteria || '-'}</Typography>
                                                    {item.penjelasan && <Typography variant="caption" color="text.secondary">{item.penjelasan}</Typography>}
                                                </Stack>
                                            </Paper>
                                        </Grid>
                                    ))}
                                </Grid>
                            </Box>

                            {selectedGrade && (
                                <Box>
                                    <Typography variant="subtitle2" fontWeight="bold" gutterBottom>
                                        Uraian Hasil Pengujian
                                    </Typography>
                                    <Stack spacing={1.5}>
                                        {visibleUraianGrades.map((grade) => (
                                            <TextField
                                                key={grade}
                                                label={`Uraian untuk Grade ${grade}`}
                                                minRows={3}
                                                multiline
                                                fullWidth
                                                value={uraianMap[grade] || ''}
                                                disabled={isReadOnly}
                                                onChange={(event) => setUraianMap((current) => ({ ...current, [grade]: event.target.value }))}
                                            />
                                        ))}
                                    </Stack>
                                </Box>
                            )}

                            {needsAoi && (
                                <Box>
                                    <Typography variant="subtitle2" fontWeight="bold" gutterBottom>Area of Improvement</Typography>
                                    <Grid container spacing={1.5}>
                                        {spipSession?.tahapan === TAHAPAN_PM && (
                                            <>
                                                <Grid item xs={12} md={6}>
                                                    <FormControl fullWidth>
                                                        <InputLabel id="spip-kluster-aoi">Kluster AoI</InputLabel>
                                                        <Select
                                                            labelId="spip-kluster-aoi"
                                                            label="Kluster AoI"
                                                            value={aoiData.kAoI}
                                                            disabled={isReadOnly}
                                                            onChange={(event) => setAoiData((current) => ({ ...current, kAoI: event.target.value }))}
                                                        >
                                                            <MenuItem value="">-</MenuItem>
                                                            {klusters.aoi.map((item) => <MenuItem key={item} value={item}>{item}</MenuItem>)}
                                                        </Select>
                                                    </FormControl>
                                                </Grid>
                                                <Grid item xs={12} md={6}>
                                                    <FormControl fullWidth>
                                                        <InputLabel id="spip-kluster-penyebab">Kluster Penyebab</InputLabel>
                                                        <Select
                                                            labelId="spip-kluster-penyebab"
                                                            label="Kluster Penyebab"
                                                            value={aoiData.kSebab}
                                                            disabled={isReadOnly}
                                                            onChange={(event) => setAoiData((current) => ({ ...current, kSebab: event.target.value }))}
                                                        >
                                                            <MenuItem value="">-</MenuItem>
                                                            {klusters.penyebab.map((item) => <MenuItem key={item} value={item}>{item}</MenuItem>)}
                                                        </Select>
                                                    </FormControl>
                                                </Grid>
                                            </>
                                        )}
                                        <Grid item xs={12}>
                                            <TextField
                                                label={spipSession?.tahapan === TAHAPAN_PK ? 'Uraian AoI Hasil PK' : 'Uraian AoI'}
                                                minRows={3}
                                                multiline
                                                fullWidth
                                                value={aoiData.uAoI}
                                                disabled={isReadOnly}
                                                onChange={(event) => setAoiData((current) => ({ ...current, uAoI: event.target.value }))}
                                            />
                                        </Grid>
                                        {spipSession?.tahapan === TAHAPAN_PM && (
                                            <Grid item xs={12}>
                                                <TextField
                                                    label="Uraian Penyebab"
                                                    minRows={3}
                                                    multiline
                                                    fullWidth
                                                    value={aoiData.uSebab}
                                                    disabled={isReadOnly}
                                                    onChange={(event) => setAoiData((current) => ({ ...current, uSebab: event.target.value }))}
                                                />
                                            </Grid>
                                        )}
                                    </Grid>
                                </Box>
                            )}
                        </Stack>
                    )}
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setDetailOpen(false)}>Tutup</Button>
                    {!isReadOnly && (
                        <Button variant="contained" startIcon={<SaveIcon />} onClick={handleSaveDetail} disabled={busy || detailLoading}>
                            Simpan
                        </Button>
                    )}
                </DialogActions>
            </Dialog>
        </AuthenticatedLayout>
    );
}
