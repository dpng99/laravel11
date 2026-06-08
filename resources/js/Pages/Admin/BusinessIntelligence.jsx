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
    Grid,
    LinearProgress,
    Paper,
    Stack,
    Typography,
} from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import { Bar, Line } from 'react-chartjs-2';
import {
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    BarElement,
    Title,
    Tooltip,
} from 'chart.js';
import AutoGraphIcon from '@mui/icons-material/AutoGraph';
import PsychologyIcon from '@mui/icons-material/Psychology';
import TrendingDownIcon from '@mui/icons-material/TrendingDown';
import TrendingUpIcon from '@mui/icons-material/TrendingUp';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend);

const riskColor = { Tinggi: 'error', Sedang: 'warning', Rendah: 'success' };

function MetricCard({ title, value, caption, color = 'primary', icon }) {
    return (
        <Paper variant="outlined" sx={{ p: 2.25, height: '100%' }}>
            <Stack direction="row" spacing={1.5}>
                <Box sx={{ color: `${color}.main`, pt: 0.4 }}>{icon}</Box>
                <Box>
                    <Typography variant="caption" color="text.secondary">{title}</Typography>
                    <Typography variant="h4" fontWeight="bold" color={`${color}.main`}>{value}</Typography>
                    <Typography variant="body2" color="text.secondary">{caption}</Typography>
                </Box>
            </Stack>
        </Paper>
    );
}

function Progress({ value }) {
    const color = value >= 75 ? 'success' : value >= 50 ? 'warning' : 'error';
    return (
        <Stack direction="row" spacing={1} alignItems="center" sx={{ width: '100%' }}>
            <LinearProgress variant="determinate" value={Math.min(Number(value), 100)} color={color} sx={{ flex: 1, height: 8, borderRadius: 8 }} />
            <Typography variant="caption" fontWeight="bold" sx={{ width: 46, textAlign: 'right' }}>{Number(value).toFixed(1)}%</Typography>
        </Stack>
    );
}

function PriorityTable({ rows }) {
    const columns = [
        {
            field: 'satkernama',
            headerName: 'Satker Prioritas',
            minWidth: 230,
            flex: 1,
            renderCell: ({ row }) => (
                <Box>
                    <Typography variant="body2" fontWeight="bold">{row.satkernama}</Typography>
                    <Typography variant="caption" color="text.secondary">{row.kejati_name}</Typography>
                </Box>
            ),
        },
        { field: 'completion', headerName: 'Kelengkapan', minWidth: 190, flex: 0.8, renderCell: ({ value }) => <Progress value={value} /> },
        {
            field: 'trend',
            headerName: 'Tren',
            width: 100,
            renderCell: ({ value }) => <Typography color={value < 0 ? 'error.main' : 'success.main'} fontWeight="bold">{value > 0 ? '+' : ''}{value}</Typography>,
        },
        { field: 'risk', headerName: 'Risiko', width: 110, renderCell: ({ value }) => <Chip size="small" color={riskColor[value]} label={value} /> },
        { field: 'missing_count', headerName: 'Belum Ada', width: 105, type: 'number' },
        { field: 'missing_documents', headerName: 'Dokumen Prioritas', minWidth: 260, flex: 1.2, valueGetter: (value) => value?.join(', ') || 'Lengkap' },
    ];

    return <DataGrid autoHeight rows={rows} columns={columns} getRowId={(row) => row.id_satker} disableRowSelectionOnClick pageSizeOptions={[10, 25]} initialState={{ pagination: { paginationModel: { pageSize: 10 } } }} />;
}

