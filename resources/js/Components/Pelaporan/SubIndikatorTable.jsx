// resources/js/Components/Pelaporan/SubIndikatorTable.jsx
import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Box,
    Typography,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    TextField,
    Select,
    MenuItem,
    FormControl,
    Grid,
    Button,
    Alert,
    CircularProgress
} from '@mui/material';
import SaveIcon from '@mui/icons-material/Save';

export default function SubIndikatorTable({ indikators = [], triwulan }) {
    // 1. Simpan data ke dalam State agar bisa diedit
    const [dataItems, setDataItems] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [message, setMessage] = useState(null);
    const [triwulanItem, setTriwulanItem] = useState([]); // Jika perlu, bisa diteruskan dari props

    // Sinkronkan state jika props indikators berubah (misal ganti triwulan/bidang)
    useEffect(() => {
        setTriwulanItem(triwulan);
        setDataItems(indikators);
    }, [indikators, triwulan]);

    // 2. Handler untuk mengubah nilai input (Two-way binding)
    const handleInputChange = (index, field, value) => {
        const newData = [...dataItems];
        newData[index] = { ...newData[index], [field]: value }; // Update field spesifik
        setDataItems(newData);
    };

    // 3. Fungsi Simpan Data
   const handleSave = () => {
        // [PENTING] Cek dulu apakah triwulan ada isinya
        if (!triwulan) {
            alert("Error: Triwulan belum dipilih/terbaca.");
            return;
        }

        setProcessing(true);

        // Ganti 'nama.route.anda' dengan nama route di web.php yang mengarah ke simpanKeterangan
        // Contoh: route('pelaporan.simpan_keterangan')
        router.post(route('pelaporan.simpan_keterangan'), {
            
            // 1. Kirim Array Data (Isian tabel)
            data: dataItems, 
            
            // 2. [WAJIB] Kirim Triwulan dari props
            triwulan: triwulan, 

        }, {
            preserveScroll: true,
            onSuccess: () => {
                setProcessing(false);
                alert('Keterangan berhasil disimpan!'); 
            },
            onError: (errors) => {
                setProcessing(false);
                console.error("Gagal simpan:", errors);
                alert('Gagal menyimpan data.');
            }
        });
    };

    if (!indikators || indikators.length === 0) {
        return <Typography sx={{ mt: 2, fontStyle: 'italic' }}>Tidak ada data indikator untuk ditampilkan.</Typography>;
    }

    return (
        <Box>
            {message && (
                <Alert severity={message.type} sx={{ mb: 2 }}>{message.text}</Alert>
            )}

            {dataItems.map((item, index) => (
                <Paper key={index} elevation={3} sx={{ mb: 3, p: 3, borderLeft: '6px solid #e6bf3e' }}>
                    {/* Judul Indikator */}
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                        <Typography variant="h6" component="strong">
                            {item.indikator_nama}
                        </Typography>
                        {/* Tombol Simpan Per Item */}
                        <Button 
                            variant="contained" 
                            color="primary" 
                            startIcon={processing ? <CircularProgress size={20} color="inherit" /> : <SaveIcon />}
                            onClick={() => handleSave(item)}
                            disabled={processing}
                        >
                            Simpan
                        </Button>
                    </Box>

                    {/* Tabel Data Angka */}
                    <TableContainer component={Paper} variant="outlined" sx={{ mb: 3 }}>
                        <Table size="small">
                            <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                                <TableRow>
                                    <TableCell align="center"><b>Jumlah Ditangani</b></TableCell>
                                    <TableCell align="center"><b>Jumlah Diselesaikan</b></TableCell>
                                    <TableCell align="center"><b>Persentase</b></TableCell>
                                    <TableCell align="center"><b>Target PK TW</b></TableCell>
                                    <TableCell align="center"><b>Capaian Target</b></TableCell>
                                    <TableCell align="center"><b>Trend</b></TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                <TableRow>
                                    {/* CATATAN: 
                                        Jika angka ini hasil hitungan otomatis dari 'Pengukuran', 
                                        sebaiknya biarkan readOnly={true}.
                                        Jika ingin diedit, hapus InputProps={{ readOnly: true }} 
                                        dan tambahkan onChange.
                                    */}
                                    <TableCell>
                                        <TextField
                                            fullWidth size="small"
                                            value={item.total_ditangani || 0}
                                            readOnly={true}// Angka biasanya otomatis
                                            style={{ textAlign: 'center' }}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <TextField
                                            fullWidth size="small"
                                            value={item.total_diselesaikan || 0}
                                           readOnly={true}// Angka biasanya otomatis
                                            style={{ textAlign: 'center' }}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <TextField
                                            fullWidth size="small"
                                            value={(item.persentase || 0) + '%'}
                                           readOnly={true}// Angka biasanya otomatis
                                            style={{ textAlign: 'center' }}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <TextField
                                            fullWidth size="small"
                                            value={(item.target_pk || 0) + '%'}
                                           readOnly={true}// Angka biasanya otomatis
                                            style={{ textAlign: 'center' }}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <TextField
                                            fullWidth size="small"
                                            value={(item.capaian_pk || 0) + '%'}
                                           readOnly={true}// Angka biasanya otomatis
                                            style={{ textAlign: 'center' }}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <FormControl fullWidth size="small">
                                            <Select
                                                value={item.tren || ''} 
                                                displayEmpty
                                                inputProps={{ readOnly: true }} // Trend biasanya otomatis
                                            >
                                                <MenuItem value="" disabled><em>Pilih...</em></MenuItem>
                                                <MenuItem value="Naik">Naik</MenuItem>
                                                <MenuItem value="Turun">Turun</MenuItem>
                                                <MenuItem value="Tetap">Tetap</MenuItem>
                                            </Select>
                                        </FormControl>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </TableContainer>

                    {/* Area Input Faktor & Upaya (INI YANG BISA DIEDIT) */}
                    <Grid container spacing={3}>
                        <Grid item xs={12} md={6}>
                            <Typography variant="subtitle2" gutterBottom>Faktor-Faktor yang mempengaruhi:</Typography>
                            <TextField
                                placeholder="Ketikan faktor penghambat/pendukung..."
                                multiline
                                rows={3}
                                fullWidth
                                // HAPUS readOnly DI SINI
                                value={item.faktor || ''} 
                                onChange={(e) => handleInputChange(index, 'faktor', e.target.value)}
                                sx={{ backgroundColor: '#fff' }}
                            />
                        </Grid>
                        <Grid item xs={12} md={6}>
                            <Typography variant="subtitle2" gutterBottom>Upaya optimalisasi kinerja:</Typography>
                            <TextField
                                placeholder="Ketikan upaya tindak lanjut..."
                                multiline
                                rows={3}
                                fullWidth
                                // HAPUS readOnly DI SINI
                                value={item.langkah_optimalisasi || ''} 
                                onChange={(e) => handleInputChange(index, 'langkah_optimalisasi', e.target.value)}
                                sx={{ backgroundColor: '#fff' }}
                            />
                        </Grid>
                    </Grid>
                </Paper>
            ))}
        </Box>
    );
}