import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    CircularProgress,
    Divider,
    FormControl,
    Grid,
    IconButton,
    InputLabel,
    MenuItem,
    Select,
    Stack,
    TextField,
    Typography,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import RefreshIcon from '@mui/icons-material/Refresh';
import SaveIcon from '@mui/icons-material/Save';

const emptyItem = () => ({
    item_key: `item_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
    item_label: '',
    value_decimal: '',
});

const displayValue = (value, unit) => {
    if (value === null || value === undefined || value === '') return '-';
    const numeric = Number(value);
    const formatted = Number.isFinite(numeric)
        ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(numeric)
        : value;
    return unit === '%' ? `${formatted}%` : `${formatted}${unit ? ` ${unit}` : ''}`;
};

const displayIkssCode = (value = '') => value.replace(/^IKSS/i, '').replace('-', '.');
const displaySsCode = (value = '') => value.replace(/^SS/i, '');

export default function IkssParameterTab({ tahun, levelSakip }) {
    const [quarter, setQuarter] = useState(1);
    const [selectedMonth, setSelectedMonth] = useState(1);
    const [selectedIkss, setSelectedIkss] = useState('all');
    const [catalog, setCatalog] = useState([]);
    const [periodValues, setPeriodValues] = useState({});
    const [forms, setForms] = useState({});
    const [dirtyParameters, setDirtyParameters] = useState(new Set());
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    const parameters = useMemo(
        () => catalog.flatMap((ikss) => ikss.parameters || []),
        [catalog]
    );
    const quarterMonths = useMemo(
        () => [((quarter - 1) * 3) + 1, ((quarter - 1) * 3) + 2, quarter * 3],
        [quarter]
    );

    const groups = useMemo(() => {
        const grouped = new Map();
        catalog.forEach((ikss) => {
            if (selectedIkss !== 'all' && ikss.ikss_id !== selectedIkss) return;

            (ikss.parameters || []).forEach((parameter) => {
                const key = parameter.group_code || `ikss:${parameter.ikss_id}`;
                if (!grouped.has(key)) {
                    grouped.set(key, {
                        code: key,
                        name: parameter.group_name || `Parameter ${parameter.ikss_id}`,
                        description: parameter.group_description,
                        ikssId: parameter.ikss_id,
                        ikssName: ikss.ikss_name,
                        ssId: ikss.ss_id,
                        ssName: ikss.ss_name,
                        parameters: [],
                    });
                }
                grouped.get(key).parameters.push(parameter);
            });
        });
        return Array.from(grouped.values());
    }, [catalog, selectedIkss]);
    const hasEditableParameters = useMemo(
        () => parameters.some((parameter) => parameter.can_enter),
        [parameters]
    );

    const loadData = async () => {
        setLoading(true);
        setError('');
        setMessage('');

        try {
            const [catalogResponse, valuesResponse] = await Promise.all([
                axios.get('/pelaporan/ikss-parameters/catalog', { params: { year: tahun } }),
                axios.get('/pelaporan/ikss-parameters/values', { params: { year: tahun, quarter } }),
            ]);
            const nextCatalog = catalogResponse.data.data || [];
            const values = valuesResponse.data.data || [];
            const byParameter = Object.fromEntries(values.map((row) => [row.parameter_id, row]));
            const nextForms = {};

            nextCatalog.flatMap((ikss) => ikss.parameters || []).forEach((parameter) => {
                const inputMonth = parameter.period_type === 'monthly' ? selectedMonth : 0;
                const period = (byParameter[parameter.id]?.values || []).find(
                    (value) => Number(value.month) === inputMonth
                );
                nextForms[parameter.id] = {
                    value_decimal: period?.value_decimal ?? '',
                    value_text: period?.value_text ?? '',
                    items: (period?.items || []).map((item) => ({
                        item_key: item.item_key,
                        item_label: item.item_label,
                        value_decimal: item.value_decimal ?? '',
                        value_text: item.value_text ?? '',
                        sort_order: item.sort_order,
                    })),
                };
            });

            setCatalog(nextCatalog);
            setPeriodValues(byParameter);
            setForms(nextForms);
            setDirtyParameters(new Set());
        } catch (requestError) {
            setError(requestError.response?.data?.message || 'Data parameter IKSS gagal dimuat.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, [quarter, selectedMonth, tahun]);

    const updateForm = (parameterId, field, value) => {
        setForms((current) => ({
            ...current,
            [parameterId]: { ...current[parameterId], [field]: value },
        }));
        setDirtyParameters((current) => new Set(current).add(parameterId));
    };

    const updateItem = (parameterId, itemIndex, field, value) => {
        const items = [...(forms[parameterId]?.items || [])];
        items[itemIndex] = { ...items[itemIndex], [field]: value };
        updateForm(parameterId, 'items', items);
    };

    const addItem = (parameterId) => {
        updateForm(parameterId, 'items', [...(forms[parameterId]?.items || []), emptyItem()]);
    };

    const removeItem = (parameterId, itemIndex) => {
        updateForm(
            parameterId,
            'items',
            (forms[parameterId]?.items || []).filter((_, index) => index !== itemIndex)
        );
    };

    const save = async () => {
        const values = parameters
            .filter((parameter) => {
                if (!dirtyParameters.has(parameter.id)) return false;

                const inputMonth = parameter.period_type === 'monthly' ? selectedMonth : 0;
                const existing = (periodValues[parameter.id]?.values || [])
                    .find((value) => Number(value.month) === inputMonth);

                return parameter.calculation_method === 'input'
                    && parameter.source_type === 'manual'
                    && existing?.status !== 'locked';
            })
            .map((parameter) => {
                const form = forms[parameter.id] || {};
                const inputMonth = parameter.period_type === 'monthly' ? selectedMonth : 0;
                const existing = (periodValues[parameter.id]?.values || [])
                    .find((value) => Number(value.month) === inputMonth);
                const entry = { parameter_id: parameter.id, month: inputMonth };

                if (parameter.input_mode === 'table' || parameter.input_mode === 'list') {
                    entry.items = (form.items || [])
                        .filter((item) => item.item_label.trim() !== '' && item.value_decimal !== '')
                        .map((item, index) => ({
                            ...item,
                            value_decimal: Number(item.value_decimal),
                            sort_order: index,
                        }));
                    if (entry.items.length === 0 && existing) {
                        return { ...entry, clear: true };
                    }
                    if (entry.items.length === 0) {
                        return null;
                    }
                } else if (form.value_decimal !== '') {
                    entry.value_decimal = Number(form.value_decimal);
                } else if (form.value_text?.trim()) {
                    entry.value_text = form.value_text.trim();
                } else if (existing) {
                    return { ...entry, clear: true };
                }

                return entry;
            })
            .filter((entry) => entry && (
                entry.value_decimal !== undefined
                || entry.value_text
                || entry.items !== undefined
                || entry.clear
            ));

        if (values.length === 0) {
            setError('Belum ada nilai parameter yang dapat disimpan.');
            return;
        }

        setSaving(true);
        setError('');
        setMessage('');

        try {
            const response = await axios.post('/pelaporan/ikss-parameters/values', {
                year: Number(tahun),
                quarter,
                values,
            });
            await loadData();
            setMessage(response.data.message || 'Parameter IKSS berhasil disimpan.');
        } catch (requestError) {
            const validation = requestError.response?.data?.errors;
            const firstValidation = validation ? Object.values(validation).flat()[0] : null;
            setError(firstValidation || requestError.response?.data?.message || 'Parameter IKSS gagal disimpan.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <Box>
            <Typography variant="h5" sx={{ fontWeight: 'bold', mb: 1 }}>
                Parameter Pengampu IKSS
            </Typography>
            <Alert severity="info" sx={{ mb: 2 }}>
                Isi seluruh parameter pengampu sesuai SS dan IKSS: nilai dasar, pembilang, penyebut,
                rincian tahapan, faktor, dan upaya optimalisasi. Hasil rumus serta agregasi wilayah dihitung otomatis.
            </Alert>

            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} sx={{ mb: 3 }}>
                <FormControl size="small" sx={{ minWidth: 280 }}>
                    <InputLabel>SS / IKSS</InputLabel>
                    <Select
                        value={selectedIkss}
                        label="SS / IKSS"
                        onChange={(event) => setSelectedIkss(event.target.value)}
                    >
                        <MenuItem value="all">Semua SS / IKSS</MenuItem>
                        {catalog.map((ikss) => (
                            <MenuItem key={ikss.ikss_id} value={ikss.ikss_id}>
                                IKSS {displayIkssCode(ikss.ikss_id)} - {ikss.ikss_name}
                            </MenuItem>
                        ))}
                    </Select>
                </FormControl>
                <FormControl size="small" sx={{ minWidth: 180 }}>
                    <InputLabel>Triwulan</InputLabel>
                    <Select
                        value={quarter}
                        label="Triwulan"
                        onChange={(event) => {
                            const nextQuarter = Number(event.target.value);
                            setQuarter(nextQuarter);
                            setSelectedMonth(((nextQuarter - 1) * 3) + 1);
                        }}
                    >
                        {[1, 2, 3, 4].map((value) => (
                            <MenuItem key={value} value={value}>Triwulan {value}</MenuItem>
                        ))}
                    </Select>
                </FormControl>
                <FormControl size="small" sx={{ minWidth: 190 }}>
                    <InputLabel>Bulan Data Bulanan</InputLabel>
                    <Select
                        value={selectedMonth}
                        label="Bulan Data Bulanan"
                        onChange={(event) => setSelectedMonth(Number(event.target.value))}
                    >
                        {quarterMonths.map((value) => (
                            <MenuItem key={value} value={value}>Bulan {value}</MenuItem>
                        ))}
                    </Select>
                </FormControl>
                <Button startIcon={<RefreshIcon />} variant="outlined" onClick={loadData} disabled={loading}>
                    Muat Ulang
                </Button>
                {hasEditableParameters && (
                    <Button
                        startIcon={saving ? <CircularProgress size={18} color="inherit" /> : <SaveIcon />}
                        variant="contained"
                        onClick={save}
                        disabled={saving || loading || dirtyParameters.size === 0}
                    >
                        Simpan dan Hitung
                    </Button>
                )}
            </Stack>

            {message && <Alert severity="success" sx={{ mb: 2 }}>{message}</Alert>}
            {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

            {loading ? (
                <Box sx={{ py: 6, textAlign: 'center' }}><CircularProgress /></Box>
            ) : groups.length === 0 ? (
                <Alert severity="warning">Katalog parameter untuk level satuan kerja ini belum tersedia.</Alert>
            ) : (
                <Stack spacing={2}>
                    {groups.map((group) => (
                        <Card key={group.code} variant="outlined">
                            <CardHeader
                                title={group.name}
                                subheader={`SS ${displaySsCode(group.ssId) || '-'} - ${group.ssName || '-'} | IKSS ${displayIkssCode(group.ikssId)} - ${group.ikssName || group.ikssId}`}
                                titleTypographyProps={{ variant: 'subtitle1', fontWeight: 'bold' }}
                                sx={{ backgroundColor: '#f8f9fa', borderBottom: '1px solid #eee' }}
                            />
                            <CardContent>
                                {group.description && (
                                    <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                                        {group.description}
                                    </Typography>
                                )}
                                <Stack spacing={2.5}>
                                    {group.parameters.map((parameter, parameterIndex) => {
                                        const inputMonth = parameter.period_type === 'monthly' ? selectedMonth : 0;
                                        const period = (periodValues[parameter.id]?.values || [])
                                            .find((value) => Number(value.month) === inputMonth);
                                        const editable = parameter.can_enter
                                            && parameter.calculation_method === 'input'
                                            && parameter.source_type === 'manual'
                                            && period?.status !== 'locked';
                                        const form = forms[parameter.id] || { items: [] };

                                        return (
                                            <Box key={parameter.id}>
                                                {parameterIndex > 0 && <Divider sx={{ mb: 2.5 }} />}
                                                <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 0.5 }}>
                                                    {parameter.name}
                                                </Typography>
                                                <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 1.5 }}>
                                                    {parameter.parameter_role} / {parameter.calculation_method}
                                                    {parameter.period_type === 'monthly' ? ` / bulan ${selectedMonth}` : ''}
                                                    {parameter.unit ? ` / ${parameter.unit}` : ''}
                                                    {period?.status ? ` / status ${period.status}` : ''}
                                                </Typography>

                                                {(parameter.input_mode === 'table' || parameter.input_mode === 'list') && editable ? (
                                                    <Stack spacing={1.5}>
                                                        {(form.items || []).map((item, itemIndex) => (
                                                            <Grid container spacing={1} alignItems="center" key={item.item_key}>
                                                                <Grid size={{ xs: 12, md: 7 }}>
                                                                    <TextField
                                                                        fullWidth
                                                                        size="small"
                                                                        label="Nama baris / rincian"
                                                                        value={item.item_label}
                                                                        onChange={(event) => updateItem(parameter.id, itemIndex, 'item_label', event.target.value)}
                                                                    />
                                                                </Grid>
                                                                <Grid size={{ xs: 10, md: 4 }}>
                                                                    <TextField
                                                                        fullWidth
                                                                        size="small"
                                                                        type="number"
                                                                        label={`Nilai${parameter.unit ? ` (${parameter.unit})` : ''}`}
                                                                        value={item.value_decimal}
                                                                        onChange={(event) => updateItem(parameter.id, itemIndex, 'value_decimal', event.target.value)}
                                                                    />
                                                                </Grid>
                                                                <Grid size={{ xs: 2, md: 1 }}>
                                                                    <IconButton color="error" onClick={() => removeItem(parameter.id, itemIndex)}>
                                                                        <DeleteIcon />
                                                                    </IconButton>
                                                                </Grid>
                                                            </Grid>
                                                        ))}
                                                        <Button startIcon={<AddIcon />} size="small" onClick={() => addItem(parameter.id)}>
                                                            Tambah Baris
                                                        </Button>
                                                        <Typography variant="body2">
                                                            Nilai rata-rata saat ini: <strong>{displayValue(period?.value_decimal, parameter.unit)}</strong>
                                                        </Typography>
                                                    </Stack>
                                                ) : editable ? (
                                                    <TextField
                                                        fullWidth
                                                        size="small"
                                                        type={parameter.value_type === 'text' ? 'text' : 'number'}
                                                        multiline={parameter.value_type === 'text'}
                                                        minRows={parameter.value_type === 'text' ? 3 : undefined}
                                                        label={`Nilai${parameter.unit ? ` (${parameter.unit})` : ''}`}
                                                        value={parameter.value_type === 'text' ? form.value_text || '' : form.value_decimal ?? ''}
                                                        onChange={(event) => updateForm(
                                                            parameter.id,
                                                            parameter.value_type === 'text' ? 'value_text' : 'value_decimal',
                                                            event.target.value
                                                        )}
                                                    />
                                                ) : (
                                                    <Alert severity={period ? 'success' : 'warning'} icon={false}>
                                                        Hasil otomatis: <strong>{displayValue(period?.value_decimal ?? period?.value_text, parameter.unit)}</strong>
                                                        {period?.completeness !== undefined && ` / Kelengkapan ${period.completeness}%`}
                                                    </Alert>
                                                )}
                                            </Box>
                                        );
                                    })}
                                </Stack>
                            </CardContent>
                        </Card>
                    ))}
                </Stack>
            )}
        </Box>
    );
}
