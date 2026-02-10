import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { 
    Box, Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, 
    Paper, TextField, Select, MenuItem, FormControl, InputLabel, Accordion, 
    AccordionSummary, AccordionDetails, Typography, Pagination, Stack, Dialog,
    DialogTitle, DialogContent, DialogActions
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

export default function SasproTab({ saspros, bidangAll }) {
    const { data, setData, post, processing, reset } = useForm({
        link: '', 
        saspro_nama: '', 
        penjelasan_saspro: '', 
        lingkup: '0', // Default 0 sesuai gambar
        target: '',   // Kolom target
        tahun: '', 
        hide: '0'
    });

    const [editOpen, setEditOpen] = useState(false);
    const [editData, setEditData] = useState({});

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('saspro.store'), { onSuccess: () => reset(), preserveScroll: true });
    };
    const getLingkupLabel = (val) => {
        const map = {
            0: 'Semua Satker', 1: 'Pusat', 2: 'Kejati', 3: 'Kejari', 
            4: 'Cabjari', 5: 'Kejati, Kejari', 6: 'Kejari, Cabjari', 7: 'Kejati, Kejari, Cabjari'
        };
        return map[val] || val;
    };
    const handleEditClick = (item) => {
        setEditData({
            id: item.id,
            link: item.link, 
            saspro_nama: item.saspro_nama,
            penjelasan_saspro: item.saspro_penjelasan,
            tahun: item.tahun,
            lingkup: item.lingkup,
            target: item.target,
            hide: item.hide
        });
        setEditOpen(true);
    };

    const handleUpdate = () => {
        router.post(route('saspro.update', editData.id), editData, {
            onSuccess: () => setEditOpen(false),
            preserveScroll: true
        });
    };

    const handleDelete = (id) => {
        if(confirm('Yakin ingin menghapus Saspro ini?')) {
            router.delete(route('saspro.destroy', id), { preserveScroll: true });
        }
    };

    const handlePageChange = (event, value) => {
        router.get(saspros.path, { page: value }, { preserveScroll: true, preserveState: true, only: ['saspros'] });
    };

    return (
        <Box>
            {/* Form Input Saspro */}
            <Accordion defaultExpanded={false} sx={{ mb: 4, border: '1px solid #ddd' }}>
                <AccordionSummary expandIcon={<ExpandMoreIcon sx={{ color: 'white' }} />} sx={{ backgroundColor: '#ffcc00' }}>
                    <Typography fontWeight="bold">Input Data Saspro</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Box component="form" onSubmit={handleSubmit} sx={{ mt: 2 }}>
                        <FormControl fullWidth margin="normal">
                            <InputLabel>Bidang</InputLabel>
                            <Select value={data.link} label="Bidang" onChange={e => setData('link', e.target.value)} required>
                                {bidangAll.map((bidang) => (
                                    <MenuItem key={bidang.id} value={bidang.id}>
                                        {bidang.bidang_nama}
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                        <TextField fullWidth label="Nama Saspro" value={data.saspro_nama} onChange={e => setData('saspro_nama', e.target.value)} margin="normal" required />
                        <TextField fullWidth multiline rows={3} label="Penjelasan" value={data.penjelasan_saspro} onChange={e => setData('penjelasan_saspro', e.target.value)} margin="normal" />
                        <TextField fullWidth label="Target" value={data.target} onChange={e => setData('target', e.target.value)} margin="normal" required />
                        <FormControl fullWidth margin="normal">
                                                            <InputLabel>Lingkup</InputLabel>
                                                            <Select value={data.lingkup} label="Lingkup" onChange={e => setData('lingkup', e.target.value)}>
                                                                {[0,1,2,3,4,5,6,7].map(val => (<MenuItem key={val} value={val}>{getLingkupLabel(val)}</MenuItem>))}
                                                            </Select>
                        </FormControl>
                        <TextField fullWidth label="Tahun" value={data.tahun} onChange={e => setData('tahun', e.target.value)} margin="normal" required />
                        
                        <Button type="submit" variant="contained" color="success" disabled={processing} sx={{ mt: 2 }}>Simpan</Button>
                    </Box>
                </AccordionDetails>
            </Accordion>

            {/* Tabel Data Saspro */}
            <TableContainer component={Paper}>
                <Table size="small">
                    <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                        <TableRow>
                            <TableCell>No</TableCell>
                            <TableCell>Lingkup</TableCell>
                            <TableCell>Nama Saspro</TableCell>
                            <TableCell>Penjelasan</TableCell>
                            <TableCell>Target</TableCell>
                            <TableCell>Lingkup</TableCell>
                            <TableCell>Tahun</TableCell>
                            <TableCell align="center">Aksi</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {saspros?.data?.map((item, index) => (
                            <TableRow key={item.id}>
                                <TableCell>{(saspros.current_page - 1) * saspros.per_page + index + 1}</TableCell>
                                <TableCell>{item.bidang?.bidang_nama || '-'}</TableCell>
                                <TableCell>{item.saspro_nama}</TableCell>
                                <TableCell>{item.saspro_penjelasan}</TableCell>
                                <TableCell>{item.target}</TableCell>
                                <TableCell>{item.lingkup}</TableCell>
                                <TableCell>{item.tahun}</TableCell>
                                <TableCell align="center">
                                    <Button size="small" color="warning" onClick={() => handleEditClick(item)} sx={{minWidth:'auto', mr:1}}><EditIcon/></Button>
                                    <Button size="small" color="error" onClick={() => handleDelete(item.id)} sx={{minWidth:'auto'}}><DeleteIcon/></Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>

            {saspros?.last_page > 1 && (
                <Stack spacing={2} sx={{ mt: 3, alignItems: 'center' }}>
                    <Pagination count={saspros.last_page} page={saspros.current_page} onChange={handlePageChange} color="primary" />
                </Stack>
            )}

            {/* Modal Edit Saspro */}
            <Dialog open={editOpen} onClose={() => setEditOpen(false)} maxWidth="md" fullWidth>
                <DialogTitle>Edit Saspro</DialogTitle>
                <DialogContent>
                    <Box component="form" sx={{ mt: 1 }}>
                         <FormControl fullWidth margin="normal">
                            <InputLabel>Bidang</InputLabel>
                            <Select value={editData.link} label="Bidang" onChange={e => setEditData({...editData, link: e.target.value})}>
                                {bidangAll.map((bidang) => (
                                    <MenuItem key={bidang.id} value={bidang.id}>{bidang.bidang_nama}</MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                        <TextField fullWidth label="Nama Saspro" value={editData.saspro_nama} onChange={e => setEditData({...editData, saspro_nama: e.target.value})} margin="normal" />
                        <TextField fullWidth multiline rows={3} label="Penjelasan" value={editData.penjelasan_saspro} onChange={e => setEditData({...editData, penjelasan_saspro: e.target.value})} margin="normal" />
                        <TextField fullWidth label="Tahun" value={editData.tahun} onChange={e => setEditData({...editData, tahun: e.target.value})} margin="normal" />
                         <FormControl fullWidth margin="normal">
                            <InputLabel>Status</InputLabel>
                            <Select value={editData.hide} label="Status" onChange={e => setEditData({...editData, hide: e.target.value})}>
                                <MenuItem value={0}>Tampil</MenuItem>
                                <MenuItem value={1}>Sembunyikan</MenuItem>
                            </Select>
                        </FormControl>
                    </Box>
                </DialogContent>
               <DialogActions>
                    <Button onClick={() => setEditOpen(false)}>Batal</Button>
                    <Button onClick={handleUpdate} variant="contained" color="success">Simpan</Button>
                </DialogActions>
            </Dialog>
        </Box>
    );
}