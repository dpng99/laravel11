import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Alert,
    Box,
    Card,
    CardContent,
    CardHeader,
    Chip,
    Divider,
    Grid,
    LinearProgress,
    Paper,
    Stack,
    Typography,
} from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import AnalyticsIcon from '@mui/icons-material/Analytics';
import FactCheckIcon from '@mui/icons-material/FactCheck';
import GroupsIcon from '@mui/icons-material/Groups';
import ReportProblemIcon from '@mui/icons-material/ReportProblem';
import TaskAltIcon from '@mui/icons-material/TaskAlt';

function SummaryCard({ title, value, caption, icon, color = 'primary' }) {
    return (
        <Paper variant="outlined" sx={{ p: 2.25, height: '100%' }}>
            <Stack direction="row" spacing={1.5} alignItems="flex-start">
                <Box sx={{ color: `${color}.main`, pt: 0.35 }}>{icon}</Box>
                <Box sx={{ minWidth: 0 }}>
                    <Typography variant="caption" color="text.secondary">{title}</Typography>
                    <Typography variant="h4" fontWeight="bold" color={`${color}.main`}>
                        {value}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">{caption}</Typography>
                </Box>
            </Stack>
        </Paper>
    );
}

function ProgressBar({ value, color = 'primary' }) {
    return (
        <Stack direction="row" spacing={1.25} alignItems="center">
            <LinearProgress
                variant="determinate"
                value={Math.min(Number(value) || 0, 100)}
                color={color}
                sx={{ flex: 1, height: 9, borderRadius: 8 }}
            />
            <Typography variant="body2" fontWeight="bold" sx={{ width: 52, textAlign: 'right' }}>
                {Number(value || 0).toFixed(1)}%
            </Typography>
        </Stack>
    );
}

function progressColor(value) {
    if (value >= 80) return 'success';
    if (value >= 50) return 'warning';
    return 'error';
}

function DocumentTable({ rows }) {
    const columns = [
        {
            field: 'label',
            headerName: 'Dokumen',
            flex: 1.2,
            minWidth: 190,
            renderCell: (params) => (
                <Box>
                    <Typography variant="body2" fontWeight="bold">{params.row.label}</Typography>
                    {params.row.status !== 'ready' && (
                        <Typography variant="caption" color="error">{params.row.note}</Typography>
                    )}
                </Box>
            ),
        },
        {
            field: 'category',
            headerName: 'Kategori',
            width: 140,
            renderCell: (params) => <Chip size="small" label={params.row.category} variant="outlined" />,
        },
        {
            field: 'percentage',
            headerName: 'Progres',
            flex: 1,
            minWidth: 220,
            renderCell: (params) => (
                <Box sx={{ width: '100%' }}>
                    <ProgressBar value={params.row.percentage} color={progressColor(params.row.percentage)} />
                </Box>
            ),
        },
        { field: 'uploaded_satkers', headerName: 'Sudah', width: 95, type: 'number' },
        { field: 'missing_satkers', headerName: 'Belum', width: 95, type: 'number' },
        { field: 'total_uploads', headerName: 'Upload', width: 95, type: 'number' },
    ];

    return (
        <Box sx={{ width: '100%' }}>
            <DataGrid
                autoHeight
                rows={rows}
                columns={columns}
                getRowId={(row) => row.key}
                disableRowSelectionOnClick
                pageSizeOptions={[12, 25, 50]}
                initialState={{ pagination: { paginationModel: { pageSize: 12 } } }}
                sx={{ '& .MuiDataGrid-cell': { alignItems: 'center' } }}
            />
        </Box>
    );
}

function CategoryPanel({ rows }) {
    return (
        <Stack spacing={1.5}>
            {rows.map((row) => (
                <Paper key={row.category} variant="outlined" sx={{ p: 1.5 }}>
                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5} alignItems={{ xs: 'stretch', md: 'center' }}>
                        <Box sx={{ width: { xs: '100%', md: 180 } }}>
                            <Typography fontWeight="bold">{row.category}</Typography>
                            <Typography variant="caption" color="text.secondary">
                                {row.document_types} jenis dokumen
                            </Typography>
                        </Box>
                        <Box sx={{ flex: 1 }}>
                            <ProgressBar value={row.percentage} color={progressColor(row.percentage)} />
                        </Box>
                    </Stack>
                </Paper>
            ))}
        </Stack>
    );
}

