import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { 
    Box, Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, 
    Paper, TextField, Select, MenuItem, FormControl, InputLabel, Accordion, 
    AccordionSummary, AccordionDetails, Typography, Pagination, Stack, Dialog,
    DialogTitle, DialogContent, DialogActions, Grid
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

export default function BidangTab({ bidangs }) {
    const lokasiOptions = [
        { value: '1', label: 'Pusat' },
        { value: '2', label: 'Kejati' },
        { value: '3', label: 'Kejari' },
        { value: '4', label: 'Cabjari' },
        { value: '5', label: 'Lainnya' },
    ];

    const statusOptions = [
        { value: '0', label: 'Tampil' },
        { value: '1', label: 'Sembunyikan' },
    ];

    // Form Tambah
    const { data, setData, post, processing, reset, errors } = useForm({
        bidang_nama: '', bidang_level: '', bidang_lokasi: '1', rumpun: '', hide: '0'
    });

    // State Modal Edit
    const [editOpen, setEditOpen] = useState(false);
    const [updating, setUpdating] = useState(false);
    const [editData, setEditData] = useState({
        id: '', bidang_nama: '', bidang_level: '', bidang_lokasi: '', rumpun: '', hide: ''
    });

    // Submit Tambah Data
    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('bidang.storeOrUpdateBidang'), {
            onSuccess: () => reset(),
            preserveScroll: true
        });
    };

    // Buka Modal Edit
    const handleEditClick = (item) => {
        setEditData({
            id: item.id,
            bidang_nama: item.bidang_nama ?? '',
            bidang_level: item.bidang_level?.toString() ?? '',
            bidang_lokasi: item.bidang_lokasi?.toString() ?? '1',
            rumpun: item.rumpun?.toString() ?? '',
            hide: item.hide?.toString() ?? '0',
        });
        setEditOpen(true);
    };

    // Submit Update Data
    const handleUpdate = (e) => {
        e.preventDefault();
        router.post(route('bidang.storeOrUpdateBidang'), editData, {
            preserveScroll: true,
            onStart: () => setUpdating(true),
            onSuccess: () => setEditOpen(false),
            onFinish: () => setUpdating(false),
        });
    };

    // Hapus Data
    const handleDelete = (id) => {
        if(confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            router.delete(route('bidang.destroy', id), { preserveScroll: true });
        }
    };

    // Pagination
    const handlePageChange = (event, value) => {
        router.get(
            route('keloladata'),
            { tab: 'bidang', bidang_page: value, per_page: bidangs.per_page },
            { preserveScroll: true, preserveState: true, only: ['bidangs', 'filters'] }
        );
    };

    const lokasiLabel = (value) => lokasiOptions.find(option => option.value === value?.toString())?.label || value;

    return (
        <Box>
            {/* Form Input (Accordion) */}
            <Accordion defaultExpanded={false} sx={{ mb: 4, border: '1px solid #ddd' }}>
                <AccordionSummary expandIcon={<ExpandMoreIcon sx={{ color: 'white' }} />} sx={{ backgroundColor: '#ffcc00' }}>
                    <Typography fontWeight="bold">Input Data Bidang</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Box component="form" onSubmit={handleSubmit} sx={{ mt: 2 }}>
                        <TextField fullWidth label="Nama Bidang" value={data.bidang_nama} onChange={e => setData('bidang_nama', e.target.value)} margin="normal" required error={!!errors.bidang_nama} helperText={errors.bidang_nama} />
                        <Grid container spacing={2}>
                            <Grid item xs={6}>
                                <TextField fullWidth type="number" label="Level" value={data.bidang_level} onChange={e => setData('bidang_level', e.target.value)} margin="normal" required error={!!errors.bidang_level} helperText={errors.bidang_level} />
                            </Grid>
                            <Grid item xs={6}>
                                <FormControl fullWidth margin="normal">
                                    <InputLabel>Lokasi</InputLabel>
                                    <Select value={data.bidang_lokasi} label="Lokasi" onChange={e => setData('bidang_lokasi', e.target.value)}>
                                        {lokasiOptions.map(option => (
                                            <MenuItem key={option.value} value={option.value}>{option.label}</MenuItem>
                                        ))}
                                    </Select>
                                </FormControl>
                            </Grid>
                        </Grid>
                        <Grid container spacing={2}>
                            <Grid item xs={6}>
                                <TextField fullWidth type="number" label="Rumpun" value={data.rumpun} onChange={e => setData('rumpun', e.target.value)} margin="normal" required error={!!errors.rumpun} helperText={errors.rumpun} />
                            </Grid>
                            <Grid item xs={6}>
                                <FormControl fullWidth margin="normal">
                                    <InputLabel>Status</InputLabel>
                                    <Select value={data.hide} label="Status" onChange={e => setData('hide', e.target.value)}>
                                        {statusOptions.map(option => (
                                            <MenuItem key={option.value} value={option.value}>{option.label}</MenuItem>
                                        ))}
                                    </Select>
                                </FormControl>
                            </Grid>
                        </Grid>
                        <Button type="submit" variant="contained" color="success" disabled={processing} sx={{ mt: 2 }}>Simpan</Button>
                    </Box>
                </AccordionDetails>
            </Accordion>

            {/* Tabel Data */}
            <Typography variant="h6" align="center" gutterBottom fontWeight="bold">Data Bidang</Typography>
            <TableContainer component={Paper}>
                <Table size="small">
                    <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                        <TableRow>
                            <TableCell>No</TableCell>
                            <TableCell>Nama Bidang</TableCell>
                            <TableCell>Level</TableCell>
                            <TableCell>Lokasi</TableCell>
                            <TableCell>Rumpun</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell align="center">Aksi</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {bidangs?.data?.map((item, index) => (
                            <TableRow key={item.id}>
                                <TableCell>{(bidangs.current_page - 1) * bidangs.per_page + index + 1}</TableCell>
                                <TableCell>{item.bidang_nama}</TableCell>
                                <TableCell>{item.bidang_level}</TableCell>
                                <TableCell>{lokasiLabel(item.bidang_lokasi)}</TableCell>
                                <TableCell>{item.rumpun}</TableCell>
                                <TableCell>{item.hide == 0 ? 'Tampil' : 'Tersembunyi'}</TableCell>
                                <TableCell align="center">
                                    <Button size="small" color="warning" onClick={() => handleEditClick(item)} sx={{minWidth:'auto', mr:1}}><EditIcon/></Button>
                                    <Button size="small" color="error" onClick={() => handleDelete(item.id)} sx={{minWidth:'auto'}}><DeleteIcon/></Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {(!bidangs?.data || bidangs.data.length === 0) && (
                            <TableRow>
                                <TableCell colSpan={7} align="center">Tidak ada data bidang.</TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Pagination */}
            {bidangs?.last_page > 1 && (
                <Stack spacing={2} sx={{ mt: 3, alignItems: 'center' }}>
                    <Pagination count={bidangs.last_page} page={bidangs.current_page} onChange={handlePageChange} color="primary" />
                </Stack>
            )}

            {/* Modal Edit */}
            <Dialog open={editOpen} onClose={() => setEditOpen(false)} maxWidth="md" fullWidth>
                <DialogTitle>Edit Data Bidang</DialogTitle>
                <Box component="form" onSubmit={handleUpdate}>
                    <DialogContent>
                        <TextField
                            fullWidth
                            label="Nama Bidang"
                            value={editData.bidang_nama}
                            onChange={e => setEditData({...editData, bidang_nama: e.target.value})}
                            margin="normal"
                            required
                        />
                        <Grid container spacing={2}>
                            <Grid item xs={12} md={4}>
                                <TextField
                                    fullWidth
                                    label="Level"
                                    type="number"
                                    value={editData.bidang_level}
                                    onChange={e => setEditData({...editData, bidang_level: e.target.value})}
                                    margin="normal"
                                    required
                                />
                            </Grid>
                            <Grid item xs={12} md={4}>
                                <FormControl fullWidth margin="normal">
                                    <InputLabel>Lokasi</InputLabel>
                                    <Select value={editData.bidang_lokasi} label="Lokasi" onChange={e => setEditData({...editData, bidang_lokasi: e.target.value})}>
                                        {lokasiOptions.map(option => (
                                            <MenuItem key={option.value} value={option.value}>{option.label}</MenuItem>
                                        ))}
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid item xs={12} md={4}>
                                <TextField
                                    fullWidth
                                    label="Rumpun"
                                    type="number"
                                    value={editData.rumpun}
                                    onChange={e => setEditData({...editData, rumpun: e.target.value})}
                                    margin="normal"
                                    required
                                />
                            </Grid>
                        </Grid>
                        <FormControl fullWidth margin="normal">
                            <InputLabel>Status</InputLabel>
                            <Select value={editData.hide} label="Status" onChange={e => setEditData({...editData, hide: e.target.value})}>
                                {statusOptions.map(option => (
                                    <MenuItem key={option.value} value={option.value}>{option.label}</MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setEditOpen(false)}>Batal</Button>
                        <Button type="submit" variant="contained" color="success" disabled={updating}>
                            {updating ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                    </DialogActions>
                </Box>
            </Dialog>
        </Box>
    );
}
