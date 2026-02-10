import React, { useState, useEffect } from 'react';
import { useForm, router} from '@inertiajs/react';
import { 
    Box, Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, 
    Paper, TextField, Select, MenuItem, FormControl, InputLabel, Accordion, 
    AccordionSummary, AccordionDetails, Typography, Pagination, Stack, Dialog,
    DialogTitle, DialogContent, DialogActions, Grid
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

export default function IndikatorTab({ indikators, bidangAll, sasproAll }) {
    const { data, setData, post, processing, reset } = useForm({
        bidang: '', lingkup: '', id_saspro: '', indikator_nama: '',
        indikator_pembilang: '', indikator_penyebut: '', indikator_penjelasan: '',
        sub_indikator: '', indikator_penghitungan: '', tahun: '', tren: ''
    });

    const [editOpen, setEditOpen] = useState(false);
    const [editData, setEditData] = useState({});
    const [filteredSaspro, setFilteredSaspro] = useState([]);

    // Create
    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('indikator.store'), { onSuccess: () => reset(), preserveScroll: true });
    };

    // Delete (Pakai POST sesuai web.php)
    const handleDelete = (id) => {
        if(confirm('Yakin ingin menghapus indikator ini?')) {
            router.post(route('indikator.delete', id), {}, { preserveScroll: true });
        }
    };
    useEffect(() => {
        if (data.bidang) {
            // Filter sasproAll berdasarkan id_bidang (pastikan nama kolom relasinya benar, misal 'link' atau 'id_bidang')
            // Berdasarkan struktur tabel Saspro sebelumnya, kolom foreign key ke bidang adalah 'link'
            const filtered = sasproAll.filter(item => item.link == data.bidang);
            setFilteredSaspro(filtered);
        } else {
            // Jika tidak ada bidang dipilih, bisa kosongkan atau tampilkan semua (tergantung kebutuhan)
            setFilteredSaspro([]); 
        }
    }, [data.bidang, sasproAll]);
    // Edit
    const handleEditClick = (item) => {
        setEditData({
            id: item.id,
            bidang: item.id_bidang,
            lingkup: item.lingkup,
            id_saspro: item.id_saspro,
            indikator_nama: item.indikator_nama,
            indikator_pembilang: item.indikator_pembilang,
            indikator_penyebut: item.indikator_penyebut,
            indikator_penjelasan: item.indikator_penjelasan,
            sub_indikator: item.sub_indikator,
            indikator_penghitungan: item.indikator_penghitungan,
            tahun: item.tahun,
            tren: item.tren
        });
        setEditOpen(true);
    };

    const handleUpdate = () => {
        router.post(route('indikator.update', editData.id), editData, {
            onSuccess: () => setEditOpen(false),
            preserveScroll: true
        });
    };

    const handlePageChange = (event, value) => {
        router.get(indikators.path, { page: value }, { preserveScroll: true, preserveState: true, only: ['indikators'] });
    };

    const getLingkupLabel = (val) => {
        const map = {
            0: 'Semua Satker', 1: 'Pusat', 2: 'Kejati', 3: 'Kejari', 
            4: 'Cabjari', 5: 'Kejati, Kejari', 6: 'Kejari, Cabjari', 7: 'Kejati, Kejari, Cabjari'
        };
        return map[val] || val;
    };

    return (
        <Box>
            <Accordion defaultExpanded={false} sx={{ mb: 4, border: '1px solid #ddd' }}>
                <AccordionSummary expandIcon={<ExpandMoreIcon sx={{ color: 'white' }} />} sx={{ backgroundColor: '#ffcc00' }}>
                    <Typography fontWeight="bold">Input Data Indikator</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Box component="form" onSubmit={handleSubmit} sx={{ mt: 2 }}>
                        {/* Form Fields untuk Indikator */}
                        <Grid container spacing={2}>
                            <Grid item xs={6}>
                                <FormControl fullWidth margin="normal">
                                    <InputLabel>Bidang</InputLabel>
                                    <Select value={data.bidang} label="Bidang" onChange={e => setData('bidang', e.target.value)} required>
                                        {bidangAll.map(b => <MenuItem key={b.id} value={b.id}>{b.bidang_nama}</MenuItem>)}
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid item xs={6}>
                                <FormControl fullWidth margin="normal">
                                    <InputLabel>Lingkup</InputLabel>
                                    <Select value={data.lingkup} label="Lingkup" onChange={e => setData('lingkup', e.target.value)}>
                                        {[0,1,2,3,4,5,6,7].map(val => (<MenuItem key={val} value={val}>{getLingkupLabel(val)}</MenuItem>))}
                                    </Select>
                                </FormControl>
                            </Grid>
                        </Grid>
                        
                        <FormControl fullWidth margin="normal">
                            <InputLabel>Sasaran Program</InputLabel>
                            <Select 
                                value={data.id_saspro} 
                                label="Sasaran Program" 
                                onChange={e => setData('id_saspro', e.target.value)} 
                                required
                                disabled={!data.bidang} // Disable jika bidang belum dipilih
                            >
                                {filteredSaspro.length > 0 ? (
                                    filteredSaspro.map(s => (
                                        <MenuItem key={s.id} value={s.id}>
                                            {s.saspro_nama} ({s.tahun})
                                        </MenuItem>
                                    ))
                                ) : (
                                    <MenuItem value="" disabled>
                                        {data.bidang ? "Tidak ada Sasaran Program untuk bidang ini" : "Pilih Bidang Terlebih Dahulu"}
                                    </MenuItem>
                                )}
                            </Select>
                        </FormControl>

                        <TextField fullWidth label="Nama Indikator" value={data.indikator_nama} onChange={e => setData('indikator_nama', e.target.value)} margin="normal" required />
                        <TextField fullWidth label="Sub Indikator" value={data.sub_indikator} onChange={e => setData('sub_indikator', e.target.value)} margin="normal" helperText="Pisahkan dengan koma" />
                        
                        <Button type="submit" variant="contained" color="success" disabled={processing} sx={{ mt: 2 }}>Simpan</Button>
                    </Box>
                </AccordionDetails>
            </Accordion>

            {/* Tabel Data Indikator */}
            <TableContainer component={Paper} sx={{ overflowX: 'auto' }}>
                <Table size="small">
                    <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                        <TableRow>
                            <TableCell>No</TableCell>
                            <TableCell>Bidang</TableCell>
                            <TableCell>Lingkup</TableCell>
                            <TableCell>Nama Indikator</TableCell>
                            <TableCell>Sub Indikator</TableCell>
                            <TableCell align="center">Aksi</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {indikators?.data?.map((item, index) => (
                            <TableRow key={item.id}>
                                <TableCell>{(indikators.current_page - 1) * indikators.per_page + index + 1}</TableCell>
                                <TableCell>{item.bidang_by_link?.bidang_nama || '-'}</TableCell>
                                <TableCell>{getLingkupLabel(item.lingkup)}</TableCell>
                                <TableCell>{item.indikator_nama}</TableCell>
                                <TableCell>{item.sub_indikator}</TableCell>
                                <TableCell align="center">
                                    <Button size="small" color="warning" onClick={() => handleEditClick(item)} sx={{ minWidth: 'auto', mr: 0.5 }}><EditIcon fontSize="small"/></Button>
                                    <Button size="small" color="error" onClick={() => handleDelete(item.id)} sx={{ minWidth: 'auto' }}><DeleteIcon fontSize="small"/></Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>

            {indikators?.last_page > 1 && (
                <Stack spacing={2} sx={{ mt: 3, alignItems: 'center' }}>
                    <Pagination count={indikators.last_page} page={indikators.current_page} onChange={handlePageChange} color="primary" />
                </Stack>
            )}

            {/* Modal Edit Indikator */}
            <Dialog open={editOpen} onClose={() => setEditOpen(false)} maxWidth="md" fullWidth>
                <DialogTitle>Edit Indikator</DialogTitle>
                <DialogContent>
                    <TextField fullWidth label="Nama Indikator" value={editData.indikator_nama} onChange={e => setEditData({...editData, indikator_nama: e.target.value})} margin="normal" />
                        {/* ... Masukkan field edit lainnya mirip dengan form create ... */}
                        <TextField fullWidth label="Sub Indikator" value={editData.sub_indikator} onChange={e => setEditData({...editData, sub_indikator: e.target.value})} margin="normal" />
                    
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setEditOpen(false)}>Batal</Button>
                    <Button onClick={handleUpdate} variant="contained" color="success">Simpan Perubahan</Button>
                </DialogActions>
            </Dialog>
        </Box>
    );
}