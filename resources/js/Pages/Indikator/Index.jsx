import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Container, Card, CardContent, Typography, TextField, InputAdornment,
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper,
    Box, Tab, Pagination, Chip
} from '@mui/material';
import { TabContext, TabList, TabPanel } from '@mui/lab';
import SearchIcon from '@mui/icons-material/Search';

export default function IndikatorIndex({ dataSastra, dataSaspro, filters }) {
    const [tabValue, setTabValue] = useState('1');
    const [search, setSearch] = useState(filters.search || '');

    // Handle Tab Change
    const handleTabChange = (event, newValue) => {
        setTabValue(newValue);
    };

    // Handle Search (Enter Key)
    const handleSearch = (e) => {
        if (e.key === 'Enter') {
            router.get(route('indikator.view'), { search }, { preserveState: true, replace: true });
        }
    };

    // Handle Pagination
    const handlePageChange = (url) => {
        if (url) {
            router.get(url, { search }, { preserveState: true, preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Data Indikator SAKIP" />

            <Container maxWidth="xl" sx={{ mt: 4, mb: 4 }}>
                <Card elevation={3}>
                    <CardContent>
                        <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
                            <Typography variant="h5" fontWeight="bold" color="primary">
                                Monitor Indikator Kinerja
                            </Typography>
                            
                            {/* Kolom Pencarian */}
                            <TextField
                                size="small"
                                placeholder="Cari indikator..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={handleSearch}
                                InputProps={{
                                    startAdornment: (
                                        <InputAdornment position="start">
                                            <SearchIcon />
                                        </InputAdornment>
                                    ),
                                }}
                                sx={{ width: 300 }}
                            />
                        </Box>

                        {/* TABS untuk Pindah View */}
                        <TabContext value={tabValue}>
                            <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
                                <TabList onChange={handleTabChange} aria-label="Indikator Tabs">
                                    <Tab label="Indikator Sastra" value="1" />
                                    <Tab label="Indikator Saspro" value="2" />
                                </TabList>
                            </Box>

                            {/* PANEL 1: Indikator Sastra */}
                            <TabPanel value="1" sx={{ px: 0 }}>
                                <TableContainer component={Paper} variant="outlined">
                                    <Table size="small">
                                        <TableHead sx={{ bgcolor: '#f5f5f5' }}>
                                            <TableRow>
                                                <TableCell width="15%"><strong>Kode Indikator</strong></TableCell>
                                                <TableCell width="35%"><strong>Nama Indikator</strong></TableCell>
                                                <TableCell width="10%"><strong>ID Sastra</strong></TableCell>
                                                <TableCell width="40%"><strong>Nama Sastra</strong></TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {dataSastra.data.length > 0 ? (
                                                dataSastra.data.map((row) => (
                                                    <TableRow key={row.kode_indikator} hover>
                                                        <TableCell>{row.kode_indikator}</TableCell>
                                                        <TableCell>{row.nama_indikator}</TableCell>
                                                        <TableCell>
                                                            <Chip label={row.id_sastra} size="small" color="primary" variant="outlined" />
                                                        </TableCell>
                                                        <TableCell>{row.nama_sastra}</TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={4} align="center">Tidak ada data ditemukan.</TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </TableContainer>
                                {/* Pagination Sastra */}
                                <Box mt={2} display="flex" justifyContent="center">
                                    <Pagination 
                                        count={dataSastra.last_page} 
                                        page={dataSastra.current_page} 
                                        onChange={(e, val) => handlePageChange(`${dataSastra.path}?sastra_page=${val}`)} 
                                        color="primary" 
                                    />
                                </Box>
                            </TabPanel>

                            {/* PANEL 2: Indikator Saspro */}
                            <TabPanel value="2" sx={{ px: 0 }}>
                                <TableContainer component={Paper} variant="outlined">
                                    <Table size="small">
                                        <TableHead sx={{ bgcolor: '#f5f5f5' }}>
                                            <TableRow>
                                                <TableCell><strong>Kode</strong></TableCell>
                                                <TableCell><strong>Nama Indikator Saspro</strong></TableCell>
                                                <TableCell><strong>Induk Saspro</strong></TableCell>
                                                <TableCell><strong>Induk Sastra</strong></TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {dataSaspro.data.length > 0 ? (
                                                dataSaspro.data.map((row) => (
                                                    <TableRow key={row.kode_indikator} hover>
                                                        <TableCell>{row.kode_indikator}</TableCell>
                                                        <TableCell>{row.nama_indikator}</TableCell>
                                                        <TableCell>
                                                            <Box>
                                                                <Chip label={row.id_saspro} size="small" color="secondary" sx={{mr:1, mb:0.5}} />
                                                                <Typography variant="caption" display="block">{row.nama_saspro}</Typography>
                                                            </Box>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Box>
                                                                <Chip label={row.id_sastra} size="small" color="primary" variant="outlined" sx={{mr:1}} />
                                                                <Typography variant="caption">{row.nama_sastra}</Typography>
                                                            </Box>
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={4} align="center">Tidak ada data ditemukan.</TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </TableContainer>
                                {/* Pagination Saspro */}
                                <Box mt={2} display="flex" justifyContent="center">
                                    <Pagination 
                                        count={dataSaspro.last_page} 
                                        page={dataSaspro.current_page} 
                                        onChange={(e, val) => handlePageChange(`${dataSaspro.path}?saspro_page=${val}`)} 
                                        color="secondary" 
                                    />
                                </Box>
                            </TabPanel>

                        </TabContext>
                    </CardContent>
                </Card>
            </Container>
        </AuthenticatedLayout>
    );
}