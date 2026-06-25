import React, { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { DataGrid } from '@mui/x-data-grid';
import {
    Box, Button, Checkbox, Dialog, DialogActions, DialogContent, DialogTitle,
    FormControl, Grid, IconButton, InputLabel, ListItemText, MenuItem, Paper,
    Select, Stack, Tab, Tabs, TextField, Tooltip,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import SaveIcon from '@mui/icons-material/Save';

export default function MasterEditorTab({ tabs = [], year }) {
    const [active, setActive] = useState(tabs[0]?.key || '');
    const [dialog, setDialog] = useState({ open: false, mode: 'create', tab: null, id: null });
    const [form, setForm] = useState({});
    const tabMap = useMemo(() => Object.fromEntries(tabs.map((tab) => [tab.key, tab])), [tabs]);
    const selected = tabMap[active] || tabs[0];

    const emptyForm = (tab) => Object.fromEntries((tab?.fields || []).map((field) => [
        field.name,
        field.type === 'multiselect' ? [] : (field.default ?? ''),
    ]));
    const openCreate = () => {
        setForm(emptyForm(selected));
        setDialog({ open: true, mode: 'create', tab: selected, id: null });
    };
    const openEdit = (row) => {
        setForm({ ...emptyForm(selected), ...row });
        setDialog({ open: true, mode: 'edit', tab: selected, id: row[selected.idKey] });
    };
    const close = () => setDialog((current) => ({ ...current, open: false }));
    const submit = (event) => {
        event.preventDefault();
        const args = dialog.mode === 'edit' ? [dialog.tab.key, dialog.id] : [dialog.tab.key];
        router.post(route(dialog.mode === 'edit' ? dialog.tab.routes.update : dialog.tab.routes.store, args), { ...form, tahun: year }, {
            preserveScroll: true,
            onSuccess: close,
        });
    };
    const destroy = (row) => {
        if (confirm('Yakin ingin menghapus data ini? Data yang masih dipakai mungkin tidak dapat dihapus.')) {
            router.delete(route(selected.routes.destroy, [selected.key, row[selected.idKey]]), {
                data: { tahun: year },
                preserveScroll: true,
            });
        }
    };

    const renderField = (field) => {
        const value = form[field.name] ?? (field.type === 'multiselect' ? [] : '');
        if (field.type === 'select' || field.type === 'multiselect' || field.type === 'boolean') {
            const options = field.type === 'boolean'
                ? [{ value: 1, label: 'Ya / Berlaku' }, { value: 0, label: 'Tidak' }]
                : (field.options || []);
            return (
                <Grid item xs={12} md={field.md || 6} key={field.name}>
                    <FormControl fullWidth margin="normal" required={Boolean(field.required)}>
                        <InputLabel>{field.label}</InputLabel>
                        <Select
                            multiple={field.type === 'multiselect'}
                            value={value}
                            label={field.label}
                            renderValue={field.type === 'multiselect'
                                ? (selectedValues) => `${selectedValues.length} bukti dukung dipilih`
                                : undefined}
                            onChange={(event) => setForm({ ...form, [field.name]: event.target.value })}
                        >
                            {options.map((option) => (
                                <MenuItem key={option.value} value={option.value}>
                                    {field.type === 'multiselect' && <Checkbox checked={value.includes(String(option.value)) || value.includes(option.value)} />}
                                    <ListItemText primary={option.label} />
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
                    fullWidth margin="normal" label={field.label} required={Boolean(field.required)}
                    type={field.type || 'text'} multiline={Boolean(field.multiline)}
                    minRows={field.multiline ? 3 : undefined} value={value}
                    placeholder={field.placeholder || ''}
                    onChange={(event) => setForm({ ...form, [field.name]: event.target.value })}
                />
            </Grid>
        );
    };

    if (!selected) return null;
    const columns = [
        ...(selected.columns || []),
        {
            field: 'aksi', headerName: 'Aksi', width: 105, sortable: false,
            renderCell: ({ row }) => (
                <Stack direction="row">
                    <Tooltip title="Edit"><IconButton color="warning" onClick={() => openEdit(row)}><EditIcon /></IconButton></Tooltip>
                    <Tooltip title="Hapus"><IconButton color="error" onClick={() => destroy(row)}><DeleteIcon /></IconButton></Tooltip>
                </Stack>
            ),
        },
    ];

    return (
        <Box>
            <Paper variant="outlined" sx={{ mb: 2 }}>
                <Tabs value={selected.key} onChange={(_, value) => setActive(value)} variant="scrollable" scrollButtons="auto">
                    {tabs.map((tab) => <Tab key={tab.key} value={tab.key} label={tab.label} />)}
                </Tabs>
            </Paper>
            <Button variant="outlined" startIcon={<AddIcon />} onClick={openCreate} sx={{ mb: 2 }}>
                Tambah {selected.label}
            </Button>
            <DataGrid
                autoHeight disableRowSelectionOnClick rows={selected.rows || []} columns={columns}
                getRowId={(row) => row[selected.idKey]} pageSizeOptions={[10, 25, 50]}
                initialState={{ pagination: { paginationModel: { pageSize: 10 } } }}
                sx={{ border: 0, '& .MuiDataGrid-columnHeaders': { backgroundColor: '#f5f5f5' } }}
            />
            <Dialog open={dialog.open} onClose={close} maxWidth="lg" fullWidth>
                <Box component="form" onSubmit={submit}>
                    <DialogTitle>{dialog.mode === 'edit' ? 'Edit' : 'Tambah'} {dialog.tab?.label}</DialogTitle>
                    <DialogContent><Grid container spacing={2}>{(dialog.tab?.fields || []).map(renderField)}</Grid></DialogContent>
                    <DialogActions>
                        <Button onClick={close}>Batal</Button>
                        <Button type="submit" variant="contained" startIcon={<SaveIcon />}>Simpan</Button>
                    </DialogActions>
                </Box>
            </Dialog>
        </Box>
    );
}
