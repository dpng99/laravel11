import React from 'react';
import { 
    Box, Typography, TextField, Paper, TableContainer, Table, 
    TableHead, TableBody, TableRow, TableCell 
} from '@mui/material';

// --- Helper Format Angka (Support Koma) ---
const formatInputIndo = (value) => {
    if (value === null || value === undefined || value === '') return '';

    // 1. Pastikan input jadi string
    let str = String(value);

    // 2. Hanya izinkan angka (0-9) dan koma (,)
    // Hapus semua karakter selain angka dan koma
    let clean = str.replace(/[^0-9,]/g, '');

    // 3. Cek apakah ada koma (desimal)
    const parts = clean.split(',');

    // Bagian Integer (Ribuan)
    let integerPart = parts[0];
    
    // Format ribuan dengan titik (hanya jika ada angka)
    if (integerPart.length > 0) {
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Bagian Desimal (Belakang Koma)
    // Jika user mengetik koma, kita ambil sisanya.
    // Limitasi: hanya ambil elemen kedua jika user mengetik banyak koma tak sengaja
    let decimalPart = '';
    if (parts.length > 1) {
        // Gabungkan sisa array untuk jaga-jaga, tapi biasanya cukup ambil index 1
        // Kita batasi desimal agar tidak ada titik di dalamnya
        decimalPart = ',' + parts.slice(1).join(''); 
    }

    return integerPart + decimalPart;
};

// Daftar Bulan & Triwulan
const bulanList = [
    'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
    'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
];
const triwulanList = ['TW1', 'TW2', 'TW3', 'TW4'];

export default function IndikatorForm({ indikator, formData, onUpdate }) {
    
    if (!indikator.sub_indikator) return null;

    const subIndikators = indikator.sub_indikator.split(',').map(s => s.trim());
    
    // Tentukan Label & Mode
    let labels = ['Ditangani', 'Diselesaikan'];
    let mode = 'bulanan';
    
    if (indikator.indikator_penghitungan) {
        const parsedLabels = indikator.indikator_penghitungan.split(',').map(s => s.trim());
        if (parsedLabels.length === 1) {
            labels = parsedLabels;
            mode = 'triwulan';
        } else if (parsedLabels.length > 1) {
            labels = parsedLabels;
            mode = 'bulanan';
        }
    }
    
    // Handler Input
    const handleChange = (sub, key, rawValue, labelIndex = null) => {
        // Format inputan user menjadi format Indonesia (Titik Ribuan, Koma Desimal)
        const formatted = formatInputIndo(rawValue);

        if (mode === 'bulanan' && labelIndex !== null) {
            // Logic Multi-kolom (format: "val1;val2")
            const currentVal = formData[sub]?.[key] || '';
            let parts = currentVal.split(';');
            
            // Pastikan array parts cukup panjang sesuai jumlah label
            while(parts.length < labels.length) parts.push('');
            
            // Update bagian spesifik (ditangani/diselesaikan)
            parts[labelIndex] = formatted;
            
            // Gabung kembali dan update parent state
            onUpdate(sub, key, parts.join(';'));
        } else {
            // Logic Single Value (TW atau Sisa Tahun Lalu)
            onUpdate(sub, key, formatted);
        }
    };

    return (
        <Box sx={{ mb: 4 }}>
            {subIndikators.map(sub => (
                <Paper key={sub} elevation={3} sx={{ p: 3, mb: 3, borderLeft: '5px solid #e6bf3e' }}>
                    <Typography variant="h6" component="div" gutterBottom sx={{ fontWeight: 'bold' }}>
                        {sub}
                    </Typography>
                    
                    {indikator.indikator_penjelasan && (
                        <Typography variant="body2" color="text.secondary" paragraph sx={{ fontStyle: 'italic', bgcolor: '#f9f9f9', p: 1 }}>
                            {indikator.indikator_penjelasan}
                        </Typography>
                    )}

                    {mode === 'bulanan' ? (
                        <>
                            <Box sx={{ mb: 2 }}>
                                <TextField
                                    label="Sisa Tahun Lalu"
                                    variant="outlined"
                                    size="small"
                                    sx={{ width: 200 }}
                                    // Gunakan value langsung dari state (sudah terformat string)
                                    value={formData[sub]?.sisa_tahun_lalu || ''}
                                    onChange={(e) => handleChange(sub, 'sisa_tahun_lalu', e.target.value)}
                                    placeholder="0"
                                    inputProps={{ inputMode: 'decimal' }} // Memunculkan numpad di HP
                                />
                            </Box>
                            
                            <TableContainer sx={{ border: '1px solid #e0e0e0' }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: '#f5f5f5' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 'bold' }}>Uraian</TableCell>
                                            {bulanList.map(bulan => (
                                                <TableCell key={bulan} align="center" sx={{ fontWeight: 'bold', fontSize: '0.75rem' }}>
                                                    {bulan.substring(0, 3)}
                                                </TableCell>
                                            ))}
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {labels.map((label, labelIdx) => (
                                            <TableRow key={label}>
                                                <TableCell>{label}</TableCell>
                                                {bulanList.map((bulan, bulanIdx) => {
                                                    const bulanKe = bulanIdx + 1; // ID Bulan (1-12)
                                                    
                                                    // Ambil data gabungan "val1;val2" dari state
                                                    const combinedVal = formData[sub]?.[bulanKe] || '';
                                                    const parts = combinedVal.split(';');
                                                    const displayVal = parts[labelIdx] || '';

                                                    return (
                                                        <TableCell key={bulan} align="center" sx={{ p: 0.5 }}>
                                                            <TextField
                                                                variant="outlined"
                                                                size="small"
                                                                fullWidth
                                                                value={displayVal}
                                                                onChange={(e) => handleChange(sub, bulanKe, e.target.value, labelIdx)}
                                                                inputProps={{ 
                                                                    style: { textAlign: 'center', fontSize: '0.85rem', padding: '6px' },
                                                                    inputMode: 'decimal'
                                                                }}
                                                                placeholder="-"
                                                            />
                                                        </TableCell>
                                                    );
                                                })}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </>
                    ) : (
                        // --- MODE TRIWULAN ---
                        <TableContainer sx={{ border: '1px solid #e0e0e0' }}>
                            <Table size="small">
                                <TableHead sx={{ bgcolor: '#f5f5f5' }}>
                                    <TableRow>
                                        <TableCell sx={{ fontWeight: 'bold' }}>Label</TableCell>
                                        {triwulanList.map(tw => (
                                            <TableCell key={tw} align="center" sx={{ fontWeight: 'bold' }}>{tw}</TableCell>
                                        ))}
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    <TableRow>
                                        <TableCell>{labels[0]}</TableCell>
                                        {triwulanList.map(tw => (
                                            <TableCell key={tw} align="center">
                                                <TextField
                                                    variant="outlined"
                                                    size="small"
                                                    value={formData[sub]?.[tw] || ''}
                                                    onChange={(e) => handleChange(sub, tw, e.target.value)}
                                                    inputProps={{ 
                                                        style: { textAlign: 'center' },
                                                        inputMode: 'decimal' 
                                                    }}
                                                    placeholder="-"
                                                />
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </TableContainer>
                    )}
                </Paper>
            ))}
        </Box>
    );
}