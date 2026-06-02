import React, { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { DataGrid } from '@mui/x-data-grid';
import {
    Box,
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    Grid,
    IconButton,
    InputAdornment,
    InputLabel,
    MenuItem,
    Paper,
    Select,
    Stack,
    Tab,
    Tabs,
    TextField,
    Tooltip,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import SearchIcon from '@mui/icons-material/Search';
import SaveIcon from '@mui/icons-material/Save';

export default function SastraSasproTab({ tabs = [], filters = {}, perPageOptions = [10, 25, 50], canManage = false }) {
    const firstTab = tabs[0]?.key || '';
    const [activeTab, setActiveTab] = useState(firstTab);
    const [search, setSearch] = useState(filters.pohon_search || filters.search || '');
    const [dialog, setDialog] = useState({ open: false, mode: 'create', tab: null, id: null });
    const [form, setForm] = useState({});

    const tabMap = useMemo(() => Object.fromEntries(tabs.map((tab) => [tab.key, tab])), [tabs]);
    const selectedTab = tabMap[activeTab] || tabs[0];

    const makeEmptyForm = (tab) => Object.fromEntries((tab?.fields || []).map((field) => [field.name, field.default ?? '']));

    const submitSearch = (extra = {}) => {
        router.get(
            route('keloladata'),
            { tab: 'sastra-saspro', pohon_search: search, ...extra },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const openCreate = (tab) => {
        setForm(makeEmptyForm(tab));
        setDialog({ open: true, mode: 'create', tab, id: null });
    };

    const openEdit = (tab, row) => {
        setForm({ ...makeEmptyForm(tab), ...row });
        setDialog({ open: true, mode: 'edit', tab, id: row[tab.idKey] });
    };

    const submitForm = (event) => {
        event.preventDefault();

        const tab = dialog.tab;
        const routeName = dialog.mode === 'edit' ? tab.routes.update : tab.routes.store;
        const routeArgs = dialog.mode === 'edit' ? [dialog.id] : [];

        router.post(route(routeName, ...routeArgs), form, {
            preserveScroll: true,
            onSuccess: () => setDialog((value) => ({ ...value, open: false })),
        });
    };

    const deleteRow = (tab, id) => {
        if (!confirm('Yakin ingin menghapus data ini?')) {
            return;
        }

        router.delete(route(tab.routes.destroy, id), { preserveScroll: true });
    };

    const columnsFor = (tab) => {
        const columns = [...(tab.columns || [])];

        if (canManage) {
            columns.push({
                field: 'aksi',
                headerName: 'Aksi',
                width: 96,
                sortable: false,
                filterable: false,
                renderCell: (params) => (
                    <Stack direction="row" spacing={0.5}>
                        <Tooltip title="Edit">
                            <IconButton size="small" color="warning" onClick={() => openEdit(tab, params.row)}>
                                <EditIcon fontSize="small" />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Hapus">
                            <IconButton size="small" color="error" onClick={() => deleteRow(tab, params.id)}>
                                <DeleteIcon fontSize="small" />
                            </IconButton>
                        </Tooltip>
                    </Stack>
                ),
            });
        }

        return columns;
    };

    const changeGridPage = (tab, model) => {
        submitSearch({
            [tab.pageKey]: model.page + 1,
            per_page: model.pageSize,
        });
    };

    const renderField = (field) => {
        const commonProps = {
            fullWidth: true,
            margin: 'normal',
            label: field.label,
            required: Boolean(field.required),
            disabled: dialog.mode === 'edit' && Boolean(field.disabledOnEdit),
        };

        if (field.type === 'select') {
            return (
                <Grid item xs={12} md={field.md || 6} key={field.name}>
                    <FormControl {...commonProps}>
                        <InputLabel>{field.label}</InputLabel>
                        <Select
                            value={form[field.name] ?? ''}
                            label={field.label}
                            onChange={(event) => setForm({ ...form, [field.name]: event.target.value })}
                        >
                            {(field.options || []).map((option) => (
                                <MenuItem key={option.value} value={option.value}>
                                    {option.label}
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                </Grid>
            );
        }

        return (
            <Grid item xs={12} md={field.md || 6} key={field.name}>
                <TextField
                    {...commonProps}
                    value={form[field.name] ?? ''}
                    type={field.type || 'text'}
                    multiline={Boolean(field.multiline)}
                    rows={field.multiline ? 3 : undefined}
                    onChange={(event) => setForm({ ...form, [field.name]: event.target.value })}
                />
            </Grid>
        );
    };

    if (!selectedTab) {
        return null;
    }

    const paginator = selectedTab.rows || {};

    return (
        <Box>
            <Paper variant="outlined" sx={{ mb: 2 }}>
                <Tabs value={selectedTab.key} onChange={(event, value) => setActiveTab(value)} variant="scrollable" scrollButtons="auto">
                    {tabs.map((tab) => (
                        <Tab key={tab.key} label={tab.label} value={tab.key} />
                    ))}
                </Tabs>
            </Paper>

            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5} sx={{ mb: 2 }} alignItems={{ xs: 'stretch', md: 'center' }}>
                <TextField
                    size="small"
                    placeholder="Cari kode atau nama"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    onKeyDown={(event) => event.key === 'Enter' && submitSearch({ [selectedTab.pageKey]: 1 })}
                    InputProps={{
                        startAdornment: (
                            <InputAdornment position="start">
                                <SearchIcon />
                            </InputAdornment>
                        ),
                    }}
                    sx={{ minWidth: { md: 360 } }}
                />
                <Button variant="contained" startIcon={<SearchIcon />} onClick={() => submitSearch({ [selectedTab.pageKey]: 1 })}>
                    Cari
                </Button>
                {canManage && (
                    <Button variant="outlined" startIcon={<AddIcon />} onClick={() => openCreate(selectedTab)}>
                        {selectedTab.addLabel}
                    </Button>
                )}
            </Stack>

            <DataGrid
                autoHeight
                disableRowSelectionOnClick
                rows={paginator.data || []}
                columns={columnsFor(selectedTab)}
                getRowId={(row) => row[selectedTab.idKey]}
                rowCount={paginator.total || 0}
                paginationMode="server"
                paginationModel={{
                    page: Math.max((paginator.current_page || 1) - 1, 0),
                    pageSize: paginator.per_page || 10,
                }}
                onPaginationModelChange={(model) => changeGridPage(selectedTab, model)}
                pageSizeOptions={perPageOptions}
                sx={{
                    border: 0,
                    '& .MuiDataGrid-columnHeaders': { backgroundColor: '#f5f5f5' },
                }}
            />

            <Dialog open={dialog.open} onClose={() => setDialog((value) => ({ ...value, open: false }))} maxWidth="md" fullWidth>
                <Box component="form" onSubmit={submitForm}>
                    <DialogTitle>{dialog.mode === 'edit' ? 'Edit' : 'Tambah'} {dialog.tab?.label}</DialogTitle>
                    <DialogContent>
                        <Grid container spacing={2}>
                            {(dialog.tab?.fields || []).map(renderField)}
                        </Grid>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setDialog((value) => ({ ...value, open: false }))}>Batal</Button>
                        <Button type="submit" variant="contained" startIcon={<SaveIcon />}>
                            Simpan
                        </Button>
                    </DialogActions>
                </Box>
            </Dialog>
        </Box>
    );
}
