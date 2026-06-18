import React, { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import * as XLSX from 'xlsx';
import {
    Alert, Box, Button, Checkbox, Dialog, DialogActions, DialogContent, DialogTitle,
    FormControl, Grid, IconButton, InputLabel, ListItemText, MenuItem, Paper, Select,
    Stack, Tab, Tabs, TextField, Typography,
} from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import DownloadIcon from '@mui/icons-material/Download';
import EditIcon from '@mui/icons-material/Edit';
import SaveIcon from '@mui/icons-material/Save';
import UploadFileIcon from '@mui/icons-material/UploadFile';

const choices = {
    group_type: ['table', 'section', 'list', 'narrative'],
    parameter_role: ['input', 'component', 'numerator', 'denominator', 'result', 'context', 'narrative'],
    input_mode: ['scalar', 'list', 'table'],
    source_type: ['manual', 'legacy', 'target', 'system', 'formula'],
    value_type: ['number', 'integer', 'percentage', 'currency', 'boolean', 'text'],
    period_type: ['monthly', 'quarterly', 'annual'],
    calculation_method: ['input', 'sum', 'average', 'weighted_average', 'ratio', 'percentage', 'min', 'max', 'latest'],
    aggregation_method: ['sum', 'average', 'weighted_average', 'ratio', 'percentage', 'min', 'max', 'latest'],
    aggregation_scope: ['children', 'self_and_children'],
    dependency_role: ['component', 'numerator', 'denominator', 'weight'],
};
const booleanFields = ['is_active', 'is_result', 'is_required', 'include_in_report'];
const groupSpreadsheetFields = [
    'ikss_id', 'code', 'name', 'parent_code', 'description', 'section_code',
    'group_type', 'settings_json', 'sort_order', 'is_active',
];
const parameterSpreadsheetFields = [
    'ikss_id', 'code', 'name', 'group_code', 'parent_code', 'description',
    'parameter_role', 'input_mode', 'source_type', 'source_reference', 'legacy_indicator_id',
    'value_type', 'unit', 'period_type', 'calculation_method', 'aggregation_method',
    'aggregation_scope', 'entry_levels', 'aggregate_to_levels', 'formula_config_json',
    'decimal_places', 'sort_order', 'is_result', 'is_required', 'include_in_report',
    'is_active', 'valid_from_year', 'valid_until_year',
];
const parameterDefaults = {
    parameter_role: 'input', input_mode: 'scalar', source_type: 'manual', value_type: 'number',
    period_type: 'quarterly', calculation_method: 'input', aggregation_method: 'sum',
    aggregation_scope: 'children', decimal_places: 2, sort_order: 0, is_result: 0,
    is_required: 0, include_in_report: 1, is_active: 1, entry_levels: [], aggregate_to_levels: [],
    dependencies: [], formula_config_json: '',
};

export default function IkssMasterTab({ data = {} }) {
    const [active, setActive] = useState('parameters');
    const [dialog, setDialog] = useState({ open: false, mode: 'create', type: 'parameter', id: null });
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [templateDownloaded, setTemplateDownloaded] = useState(false);
    const [importError, setImportError] = useState('');
    const [form, setForm] = useState({});
    const parameters = data.parameters || [];
    const groups = data.groups || [];
    const parameterOptions = useMemo(() => parameters.map((row) => ({ value: row.id, label: `${row.ikss_id} / ${row.code} - ${row.name}` })), [parameters]);
    const groupOptions = groups.map((row) => ({ value: row.id, label: `${row.code} - ${row.name}` }));
    const ikssOptions = data.ikssOptions || [];

    if (!data.available) {
        return <Alert severity="warning">Tabel parameter IKSS belum tersedia. Jalankan migration IKSS agar editor dapat digunakan.</Alert>;
    }

    const open = (type, row = null) => {
        const defaults = type === 'parameter'
            ? parameterDefaults
            : { group_type: 'table', sort_order: 0, is_active: 1, settings_json: '' };
        setForm({ ...defaults, ...(row || {}) });
        setDialog({ open: true, mode: row ? 'edit' : 'create', type, id: row?.id || null });
    };
    const close = () => setDialog((current) => ({ ...current, open: false }));
    const save = (event) => {
        event.preventDefault();
        const base = dialog.type === 'parameter' ? 'keloladata.ikss.parameters' : 'keloladata.ikss.groups';
        router.post(route(`${base}.${dialog.mode === 'edit' ? 'update' : 'store'}`, dialog.mode === 'edit' ? dialog.id : undefined), form, {
            preserveScroll: true, onSuccess: close,
        });
    };
    const destroy = (type, id) => {
        if (confirm('Yakin ingin menghapus definisi ini?')) {
            router.delete(route(`keloladata.ikss.${type}s.destroy`, id), { preserveScroll: true });
        }
    };
    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));
    const selectField = (name, label, options, multiple = false, md = 6) => (
        <Grid item xs={12} md={md} key={name}>
            <FormControl fullWidth margin="normal">
                <InputLabel>{label}</InputLabel>
                <Select multiple={multiple} value={form[name] ?? (multiple ? [] : '')} label={label}
                    renderValue={multiple ? (values) => values.join(', ') : undefined}
                    onChange={(event) => update(name, event.target.value)}>
                    {options.map((option) => {
                        const item = typeof option === 'object' ? option : { value: option, label: option };
                        return <MenuItem key={item.value} value={item.value}>
                            {multiple && <Checkbox checked={(form[name] || []).includes(item.value)} />}
                            <ListItemText primary={item.label} />
                        </MenuItem>;
                    })}
                </Select>
            </FormControl>
        </Grid>
    );
    const textField = (name, label, { multiline = false, type = 'text', md = 6 } = {}) => (
        <Grid item xs={12} md={md} key={name}>
            <TextField fullWidth margin="normal" label={label} type={type} multiline={multiline}
                minRows={multiline ? 3 : undefined} value={form[name] ?? ''}
                onChange={(event) => update(name, event.target.value)} />
        </Grid>
    );
    const addDependency = () => update('dependencies', [...(form.dependencies || []), { source_parameter_id: '', role: 'component', weight: '', sort_order: (form.dependencies || []).length }]);
    const sourceParameterById = useMemo(
        () => Object.fromEntries(parameters.map((row) => [row.id, row])),
        [parameters],
    );
    const cleanSpreadsheetRow = (row, fields) => Object.fromEntries(fields.map((field) => {
        const value = row[field];
        if (Array.isArray(value)) return [field, value.join(',')];
        if (value && typeof value === 'object') return [field, JSON.stringify(value)];
        return [field, value ?? ''];
    }));
    const setSheetWidths = (sheet, fields) => {
        sheet['!cols'] = fields.map((field) => ({ wch: Math.max(14, Math.min(45, field.length + 8)) }));
        sheet['!autofilter'] = { ref: sheet['!ref'] };
    };
    const downloadSpreadsheetTemplate = () => {
        const groupRows = groups.map((row) => {
            const parent = groups.find((item) => item.id === row.parent_id);
            return cleanSpreadsheetRow({ ...row, parent_code: parent?.code || '' }, groupSpreadsheetFields);
        });
        const parameterRows = parameters.map((row) => {
            const group = groups.find((item) => item.id === row.group_id);
            const parent = sourceParameterById[row.parent_id];
            return cleanSpreadsheetRow({ ...row, group_code: group?.code || '', parent_code: parent?.code || '' }, parameterSpreadsheetFields);
        });
        const relationRows = parameters.flatMap((row) => (row.dependencies || []).map((dependency) => {
            const source = sourceParameterById[dependency.source_parameter_id];
            return {
                ikss_id: row.ikss_id,
                parameter_code: row.code,
                source_ikss_id: source?.ikss_id || row.ikss_id,
                source_code: source?.code || '',
                role: dependency.role,
                weight: dependency.weight ?? '',
                sort_order: dependency.sort_order ?? 0,
            };
        }));
        const instructions = [
            { langkah: 1, petunjuk: 'Edit data pada sheet Kelompok, Parameter, dan Relasi. Jangan mengubah nama sheet atau judul kolom.' },
            { langkah: 2, petunjuk: 'Kolom code menjadi kunci upsert. Kode yang sama diperbarui, kode baru ditambahkan.' },
            { langkah: 3, petunjuk: 'entry_levels dan aggregate_to_levels diisi angka dipisahkan koma, contoh: 1,2,3,4.' },
            { langkah: 4, petunjuk: 'settings_json dan formula_config_json harus berupa JSON valid atau dikosongkan.' },
            { langkah: 5, petunjuk: 'Relasi parameter diatur pada sheet Relasi menggunakan parameter_code dan source_code.' },
        ];
        const references = Object.entries(choices).flatMap(([field, values]) => values.map((value) => ({ field, nilai_diizinkan: value })));
        const workbook = XLSX.utils.book_new();
        [
            ['Petunjuk', instructions, ['langkah', 'petunjuk']],
            ['Kelompok', groupRows, groupSpreadsheetFields],
            ['Parameter', parameterRows, parameterSpreadsheetFields],
            ['Relasi', relationRows, ['ikss_id', 'parameter_code', 'source_ikss_id', 'source_code', 'role', 'weight', 'sort_order']],
            ['Referensi', references, ['field', 'nilai_diizinkan']],
        ].forEach(([name, rows, fields]) => {
            const sheet = XLSX.utils.json_to_sheet(rows, { header: fields });
            setSheetWidths(sheet, fields);
            XLSX.utils.book_append_sheet(workbook, sheet, name);
        });
        XLSX.writeFile(workbook, `template-katalog-ikss-${new Date().toISOString().slice(0, 10)}.xlsx`);
        setTemplateDownloaded(true);
        setImportError('');
    };
    const parseList = (value) => String(value ?? '').split(',').map((item) => Number(item.trim())).filter((item) => Number.isInteger(item));
    const numberValue = (value, fallback = 0) => value === '' || value === null || value === undefined ? fallback : Number(value);
    const parseJson = (value, field, rowNumber) => {
        if (value === null || value === undefined || String(value).trim() === '') return null;
        try {
            return typeof value === 'object' ? value : JSON.parse(value);
        } catch {
            throw new Error(`${field} pada baris ${rowNumber} bukan JSON yang valid.`);
        }
    };
    const parseSpreadsheet = async (file) => {
        const workbook = XLSX.read(await file.arrayBuffer(), { type: 'array' });
        for (const name of ['Kelompok', 'Parameter', 'Relasi']) {
            if (!workbook.Sheets[name]) throw new Error(`Sheet "${name}" tidak ditemukan. Gunakan template yang telah diunduh.`);
        }
        const sheetRows = (name) => XLSX.utils.sheet_to_json(workbook.Sheets[name], { defval: '', raw: true });
        const groupsPayload = sheetRows('Kelompok').filter((row) => row.code || row.name).map((row, index) => ({
            ...row,
            settings: parseJson(row.settings_json, 'settings_json', index + 2),
            ikss_id: row.ikss_id || null,
            sort_order: numberValue(row.sort_order),
            is_active: numberValue(row.is_active, 1),
        }));
        const parametersPayload = sheetRows('Parameter').filter((row) => row.code || row.name).map((row, index) => ({
            ...row,
            entry_levels: parseList(row.entry_levels),
            aggregate_to_levels: parseList(row.aggregate_to_levels),
            formula_config: parseJson(row.formula_config_json, 'formula_config_json', index + 2),
            decimal_places: numberValue(row.decimal_places, 2),
            sort_order: numberValue(row.sort_order),
            is_result: numberValue(row.is_result),
            is_required: numberValue(row.is_required),
            include_in_report: numberValue(row.include_in_report, 1),
            is_active: numberValue(row.is_active, 1),
            valid_from_year: row.valid_from_year === '' ? null : Number(row.valid_from_year),
            valid_until_year: row.valid_until_year === '' ? null : Number(row.valid_until_year),
            dependencies: [],
        }));
        const parameterMap = Object.fromEntries(parametersPayload.map((row) => [`${row.ikss_id}::${row.code}`, row]));
        sheetRows('Relasi').filter((row) => row.parameter_code || row.source_code).forEach((row, index) => {
            const parameter = parameterMap[`${row.ikss_id}::${row.parameter_code}`];
            if (!parameter) throw new Error(`Parameter tujuan relasi pada baris ${index + 2} tidak ditemukan.`);
            parameter.dependencies.push({
                source_ikss_id: row.source_ikss_id || row.ikss_id,
                source_code: row.source_code,
                role: row.role || 'component',
                weight: row.weight === '' ? null : Number(row.weight),
                sort_order: numberValue(row.sort_order),
            });
        });
        return { format: 'sicana-ikss-catalog', version: 1, groups: groupsPayload, parameters: parametersPayload };
    };
    const importCatalog = async (event) => {
        event.preventDefault();
        if (!importFile) return;
        setImportError('');
        let payload;
        try {
            payload = await parseSpreadsheet(importFile);
        } catch (error) {
            setImportError(error.message || 'File Excel tidak dapat dibaca.');
            return;
        }
        const jsonFile = new File([JSON.stringify(payload)], 'katalog-ikss.json', { type: 'application/json' });
        router.post(route('keloladata.ikss.import'), { file: jsonFile }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setImportOpen(false);
                setImportFile(null);
            },
        });
    };

    const parameterColumns = [
        { field: 'ikss_id', headerName: 'IKSS', width: 120 },
        { field: 'code', headerName: 'Kode', width: 180 },
        { field: 'name', headerName: 'Nama Parameter', flex: 1, minWidth: 260 },
        { field: 'parameter_role', headerName: 'Peran', width: 130 },
        { field: 'calculation_method', headerName: 'Perhitungan', width: 150 },
        { field: 'valid_from_year', headerName: 'Mulai', width: 90 },
        { field: 'valid_until_year', headerName: 'Sampai', width: 90 },
        { field: 'aksi', headerName: 'Aksi', width: 105, renderCell: ({ row }) => <><IconButton color="warning" onClick={() => open('parameter', row)}><EditIcon /></IconButton><IconButton color="error" onClick={() => destroy('parameter', row.id)}><DeleteIcon /></IconButton></> },
    ];
    const groupColumns = [
        { field: 'ikss_id', headerName: 'IKSS', width: 130 }, { field: 'code', headerName: 'Kode', width: 220 },
        { field: 'name', headerName: 'Nama Kelompok', flex: 1, minWidth: 280 }, { field: 'group_type', headerName: 'Tipe', width: 120 },
        { field: 'aksi', headerName: 'Aksi', width: 105, renderCell: ({ row }) => <><IconButton color="warning" onClick={() => open('group', row)}><EditIcon /></IconButton><IconButton color="error" onClick={() => destroy('group', row.id)}><DeleteIcon /></IconButton></> },
    ];

    return <Box>
        <Alert severity="info" sx={{ mb: 2 }}>Nama SS, IKSS, deskripsi, relasi SS, dan tahun diedit pada tab <strong>Sastra, Saspro & Indikator</strong>. Di sini Anda mengatur seluruh parameter dan rumus IKSS.</Alert>
        <Paper variant="outlined" sx={{ mb: 2 }}><Tabs value={active} onChange={(_, value) => setActive(value)}>
            <Tab value="parameters" label="Parameter IKSS" /><Tab value="groups" label="Kelompok Parameter" />
        </Tabs></Paper>
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ mb: 2 }}>
            <Button startIcon={<AddIcon />} variant="outlined" onClick={() => open(active === 'parameters' ? 'parameter' : 'group')}>
                Tambah {active === 'parameters' ? 'Parameter' : 'Kelompok'}
            </Button>
            <Button startIcon={<DownloadIcon />} variant="outlined" color="success" onClick={downloadSpreadsheetTemplate}>
                Download Template Excel
            </Button>
            <Button startIcon={<UploadFileIcon />} variant="contained" color="success" onClick={() => setImportOpen(true)}>
                Impor Excel & Upsert
            </Button>
        </Stack>
        <DataGrid autoHeight disableRowSelectionOnClick rows={active === 'parameters' ? parameters : groups}
            columns={active === 'parameters' ? parameterColumns : groupColumns} pageSizeOptions={[10, 25, 50]}
            initialState={{ pagination: { paginationModel: { pageSize: 10 } } }} sx={{ border: 0 }} />
        <Dialog open={dialog.open} onClose={close} maxWidth="lg" fullWidth><Box component="form" onSubmit={save}>
            <DialogTitle>{dialog.mode === 'edit' ? 'Edit' : 'Tambah'} {dialog.type === 'parameter' ? 'Parameter IKSS' : 'Kelompok Parameter'}</DialogTitle>
            <DialogContent><Grid container spacing={2}>
                {selectField('ikss_id', 'IKSS', ikssOptions, false, 6)}
                {dialog.type === 'group' ? <>
                    {textField('code', 'Kode Kelompok')}{textField('name', 'Nama Kelompok')}
                    {selectField('parent_id', 'Kelompok Induk', groupOptions)}
                    {selectField('group_type', 'Tipe Kelompok', choices.group_type)}
                    {textField('section_code', 'Kode Bagian')}{textField('sort_order', 'Urutan', { type: 'number' })}
                    {textField('description', 'Deskripsi', { multiline: true, md: 12 })}
                    {textField('settings_json', 'Pengaturan JSON', { multiline: true, md: 12 })}
                    {selectField('is_active', 'Status Aktif', [{ value: 1, label: 'Aktif' }, { value: 0, label: 'Nonaktif' }])}
                </> : <>
                    {textField('code', 'Kode Parameter')}{textField('name', 'Nama Parameter')}
                    {selectField('group_id', 'Kelompok Parameter', groupOptions)}{selectField('parent_id', 'Parameter Induk', parameterOptions)}
                    {textField('description', 'Deskripsi', { multiline: true, md: 12 })}
                    {selectField('parameter_role', 'Peran Parameter', choices.parameter_role)}
                    {selectField('input_mode', 'Mode Input', choices.input_mode)}
                    {selectField('source_type', 'Sumber Data', choices.source_type)}
                    {textField('source_reference', 'Referensi Sumber')}{textField('legacy_indicator_id', 'ID Indikator Lama')}
                    {selectField('value_type', 'Tipe Nilai', choices.value_type)}{textField('unit', 'Satuan')}
                    {selectField('period_type', 'Periode', choices.period_type)}
                    {selectField('calculation_method', 'Metode Perhitungan', choices.calculation_method)}
                    {selectField('aggregation_method', 'Metode Agregasi', choices.aggregation_method)}
                    {selectField('aggregation_scope', 'Lingkup Agregasi', choices.aggregation_scope)}
                    {selectField('entry_levels', 'Level Pengisi (1-4)', [1, 2, 3, 4], true)}
                    {selectField('aggregate_to_levels', 'Agregasi ke Level (1-4)', [1, 2, 3, 4], true)}
                    {textField('valid_from_year', 'Berlaku Mulai Tahun', { type: 'number' })}
                    {textField('valid_until_year', 'Berlaku Sampai Tahun', { type: 'number' })}
                    {textField('decimal_places', 'Jumlah Desimal', { type: 'number' })}
                    {textField('sort_order', 'Urutan', { type: 'number' })}
                    {booleanFields.map((name) => selectField(name, name.replaceAll('_', ' ').toUpperCase(), [{ value: 1, label: 'Ya' }, { value: 0, label: 'Tidak' }], false, 3))}
                    {textField('formula_config_json', 'Konfigurasi Rumus JSON', { multiline: true, md: 12 })}
                    <Grid item xs={12}><Typography variant="h6" sx={{ mt: 2 }}>Relasi Pembilang, Penyebut, Komponen, atau Bobot</Typography>
                        <Stack spacing={1}>{(form.dependencies || []).map((dependency, index) => <Grid container spacing={1} key={index}>
                            <Grid item xs={12} md={6}><FormControl fullWidth><InputLabel>Parameter Sumber</InputLabel><Select value={dependency.source_parameter_id} label="Parameter Sumber" onChange={(e) => update('dependencies', form.dependencies.map((row, i) => i === index ? { ...row, source_parameter_id: e.target.value } : row))}>{parameterOptions.filter((item) => item.value !== dialog.id).map((item) => <MenuItem key={item.value} value={item.value}>{item.label}</MenuItem>)}</Select></FormControl></Grid>
                            <Grid item xs={5} md={3}><FormControl fullWidth><InputLabel>Peran</InputLabel><Select value={dependency.role} label="Peran" onChange={(e) => update('dependencies', form.dependencies.map((row, i) => i === index ? { ...row, role: e.target.value } : row))}>{choices.dependency_role.map((role) => <MenuItem key={role} value={role}>{role}</MenuItem>)}</Select></FormControl></Grid>
                            <Grid item xs={5} md={2}><TextField fullWidth type="number" label="Bobot" value={dependency.weight ?? ''} onChange={(e) => update('dependencies', form.dependencies.map((row, i) => i === index ? { ...row, weight: e.target.value } : row))} /></Grid>
                            <Grid item xs={2} md={1}><IconButton color="error" onClick={() => update('dependencies', form.dependencies.filter((_, i) => i !== index))}><DeleteIcon /></IconButton></Grid>
                        </Grid>)}</Stack><Button startIcon={<AddIcon />} onClick={addDependency} sx={{ mt: 1 }}>Tambah Relasi</Button>
                    </Grid>
                </>}
            </Grid></DialogContent>
            <DialogActions><Button onClick={close}>Batal</Button><Button type="submit" variant="contained" startIcon={<SaveIcon />}>Simpan</Button></DialogActions>
        </Box></Dialog>
        <Dialog open={importOpen} onClose={() => setImportOpen(false)} maxWidth="sm" fullWidth>
            <Box component="form" onSubmit={importCatalog}>
                <DialogTitle>Impor Excel Katalog IKSS</DialogTitle>
                <DialogContent>
                    <Alert severity="info" sx={{ mb: 2 }}>
                        Wajib unduh template Excel terlebih dahulu. Template berisi data saat ini dan dapat diedit sebelum diunggah kembali.
                    </Alert>
                    <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1}>
                        <Button variant="contained" color="success" startIcon={<DownloadIcon />} onClick={downloadSpreadsheetTemplate}>
                            1. Download Template Excel
                        </Button>
                        <Button component="label" variant="outlined" disabled={!templateDownloaded} startIcon={<UploadFileIcon />}>
                            2. Pilih File Excel
                            <input hidden type="file" accept=".xlsx,.xls" onChange={(event) => {
                                setImportFile(event.target.files?.[0] || null);
                                setImportError('');
                            }} />
                        </Button>
                    </Stack>
                    <Typography variant="body2" sx={{ mt: 1 }}>{importFile?.name || 'Belum ada file dipilih.'}</Typography>
                    {importError && <Alert severity="error" sx={{ mt: 2 }}>{importError}</Alert>}
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setImportOpen(false)}>Batal</Button>
                    <Button type="submit" variant="contained" disabled={!importFile} startIcon={<UploadFileIcon />}>Impor Excel & Upsert</Button>
                </DialogActions>
            </Box>
        </Dialog>
    </Box>;
}