function WilayahTable({ title, rows }) {
    const columns = [
        {
            field: 'kejati_name',
            headerName: 'Wilayah',
            flex: 1,
            minWidth: 220,
            renderCell: (params) => (
                <Box>
                    <Typography variant="body2" fontWeight="bold">{params.row.kejati_name}</Typography>
                    <Typography variant="caption" color="text.secondary">
                        {params.row.complete_satkers} lengkap, {params.row.critical_satkers} kritis
                    </Typography>
                </Box>
            ),
        },
        { field: 'total_satkers', headerName: 'Satker', width: 95, type: 'number' },
        {
            field: 'completion_percentage',
            headerName: 'Progres',
            flex: 0.9,
            minWidth: 190,
            renderCell: (params) => (
                <Box sx={{ width: '100%' }}>
                    <ProgressBar value={params.row.completion_percentage} color={progressColor(params.row.completion_percentage)} />
                </Box>
            ),
        },
    ];

    return (
        <Card variant="outlined">
            <CardHeader title={title} titleTypographyProps={{ variant: 'h6', fontWeight: 'bold' }} />
            <Divider />
            <CardContent>
                <DataGrid
                    autoHeight
                    rows={rows}
                    columns={columns}
                    getRowId={(row) => row.id_kejati}
                    disableRowSelectionOnClick
                    hideFooter
                    sx={{ '& .MuiDataGrid-cell': { alignItems: 'center' } }}
                />
            </CardContent>
        </Card>
    );
}

function PriorityTable({ rows }) {
    const preparedRows = rows.map((row) => ({
        ...row,
        missing_documents_text: row.missing_documents?.length ? row.missing_documents.join(', ') : 'Lengkap',
    }));

    const columns = [
        {
            field: 'satkernama',
            headerName: 'Satker Prioritas',
            flex: 1,
            minWidth: 240,
            renderCell: (params) => (
                <Box>
                    <Typography variant="body2" fontWeight="bold">{params.row.satkernama}</Typography>
                    <Typography variant="caption" color="text.secondary">{params.row.id_satker}</Typography>
                </Box>
            ),
        },
        { field: 'kejati_name', headerName: 'Wilayah', flex: 0.85, minWidth: 190 },
        {
            field: 'completion_percentage',
            headerName: 'Kelengkapan',
            flex: 0.75,
            minWidth: 210,
            renderCell: (params) => (
                <Box sx={{ width: '100%' }}>
                    <ProgressBar value={params.row.completion_percentage} color={progressColor(params.row.completion_percentage)} />
                </Box>
            ),
        },
        {
            field: 'missing_documents_text',
            headerName: 'Dokumen Belum Ada',
            flex: 1.3,
            minWidth: 260,
            renderCell: (params) => (
                <Typography variant="body2" sx={{ whiteSpace: 'normal', lineHeight: 1.35 }}>
                    {params.row.missing_documents_text}
                </Typography>
            ),
        },
    ];

    return (
        <Box sx={{ width: '100%' }}>
            <DataGrid
                autoHeight
                rows={preparedRows}
                columns={columns}
                getRowId={(row) => row.id_satker}
                disableRowSelectionOnClick
                pageSizeOptions={[15, 30, 50]}
                initialState={{ pagination: { paginationModel: { pageSize: 15 } } }}
                sx={{ '& .MuiDataGrid-cell': { alignItems: 'center' } }}
            />
        </Box>
    );
}

