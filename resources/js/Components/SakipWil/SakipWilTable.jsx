import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { DataGrid } from '@mui/x-data-grid';
import {
    Box,
    Button,
    IconButton,
    InputAdornment,
    Link,
    Paper,
    TextField,
    Tooltip,
} from '@mui/material';
import CancelIcon from '@mui/icons-material/Cancel';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import DownloadIcon from '@mui/icons-material/Download';
import SearchIcon from '@mui/icons-material/Search';
import * as XLSX from 'xlsx';

const fileFields = [
    ['renstra', 'Renstra', 'sortedRenstraList'],
    ['iku', 'IKU', 'sortedIkuList'],
    ['renja', 'Renja', 'sortedRenjaList'],
    ['rkakl', 'RKAKL', 'sortedRkaklList'],
    ['dipa', 'DIPA', 'sortedDipaList'],
    ['renaksi', 'Renaksi', 'sortedRenaksiList'],
    ['pk', 'PK', 'sortedPkList'],
    ['lkjipTW1', 'LKJIP TW1', 'sortedLkjipTW1'],
    ['lkjipTW2', 'LKJIP TW2', 'sortedLkjipTW2'],
    ['lkjipTW3', 'LKJIP TW3', 'sortedLkjipTW3'],
    ['lkjipTW4', 'LKJIP TW4', 'sortedLkjipTW4'],
    ['rastaff', 'Rapat Staff', 'sortedRastaffList'],
    ['lhe', 'LHE AKIP', 'sortedLheList'],
    ['tlLheAkip', 'TL LHE AKIP', 'sortedTlLheAkipList'],
    ['monevRenaksi', 'Monev Renaksi', 'sortedMonevRenaksiList'],
];

const FileStatus = ({ filename, satkerId }) => {
    if (!filename) {
        return <CancelIcon color="error" fontSize="small" />;
    }

    return (
        <Tooltip title="Lihat dokumen">
            <IconButton
                component={Link}
                href={`/file/view/${satkerId}/${encodeURIComponent(filename)}`}
                target="_blank"
                rel="noopener noreferrer"
                size="small"
                color="success"
            >
                <CheckCircleIcon fontSize="small" />
            </IconButton>
        </Tooltip>
    );
};

export default function SakipWilTable({ data, filters = {}, perPageOptions = [10, 50], ...props }) {
    const [search, setSearch] = useState(filters.search || '');
    const rows = (data?.data || []).map((row, index) => ({
        ...row,
        no: (data.current_page - 1) * data.per_page + index + 1,
    }));

    const visit = (extra = {}) => {
        router.get(
            route('sakipwil'),
            {
                search,
                per_page: data?.per_page || filters.per_page || 10,
                page: data?.current_page || 1,
                ...extra,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const columns = [
        {
            field: 'no',
            headerName: 'No',
            width: 72,
            sortable: false,
        },
        {
            field: 'satkernama',
            headerName: 'Nama Satker',
            minWidth: 280,
            flex: 1,
            renderCell: (params) => (
                <Box sx={{ fontWeight: params.row.id_kejari == 0 ? 'bold' : 'normal' }}>
                    {(params.value || '').replace(/_/g, ' ')}
                </Box>
            ),
        },
        ...fileFields.map(([field, label, propName]) => ({
            field,
            headerName: label,
            width: 118,
            align: 'center',
            headerAlign: 'center',
            sortable: false,
            renderCell: (params) => (
                <FileStatus filename={props[propName]?.[params.row.id_satker]} satkerId={params.row.id_satker} />
            ),
        })),
    ];

    const handleExportExcel = () => {
        const exportRows = rows.map((row, index) => {
            const item = {
                No: row.no || index + 1,
                'Nama Satker': (row.satkernama || '').replace(/_/g, ' '),
            };

            fileFields.forEach(([, label, propName]) => {
                item[label] = props[propName]?.[row.id_satker] ? 'Ada' : 'Tidak Ada';
            });

            return item;
        });

        const worksheet = XLSX.utils.json_to_sheet(exportRows);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'SAKIP Wilayah');
        XLSX.writeFile(workbook, 'data_sakip_wilayah.xlsx');
    };

    return (
        <Paper sx={{ p: 2, overflow: 'hidden' }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 2 }}>
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
                />
                <Box sx={{ display: 'flex', gap: 1 }}>
                    <Button variant="contained" startIcon={<SearchIcon />} onClick={() => visit({ page: 1 })}>
                        Cari
                    </Button>
                    <Tooltip title="Export halaman ini ke Excel">
                        <Button variant="contained" color="success" onClick={handleExportExcel} startIcon={<DownloadIcon />}>
                            Export Excel
                        </Button>
                    </Tooltip>
                </Box>
            </Box>

            <DataGrid
                autoHeight
                disableRowSelectionOnClick
                rows={rows}
                columns={columns}
                getRowId={(row) => row.id_satker}
                rowCount={data?.total || 0}
                paginationMode="server"
                paginationModel={{
                    page: Math.max((data?.current_page || 1) - 1, 0),
                    pageSize: data?.per_page || 10,
                }}
                onPaginationModelChange={(model) => visit({ page: model.page + 1, per_page: model.pageSize })}
                pageSizeOptions={perPageOptions}
                sx={{
                    border: 0,
                    '& .MuiDataGrid-columnHeaders': { backgroundColor: 'primary.main', color: 'white' },
                }}
            />
        </Paper>
    );
}
