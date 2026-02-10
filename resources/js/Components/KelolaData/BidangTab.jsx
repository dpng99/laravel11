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
    // Form Tambah
    const { data, setData, post, processing, reset } = useForm({
        bidang_nama: '', bidang_level: '', bidang_lokasi: '1', rumpun: '', hide: '0'
    });

    // State Modal Edit
    const [editOpen, setEditOpen] = useState(false);
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
        setEditData(item);
        setEditOpen(true);
    };

    // Submit Update Data
    const handleUpdate = () => {
        router.post(route('bidang.storeOrUpdateBidang'), editData, {
            onSuccess: () => setEditOpen(false),
            preserveScroll: true
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
        router.get(bidangs.path, { page: value }, { preserveScroll: true, preserveState: true, only: ['bidangs'] });
    };

    return (
        <Box>
            {/* Form Input (Accordion) */}
            <Accordion defaultExpanded={false} sx={{ mb: 4, border: '1px solid #ddd' }}>
                <AccordionSummary expandIcon={<ExpandMoreIcon sx={{ color: 'white' }} />} sx={{ backgroundColor: '#ffcc00' }}>
                    <Typography fontWeight="bold">Input Data Bidang</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Box component="form" onSubmit={handleSubmit} sx={{ mt: 2 }}>
                        <TextField fullWidth label="Nama Bidang" value={data.bidang_nama} onChange={e => setData('bidang_nama', e.target.value)} margin="normal" required />
                        <Grid container spacing={2}>
                            <Grid item xs={6}>
                                <TextField fullWidth type="number" label="Level" value={data.bidang_level} onChange={e => setData('bidang_level', e.target.value)} margin="normal" required />
                            </Grid>
                            <Grid item xs={6}>
                                <FormControl fullWidth margin="normal">
                                    <InputLabel>Lokasi</InputLabel>
                                    <Select value={data.bidang_lokasi} label="Lokasi" onChange={e => setData('bidang_lokasi', e.target.value)}>
                                        <MenuItem value="1">Pusat</MenuItem>
                                        <MenuItem value="2">Kejati</MenuItem>
                                        <MenuItem value="3">Kejari</MenuItem>
                                        <MenuItem value="4">Cabjari</MenuItem>
                                    </Select>
                                </FormControl>
                            </Grid>
                        </Grid>
                        <Grid container spacing={2}>
                            <Grid item xs={6}>
                                <TextField fullWidth type="number" label="Rumpun" value={data.rumpun} onChange={e => setData('rumpun', e.target.value)} margin="normal" required />
                            </Grid>
                            <Grid item xs={6}>
                                <FormControl fullWidth margin="normal">
                                    <InputLabel>Status</InputLabel>
                                    <Select value={data.hide} label="Status" onChange={e => setData('hide', e.target.value)}>
                                        <MenuItem value="0">Tampil</MenuItem>
                                        <MenuItem value="1">Sembunyikan</MenuItem>
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
                                <TableCell>{item.bidang_lokasi}</TableCell>
                                <TableCell>{item.rumpun}</TableCell>
                                <TableCell>{item.hide == 0 ? 'Tampil' : 'Tersembunyi'}</TableCell>
                                <TableCell align="center">
                                    <Button size="small" color="warning" onClick={() => handleEditClick(item)} sx={{minWidth:'auto', mr:1}}><EditIcon/></Button>
                                    <Button size="small" color="error" onClick={() => handleDelete(item.id)} sx={{minWidth:'auto'}}><DeleteIcon/></Button>
                                </TableCell>
                            </TableRow>
                        ))}
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
                <DialogContent>
                    <TextField fullWidth label="Nama Bidang" value={editData.bidang_nama} onChange={e => setEditData({...editData, bidang_nama: e.target.value})} margin="normal" />
                    {/* ... Field lain sama seperti form create ... */}
                    <TextField fullWidth label="Rumpun" type="number" value={editData.rumpun} onChange={e => setEditData({...editData, rumpun: e.target.value})} margin="normal" />
                    {/* Tambahkan field lainnya sesuai kebutuhan edit */}
                    <Button onClick={() => setEditOpen(false)}>Batal</Button>
                    <Button onClick={handleUpdate} variant="contained" color="success">Simpan Perubahan</Button>
                </DialogContent>
             
            </Dialog>
        </Box>
    );
}