export default function BusinessIntelligence({ tahun, analysis, analysisError }) {
    if (!analysis) {
        return (
            <AuthenticatedLayout>
                <Head title="Business Intelligence" />
                <Alert severity="error">
                    <strong>Business Intelligence belum dapat dijalankan.</strong><br />{analysisError}
                </Alert>
            </AuthenticatedLayout>
        );
    }

    const { summary, trend, risk_distribution: riskDistribution, priority_satkers: priorities, region_performance: regions, document_opportunities: documents, insights, engine } = analysis;
    const trendData = {
        labels: trend.map((row) => row.year),
        datasets: [{ label: 'Kelengkapan Nasional (%)', data: trend.map((row) => row.completion), borderColor: '#1565c0', backgroundColor: '#1565c0', tension: 0.25 }],
    };
    const riskData = {
        labels: riskDistribution.map((row) => row.level),
        datasets: [{ label: 'Jumlah Satker', data: riskDistribution.map((row) => row.count), backgroundColor: ['#d32f2f', '#ed6c02', '#2e7d32'] }],
    };
    const documentColumns = [
        { field: 'label', headerName: 'Dokumen', minWidth: 190, flex: 1 },
        { field: 'category', headerName: 'Kategori', width: 130 },
        { field: 'coverage', headerName: 'Cakupan', minWidth: 190, flex: 0.9, renderCell: ({ value }) => <Progress value={value} /> },
        { field: 'change', headerName: 'Perubahan', width: 110, renderCell: ({ value }) => `${value > 0 ? '+' : ''}${value} poin` },
        { field: 'missing_satkers', headerName: 'Belum', width: 90, type: 'number' },
    ];
    const regionColumns = [
        { field: 'name', headerName: 'Wilayah', minWidth: 220, flex: 1 },
        { field: 'satkers', headerName: 'Satker', width: 85, type: 'number' },
        { field: 'completion', headerName: 'Kelengkapan', minWidth: 190, flex: 0.8, renderCell: ({ value }) => <Progress value={value} /> },
        { field: 'trend', headerName: 'Tren', width: 100, renderCell: ({ value }) => `${value > 0 ? '+' : ''}${value}` },
        { field: 'high_risk', headerName: 'Risiko Tinggi', width: 120, type: 'number' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Business Intelligence" />
            <Stack spacing={3}>
                <Card elevation={3}>
                    <CardHeader
                        avatar={<PsychologyIcon />}
                        title="Business Intelligence AKIP"
                        subheader={`Analisis prediktif dan prioritas keputusan admin tahun ${tahun}`}
                        titleTypographyProps={{ variant: 'h5', fontWeight: 'bold' }}
                        sx={{ backgroundColor: 'primary.main', color: 'white', '& .MuiCardHeader-subheader': { color: 'white' } }}
                    />
                </Card>

                <Alert severity="info">
                    Dianalisis oleh <strong>{engine.name}</strong>. Metode: {engine.method}.
                </Alert>

                <Grid container spacing={2}>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Kelengkapan Nasional" value={`${summary.average_completion}%`} caption={`${summary.yearly_change > 0 ? '+' : ''}${summary.yearly_change} poin dari tahun lalu`} icon={<AutoGraphIcon />} /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Risiko Tinggi" value={summary.high_risk_satkers} caption="Satker perlu tindak lanjut segera" color="error" icon={<WarningAmberIcon />} /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Satker Membaik" value={summary.improving_satkers} caption="Naik lebih dari 2 poin" color="success" icon={<TrendingUpIcon />} /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Satker Menurun" value={summary.declining_satkers} caption="Turun lebih dari 2 poin" color="warning" icon={<TrendingDownIcon />} /></Grid>
                </Grid>

                <Grid container spacing={2}>
                    <Grid item xs={12} lg={8}>
                        <Card variant="outlined"><CardHeader title="Tren Kelengkapan Nasional" /><CardContent><Box sx={{ height: 300 }}><Line data={trendData} options={{ maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }} /></Box></CardContent></Card>
                    </Grid>
                    <Grid item xs={12} lg={4}>
                        <Card variant="outlined"><CardHeader title="Distribusi Risiko Satker" /><CardContent><Box sx={{ height: 300 }}><Bar data={riskData} options={{ maintainAspectRatio: false, plugins: { legend: { display: false } } }} /></Box></CardContent></Card>
                    </Grid>
                </Grid>

                <Card variant="outlined">
                    <CardHeader title="Insight dan Rekomendasi Python" subheader="Temuan otomatis yang paling relevan untuk keputusan admin" />
                    <CardContent><Stack spacing={1.5}>{insights.map((item) => <Alert key={item.title} severity={item.severity}><strong>{item.title}</strong><br />{item.description}<br /><Typography variant="caption"><strong>Tindakan:</strong> {item.action}</Typography></Alert>)}</Stack></CardContent>
                </Card>

                <Card variant="outlined"><CardHeader title="Satker Prioritas Tindak Lanjut" subheader="Diurutkan berdasarkan skor risiko BI" /><CardContent><PriorityTable rows={priorities} /></CardContent></Card>

                <Grid container spacing={2}>
                    <Grid item xs={12} lg={6}><Card variant="outlined"><CardHeader title="Peluang Perbaikan Dokumen" /><CardContent><DataGrid autoHeight rows={documents} columns={documentColumns} getRowId={(row) => row.key} hideFooter disableRowSelectionOnClick /></CardContent></Card></Grid>
                    <Grid item xs={12} lg={6}><Card variant="outlined"><CardHeader title="Performa Wilayah" /><CardContent><DataGrid autoHeight rows={regions} columns={regionColumns} getRowId={(row) => row.name} pageSizeOptions={[10, 25]} initialState={{ pagination: { paginationModel: { pageSize: 10 } } }} disableRowSelectionOnClick /></CardContent></Card></Grid>
                </Grid>
            </Stack>
        </AuthenticatedLayout>
    );
}
