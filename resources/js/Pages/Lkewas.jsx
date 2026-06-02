// resources/js/Pages/LkeWas/Index.jsx
import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { DataGrid } from '@mui/x-data-grid';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

import {
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    InputAdornment,
    TextField,
    Typography,
} from '@mui/material';
import InfoIcon from '@mui/icons-material/Info';
import SearchIcon from '@mui/icons-material/Search';

export default function Lkewas() {
    const { list_kejari, tahun, filters = {}, perPageOptions = [10, 50] } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');

    const rows = (list_kejari?.data || []).map((row, index) => ({
        ...row,
        no: (list_kejari.current_page - 1) * list_kejari.per_page + index + 1,
    }));

    const visit = (extra = {}) => {
        router.get(
            route('lke_was'),
            {
                search,
                per_page: list_kejari?.per_page || filters.per_page || 10,
                page: list_kejari?.current_page || 1,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const columns = [
        { field: 'no', headerName: 'No', width: 80, sortable: false },
        {
            field: 'satkernama',
            headerName: 'Nama Satuan Kerja',
            flex: 1,
            minWidth: 260,
            renderCell: (params) => (params.value || '').replace(/_/g, ' '),
        },
        {
            field: 'aksi',
            headerName: 'Aksi',
            width: 150,
            align: 'center',
            headerAlign: 'center',
            sortable: false,
            filterable: false,
            renderCell: (params) => (
                <Button
                    component={Link}
                    href={`/was-lke/${params.row.id_satker}`}
                    variant="contained"
                    color="info"
                    size="small"
                    startIcon={<InfoIcon />}
                >
                    Detail
                </Button>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="LKE Pengawasan" />

            <Card elevation={3}>
                <CardHeader
                    title={`Bukti Dukung LKE AKIP Internal Tahun ${tahun}`}
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    <Typography paragraph>
                        Halaman ini digunakan untuk melihat dokumen/bukti dukung setiap satuan kerja sebagaimana tercantum pada Lembar Kerja Evaluasi (LKE) AKIP.
                    </Typography>

                    <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap', mb: 2 }}>
                        <TextField
                            label="Cari Nama/ID Satker"
                            variant="outlined"
                            size="small"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            onKeyDown={(event) => event.key === 'Enter' && visit({ page: 1 })}
                            InputProps={{
                                startAdornment: (
                                    <InputAdornment position="start">
                                        <SearchIcon />
                                    </InputAdornment>
                                ),
                            }}
                            sx={{ minWidth: { xs: '100%', md: 320 } }}
                        />
                        <Button variant="contained" startIcon={<SearchIcon />} onClick={() => visit({ page: 1 })}>
                            Cari
                        </Button>
                    </Box>

                    <DataGrid
                        autoHeight
                        disableRowSelectionOnClick
                        rows={rows}
                        columns={columns}
                        getRowId={(row) => row.id_satker}
                        rowCount={list_kejari?.total || 0}
                        paginationMode="server"
                        paginationModel={{
                            page: Math.max((list_kejari?.current_page || 1) - 1, 0),
                            pageSize: list_kejari?.per_page || 10,
                        }}
                        onPaginationModelChange={(model) => visit({ page: model.page + 1, per_page: model.pageSize })}
                        pageSizeOptions={perPageOptions}
                        sx={{
                            border: 0,
                            '& .MuiDataGrid-columnHeaders': { backgroundColor: '#f0bb49' },
                        }}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
