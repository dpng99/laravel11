// resources/js/Components/Monitoring/CapaianSaspro.jsx
import React, { useState, useEffect } from 'react';
import { Box, CircularProgress, Typography, Alert, Tabs, Tab, Paper, TableContainer, Table, TableHead, TableBody, TableRow, TableCell } from '@mui/material';
import axios from 'axios';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

// Komponen helper untuk Panel Tab
function TabPanel(props) {
    const { children, value, index, ...other } = props;
    return (
        <div role="tabpanel" hidden={value !== index} id={`saspro-tabpanel-${index}`} {...other}>
            {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
        </div>
    );
}

// Komponen helper untuk Bar Chart
function SasproBarChart({ saspro }) {
    const labels = saspro.indikators.map(ind => ind.nama);
    
    // Fungsi untuk memecah label panjang (dari Blade Anda)
    const wrapLabels = (label) => {
        const words = label.split(' ');
        const lines = [];
        let line = '';
        words.forEach((word) => {
            if ((line + ' ' + word).trim().split(' ').length <= Math.ceil(words.length / 4)) {
                line = (line + ' ' + word).trim();
            } else {
                lines.push(line);
                line = word;
            }
        });
        if(line) lines.push(line);
        return lines;
    };
    
    const data = {
        labels: labels.map(l => wrapLabels(l)), // Terapkan pembungkus label
        datasets: [
            { label: 'TW1', data: saspro.indikators.map(ind => ind.capaian_tw1 ?? 0), backgroundColor: 'rgba(54, 162, 235, 0.6)' },
            { label: 'TW2', data: saspro.indikators.map(ind => ind.capaian_tw2 ?? 0), backgroundColor: 'rgba(255, 206, 86, 0.6)' },
            { label: 'TW3', data: saspro.indikators.map(ind => ind.capaian_tw3 ?? 0), backgroundColor: 'rgba(75, 192, 192, 0.6)' },
            { label: 'TW4', data: saspro.indikators.map(ind => ind.capaian_tw4 ?? 0), backgroundColor: 'rgba(255, 99, 132, 0.6)' },
        ]
    };
    const options = {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: `Capaian ${saspro.nama_saspro}`, font: { size: 20, weight: 'bold' } }
        },
        scales: {
            y: { beginAtZero: false, min: 0, max: 100 },
            x: { ticks: { font: { size: 14 } } }
        }
    };
    return <Bar options={options} data={data} height={100} />;
}

// Helper untuk format %
const showVal = (v) => (v !== null && v !== undefined) ? `${v}%` : '-';

export default function CapaianSaspro() {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState(0); // Gunakan index 0 sebagai default

    useEffect(() => {
        axios.get('/monitoring/capaian-saspro-all') // URL dari Blade
            .then(response => {
                setData(response.data);
                setLoading(false);
            })
            .catch(err => {
                console.error("Gagal memuat data saspro:", err);
                setError("Gagal memuat data Sasaran Strategis.");
                setLoading(false);
            });
    }, []); // Hanya jalan sekali saat mount

    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
    };

    if (loading) return <CircularProgress />;
    if (error) return <Alert severity="error">{error}</Alert>;
    if (data.length === 0) return <Alert severity="warning">Tidak ada data</Alert>;

    return (
        <Box>
            {/* Navigasi Tab */}
            <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
                <Tabs value={activeTab} onChange={handleTabChange} variant="scrollable" scrollButtons="auto">
                    {data.map((saspro, i) => (
                        <Tab label={`Sasaran Strategis ${i + 1}`} key={saspro.id_saspro} id={`tab-${saspro.id_saspro}`} />
                    ))}
                </Tabs>
            </Box>

            {/* Konten Tab */}
            {data.map((saspro, i) => (
                <TabPanel value={activeTab} index={i} key={saspro.id_saspro}>
                    <Typography variant="h4" sx={{ mt: 3, fontWeight: 'bold' }}>{saspro.nama_saspro}</Typography>
                    
                    {/* Tabel */}
                    <TableContainer component={Paper} sx={{ my: 2 }}>
                        <Table size="small">
                            <TableHead sx={{ backgroundColor: '#f0bb49' }}>
                                <TableRow>
                                    <TableCell rowSpan={2}>No</TableCell>
                                    <TableCell rowSpan={2}>Nama Indikator</TableCell>
                                    <TableCell rowSpan={2}>Target</TableCell>
                                    <TableCell colSpan={2}>Triwulan 1</TableCell>
                                    <TableCell colSpan={2}>Triwulan 2</TableCell>
                                    <TableCell colSpan={2}>Triwulan 3</TableCell>
                                    <TableCell colSpan={2}>Triwulan 4</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell>Capaian</TableCell>
                                    <TableCell>Capaian thd Target</TableCell>
                                    <TableCell>Capaian</TableCell>
                                    <TableCell>Capaian thd Target</TableCell>
                                    <TableCell>Capaian</TableCell>
                                    <TableCell>Capaian thd Target</TableCell>
                                    <TableCell>Capaian</TableCell>
                                    <TableCell>Capaian thd Target</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {saspro.indikators.map((ind, j) => (
                                    <TableRow key={j}>
                                        <TableCell>{j + 1}</TableCell>
                                        <TableCell>{ind.nama}</TableCell>
                                        <TableCell align="center">{ind.target_tw1 || 0}%</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_tw1)}</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_terhadap_target_tw1)}</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_tw2)}</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_terhadap_target_tw2)}</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_tw3)}</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_terhadap_target_tw3)}</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_tw4)}</TableCell>
                                        <TableCell align="center">{showVal(ind.capaian_terhadap_target_tw4)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TableContainer>
                    
                    {/* Chart */}
                    <Box sx={{ mb: 4 }}>
                        <SasproBarChart saspro={saspro} />
                    </Box>
                </TabPanel>
            ))}
        </Box>
    );
}