export default function DashboardAnalytic({ tahun, summary, documentStats, categoryStats, wilayahStats, prioritySatkers }) {
    const bestWilayah = wilayahStats.slice(0, 5);
    const weakestWilayah = [...wilayahStats].sort((a, b) => a.completion_percentage - b.completion_percentage).slice(0, 5);
    const weakestDocument = [...documentStats]
        .filter((row) => row.status === 'ready')
        .sort((a, b) => a.percentage - b.percentage)[0];

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard Analitik" />

            <Stack spacing={3}>
                <Card elevation={3}>
                    <CardHeader
                        avatar={<AnalyticsIcon />}
                        title="Dashboard Analitik"
                        subheader={`Pemantauan kepatuhan dokumen AKIP nasional tahun ${tahun}`}
                        titleTypographyProps={{ variant: 'h5', fontWeight: 'bold' }}
                        sx={{ backgroundColor: 'primary.main', color: 'white', '& .MuiCardHeader-subheader': { color: 'white' } }}
                    />
                </Card>

                {weakestDocument && (
                    <Alert severity={weakestDocument.percentage >= 80 ? 'success' : 'warning'}>
                        Dokumen dengan progres terendah saat ini adalah <strong>{weakestDocument.label}</strong>:
                        {' '}{weakestDocument.uploaded_satkers} satker sudah upload dari {summary.total_satkers} satker.
                    </Alert>
                )}

                <Grid container spacing={2}>
                    <Grid item xs={12} sm={6} lg={3}>
                        <SummaryCard
                            title="Total Satker"
                            value={summary.total_satkers}
                            caption={`${summary.document_types} jenis dokumen dipantau`}
                            icon={<GroupsIcon />}
                        />
                    </Grid>
                    <Grid item xs={12} sm={6} lg={3}>
                        <SummaryCard
                            title="Rata-rata Kelengkapan"
                            value={`${summary.average_completion}%`}
                            caption="Akumulasi seluruh dokumen wajib"
                            icon={<FactCheckIcon />}
                            color={progressColor(summary.average_completion)}
                        />
                    </Grid>
                    <Grid item xs={12} sm={6} lg={3}>
                        <SummaryCard
                            title="Satker Lengkap"
                            value={summary.complete_satkers}
                            caption={`${summary.incomplete_satkers} satker masih belum lengkap`}
                            icon={<TaskAltIcon />}
                            color="success"
                        />
                    </Grid>
                    <Grid item xs={12} sm={6} lg={3}>
                        <SummaryCard
                            title="Satker Kritis"
                            value={summary.critical_satkers}
                            caption="Kelengkapan di bawah 50%"
                            icon={<ReportProblemIcon />}
                            color={summary.critical_satkers > 0 ? 'error' : 'success'}
                        />
                    </Grid>
                </Grid>

                <Grid container spacing={2}>
                    <Grid item xs={12} lg={8}>
                        <Card variant="outlined">
                            <CardHeader
                                title="Kepatuhan per Jenis Dokumen"
                                subheader="Menghitung satker yang sudah memiliki minimal satu file pada tahun/periode terpilih"
                            />
                            <CardContent>
                                <DocumentTable rows={documentStats} />
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={12} lg={4}>
                        <Card variant="outlined" sx={{ height: '100%' }}>
                            <CardHeader title="Kelengkapan per Kategori" />
                            <CardContent>
                                <CategoryPanel rows={categoryStats} />
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>

                <Grid container spacing={2}>
                    <Grid item xs={12} lg={6}>
                        <WilayahTable title="Wilayah dengan Progres Terbaik" rows={bestWilayah} />
                    </Grid>
                    <Grid item xs={12} lg={6}>
                        <WilayahTable title="Wilayah Perlu Perhatian" rows={weakestWilayah} />
                    </Grid>
                </Grid>

                <Card variant="outlined">
                    <CardHeader
                        title="Satker Prioritas Tindak Lanjut"
                        subheader="Diurutkan dari kelengkapan dokumen terendah"
                    />
                    <CardContent>
                        <PriorityTable rows={prioritySatkers} />
                    </CardContent>
                </Card>
            </Stack>
        </AuthenticatedLayout>
    );
}
