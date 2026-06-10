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

function formatPercent(value, digits = 1) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '-';
    }

    return `${Number(value).toFixed(digits)}%`;
}

function formatNumber(value, digits = 2) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '-';
    }

    return Number(value).toFixed(digits);
}

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
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return <Typography variant="caption" color="text.secondary">Belum ada data</Typography>;
    }

    const color = value >= 100 ? 'success' : value >= 80 ? 'warning' : 'error';
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
        { field: 'achievement', headerName: 'Capaian/Target', minWidth: 190, flex: 0.8, renderCell: ({ value }) => <Progress value={value} /> },
        { field: 'target_average', headerName: 'Target Rata-rata', width: 130, renderCell: ({ value }) => formatNumber(value) },
        { field: 'capaian_average', headerName: 'Capaian Rata-rata', width: 140, renderCell: ({ value }) => formatNumber(value) },
        {
            field: 'trend',
            headerName: 'Tren',
            width: 100,
            renderCell: ({ value }) => <Typography color={value < 0 ? 'error.main' : 'success.main'} fontWeight="bold">{value > 0 ? '+' : ''}{value}</Typography>,
        },
        { field: 'risk', headerName: 'Risiko', width: 110, renderCell: ({ value }) => <Chip size="small" color={riskColor[value]} label={value} /> },
        { field: 'under_target_count', headerName: 'IKSS < Target', width: 120, type: 'number' },
        { field: 'measured_ikss', headerName: 'Data IKSS', width: 110, renderCell: ({ row }) => `${row.measured_ikss}/${row.ikss_total}` },
        {
            field: 'attention_ikss',
            headerName: 'IKSS Prioritas',
            minWidth: 280,
            flex: 1.2,
            renderCell: ({ value }) => value?.map((item) => item.id).join(', ') || 'Sesuai target',
        },
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

    const {
        summary,
        trend,
        risk_distribution: riskDistribution,
        priority_satkers: priorities,
        region_performance: regions,
        ikss_opportunities: ikssRows,
        strategic_objectives: strategicObjectives,
        insights,
        engine,
    } = analysis;
    const trendData = {
        labels: trend.map((row) => row.year),
        datasets: [{ label: 'Capaian terhadap Target Nasional (%)', data: trend.map((row) => row.achievement), borderColor: '#1565c0', backgroundColor: '#1565c0', tension: 0.25 }],
    };
    const riskData = {
        labels: riskDistribution.map((row) => row.level),
        datasets: [{ label: 'Jumlah Satker', data: riskDistribution.map((row) => row.count), backgroundColor: ['#d32f2f', '#ed6c02', '#2e7d32'] }],
    };
    const ikssColumns = [
        {
            field: 'name',
            headerName: 'IKSS',
            minWidth: 260,
            flex: 1.3,
            renderCell: ({ row }) => (
                <Box>
                    <Typography variant="body2" fontWeight="bold">{row.id}</Typography>
                    <Typography variant="caption" color="text.secondary">{row.name}</Typography>
                </Box>
            ),
        },
        { field: 'ss_id', headerName: 'SS', width: 85 },
        { field: 'target_average', headerName: 'Target', width: 105, renderCell: ({ value }) => formatNumber(value) },
        { field: 'capaian_average', headerName: 'Capaian', width: 110, renderCell: ({ value }) => formatNumber(value) },
        { field: 'average_achievement', headerName: 'Capaian/Target', minWidth: 180, flex: 0.9, renderCell: ({ value }) => <Progress value={value} /> },
        { field: 'change', headerName: 'Perubahan', width: 110, renderCell: ({ value }) => value === null || value === undefined ? '-' : `${value > 0 ? '+' : ''}${value} poin` },
        { field: 'coverage', headerName: 'Data', width: 95, renderCell: ({ value }) => formatPercent(value) },
        { field: 'below_target_satkers', headerName: 'Satker < Target', width: 130, type: 'number' },
    ];
    const ssColumns = [
        { field: 'id', headerName: 'SS', width: 85 },
        { field: 'name', headerName: 'Sasaran Strategis', minWidth: 260, flex: 1.2 },
        { field: 'average_achievement', headerName: 'Capaian/Target', minWidth: 180, flex: 0.8, renderCell: ({ value }) => <Progress value={value} /> },
        { field: 'measured_ikss', headerName: 'IKSS Terukur', width: 120, renderCell: ({ row }) => `${row.measured_ikss}/${row.ikss_count}` },
        { field: 'below_target_ikss', headerName: 'IKSS < Target', width: 125, type: 'number' },
    ];
    const regionColumns = [
        { field: 'name', headerName: 'Wilayah', minWidth: 220, flex: 1 },
        { field: 'satkers', headerName: 'Satker', width: 85, type: 'number' },
        { field: 'achievement', headerName: 'Capaian/Target', minWidth: 190, flex: 0.8, renderCell: ({ value }) => <Progress value={value} /> },
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
                        subheader={`Analisis SS, IKSS, target, dan capaian tahun ${tahun}`}
                        titleTypographyProps={{ variant: 'h5', fontWeight: 'bold' }}
                        sx={{ backgroundColor: 'primary.main', color: 'white', '& .MuiCardHeader-subheader': { color: 'white' } }}
                    />
                </Card>

                <Alert severity="info">
                    Dianalisis oleh <strong>{engine.name}</strong>. Metode: {engine.method}. Data utama: target, capaian, dan capaian terhadap target pada SS/IKSS.
                </Alert>

                <Grid container spacing={2}>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Capaian/Target Nasional" value={`${summary.average_achievement}%`} caption={`${summary.yearly_change > 0 ? '+' : ''}${summary.yearly_change} poin dari tahun lalu`} icon={<AutoGraphIcon />} /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Risiko Tinggi" value={summary.high_risk_satkers} caption="Satker perlu tindak lanjut segera" color="error" icon={<WarningAmberIcon />} /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Satker Membaik" value={summary.improving_satkers} caption="Naik lebih dari 2 poin" color="success" icon={<TrendingUpIcon />} /></Grid>
                    <Grid item xs={12} sm={6} lg={3}><MetricCard title="Satker Menurun" value={summary.declining_satkers} caption="Turun lebih dari 2 poin" color="warning" icon={<TrendingDownIcon />} /></Grid>
                </Grid>

                <Grid container spacing={2}>
                    <Grid item xs={12} lg={8}>
                        <Card variant="outlined"><CardHeader title="Tren Capaian terhadap Target Nasional" /><CardContent><Box sx={{ height: 300 }}><Line data={trendData} options={{ maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }} /></Box></CardContent></Card>
                    </Grid>
                    <Grid item xs={12} lg={4}>
                        <Card variant="outlined"><CardHeader title="Distribusi Risiko Satker" /><CardContent><Box sx={{ height: 300 }}><Bar data={riskData} options={{ maintainAspectRatio: false, plugins: { legend: { display: false } } }} /></Box></CardContent></Card>
                    </Grid>
                </Grid>

                <Card variant="outlined">
                    <CardHeader title="Insight dan Rekomendasi Python" subheader="Temuan otomatis yang paling relevan untuk keputusan admin" />
                    <CardContent><Stack spacing={1.5}>{insights.map((item) => <Alert key={item.title} severity={item.severity}><strong>{item.title}</strong><br />{item.description}<br /><Typography variant="caption"><strong>Tindakan:</strong> {item.action}</Typography></Alert>)}</Stack></CardContent>
                </Card>

                <Card variant="outlined"><CardHeader title="Satker Prioritas Tindak Lanjut" subheader="Diurutkan berdasarkan gap capaian terhadap target dan risiko BI" /><CardContent><PriorityTable rows={priorities} /></CardContent></Card>

                <Card variant="outlined">
                    <CardHeader title="Analisis IKSS" subheader="Target, capaian, dan capaian terhadap target per IKSS" />
                    <CardContent><DataGrid autoHeight rows={ikssRows} columns={ikssColumns} getRowId={(row) => row.id} pageSizeOptions={[10, 25]} initialState={{ pagination: { paginationModel: { pageSize: 10 } } }} disableRowSelectionOnClick /></CardContent>
                </Card>

                <Grid container spacing={2}>
                    <Grid item xs={12} lg={6}><Card variant="outlined"><CardHeader title="Performa Sasaran Strategis" /><CardContent><DataGrid autoHeight rows={strategicObjectives} columns={ssColumns} getRowId={(row) => row.id} hideFooter disableRowSelectionOnClick /></CardContent></Card></Grid>
                    <Grid item xs={12} lg={6}><Card variant="outlined"><CardHeader title="Performa Wilayah" /><CardContent><DataGrid autoHeight rows={regions} columns={regionColumns} getRowId={(row) => row.name} pageSizeOptions={[10, 25]} initialState={{ pagination: { paginationModel: { pageSize: 10 } } }} disableRowSelectionOnClick /></CardContent></Card></Grid>
                </Grid>
            </Stack>
        </AuthenticatedLayout>
    );
}
