import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Box,
    Button,
    Chip,
    InputAdornment,
    Pagination,
    Paper,
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
import SearchIcon from '@mui/icons-material/Search';

const lingkupOptions = {
    0: 'Semua Satker',
    1: 'Pusat',
    2: 'Kejati',
    3: 'Kejari',
    4: 'Cabjari',
    5: 'Kejati, Kejari',
    6: 'Kejari, Cabjari',
    7: 'Kejati, Kejari, Cabjari',
};

export default function SastraSasproTab({ dataSastra, dataSaspro, filters = {} }) {
    const [tab, setTab] = useState('sastra');
    const [search, setSearch] = useState(filters.search || '');

    const sastraRows = dataSastra?.data || [];
    const sasproRows = dataSaspro?.data || [];

    const submitSearch = (extra = {}) => {
        router.get(
            route('keloladata'),
            { tab: 'sastra-saspro', search, ...extra },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const handleSearchKey = (event) => {
        if (event.key === 'Enter') {
            submitSearch();
        }
    };

    const handlePageChange = (pageKey, page) => {
        submitSearch({ [pageKey]: page });
    };

    const lingkupLabel = (value) => {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        return lingkupOptions[value] || value;
    };

    return (
        <Box>
            <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap', alignItems: 'center', mb: 2 }}>
                <TextField
                    size="small"
                    placeholder="Cari kode, nama, link, lingkup..."
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    onKeyDown={handleSearchKey}
                    InputProps={{
                        startAdornment: (
                            <InputAdornment position="start">
                                <SearchIcon />
                            </InputAdornment>
                        ),
                    }}
                    sx={{ minWidth: { xs: '100%', md: 360 } }}
                />
                <Button variant="contained" startIcon={<SearchIcon />} onClick={() => submitSearch()}>
                    Cari
                </Button>
            </Box>

            <Paper variant="outlined" sx={{ mb: 2 }}>
                <Tabs value={tab} onChange={(event, value) => setTab(value)} variant="scrollable" scrollButtons="auto">
                    <Tab label="Indikator Sastra" value="sastra" />
                    <Tab label="Indikator Saspro" value="saspro" />
                </Tabs>
            </Paper>

            {tab === 'sastra' && (
                <Box>
                    <Typography variant="h6" fontWeight="bold" gutterBottom>
                        Indikator Sastra
                    </Typography>
                    <TableContainer component={Paper} variant="outlined">
                        <Table size="small">
                            <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                                <TableRow>
                                    <TableCell>Kode</TableCell>
                                    <TableCell>Indikator</TableCell>
                                    <TableCell>Sastra</TableCell>
                                    <TableCell>Link/Rumpun</TableCell>
                                    <TableCell>Lingkup</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {sastraRows.map((row) => (
                                    <TableRow key={row.kode_indikator} hover>
                                        <TableCell>{row.kode_indikator}</TableCell>
                                        <TableCell>{row.nama_indikator}</TableCell>
                                        <TableCell>
                                            <Box>
                                                <Chip label={row.id_sastra} size="small" color="primary" variant="outlined" sx={{ mr: 1, mb: 0.5 }} />
                                                <Typography variant="caption" display="block">{row.nama_sastra}</Typography>
                                            </Box>
                                        </TableCell>
                                        <TableCell>{row.link ?? '-'}</TableCell>
                                        <TableCell>{lingkupLabel(row.lingkup)}</TableCell>
                                    </TableRow>
                                ))}
                                {sastraRows.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} align="center">Tidak ada data ditemukan.</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                    {dataSastra?.last_page > 1 && (
                        <Stack sx={{ mt: 2, alignItems: 'center' }}>
                            <Pagination
                                count={dataSastra.last_page}
                                page={dataSastra.current_page}
                                onChange={(event, page) => handlePageChange('sastra_page', page)}
                                color="primary"
                            />
                        </Stack>
                    )}
                </Box>
            )}

            {tab === 'saspro' && (
                <Box>
                    <Typography variant="h6" fontWeight="bold" gutterBottom>
                        Indikator Saspro
                    </Typography>
                    <TableContainer component={Paper} variant="outlined">
                        <Table size="small">
                            <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                                <TableRow>
                                    <TableCell>Kode</TableCell>
                                    <TableCell>Sastra</TableCell>
                                    <TableCell>Saspro</TableCell>
                                    <TableCell>Indikator</TableCell>
                                    <TableCell>Link/Rumpun</TableCell>
                                    <TableCell>Lingkup</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {sasproRows.map((row) => (
                                    <TableRow key={row.kode_indikator} hover>
                                        <TableCell>{row.kode_indikator}</TableCell>
                                        <TableCell>
                                            <Box>
                                                <Chip label={row.id_sastra} size="small" color="primary" variant="outlined" sx={{ mr: 1, mb: 0.5 }} />
                                                <Typography variant="caption" display="block">{row.nama_sastra}</Typography>
                                            </Box>
                                        </TableCell>
                                        <TableCell>
                                            <Box>
                                                <Chip label={row.id_saspro} size="small" color="secondary" sx={{ mr: 1, mb: 0.5 }} />
                                                <Typography variant="caption" display="block">{row.nama_saspro}</Typography>
                                            </Box>
                                        </TableCell>
                                        <TableCell>{row.nama_indikator}</TableCell>
                                        <TableCell>{row.link ?? '-'}</TableCell>
                                        <TableCell>{lingkupLabel(row.lingkup)}</TableCell>
                                    </TableRow>
                                ))}
                                {sasproRows.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} align="center">Tidak ada data ditemukan.</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                    {dataSaspro?.last_page > 1 && (
                        <Stack sx={{ mt: 2, alignItems: 'center' }}>
                            <Pagination
                                count={dataSaspro.last_page}
                                page={dataSaspro.current_page}
                                onChange={(event, page) => handlePageChange('saspro_indikator_page', page)}
                                color="secondary"
                            />
                        </Stack>
                    )}
                </Box>
            )}
        </Box>
    );
}
