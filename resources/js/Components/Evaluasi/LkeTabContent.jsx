import React, { useState, useMemo } from 'react';
import { 
    Box, TableContainer, Table, TableHead, TableBody, TableRow, TableCell, Paper, 
    Button, List, ListItem, ListItemText, ListItemIcon, Typography, Chip 
} from '@mui/material';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import CancelIcon from '@mui/icons-material/Cancel';
import InfoIcon from '@mui/icons-material/Info';
import LkeModal from './LkeModal';

export default function LkeTabContent({ lkeGrouped }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedRow, setSelectedRow] = useState(null);

    // Handler untuk membuka modal
    const handleOpenModal = (row) => {
        setSelectedRow(row);
        setModalOpen(true);
    };

    // === LOGIKA ROWSPAN UTAMA ===
    // Mengubah struktur object bersarang (Nested) menjadi array datar (Flat) 
    // agar bisa dirender oleh tabel HTML standar, sambil menghitung rowspan.
    const tableRows = useMemo(() => {
        let rows = [];
        let no = 1;

        if (!lkeGrouped) return [];

        // 1. Loop Level Komponen
        Object.keys(lkeGrouped).forEach(komponenId => {
            const subKomponens = lkeGrouped[komponenId];
            const subKeys = Object.keys(subKomponens);
            
            // Hitung total baris yang dibutuhkan untuk rowspan Komponen
            // (Jumlah semua kriteria di dalam semua subkomponen miliknya)
            let totalKomponenRows = 0;
            subKeys.forEach(k => {
                totalKomponenRows += subKomponens[k].length;
            });

            let isFirstKomponen = true; // Penanda baris pertama di grup Komponen

            // 2. Loop Level Sub Komponen
            subKeys.forEach(subId => {
                const kriterias = subKomponens[subId];
                let isFirstSub = true; // Penanda baris pertama di grup Sub Komponen

                // 3. Loop Level Kriteria (Baris Data Asli)
                kriterias.forEach((kriteria) => {
                    rows.push({
                        ...kriteria,
                        // Properti khusus untuk mengatur rowspan di HTML
                        rowspanKomponen: isFirstKomponen ? totalKomponenRows : 0,
                        rowspanSub: isFirstSub ? kriterias.length : 0,
                        displayNo: isFirstKomponen ? no : null,
                        id_sub_komponen: kriteria.id_sub_komponen
                    });
                    
                    // Setelah baris pertama dicatat, matikan flag agar baris berikutnya tidak membuat cell baru
                    isFirstKomponen = false; 
                    isFirstSub = false;
                });
            });
            no++;
        });
        return rows;
    }, [lkeGrouped]);

    return (
        <Box>
            <TableContainer component={Paper} sx={{ mt: 2, maxHeight: '75vh', boxShadow: 3 }}>
                <Table size="small" stickyHeader>
                    <TableHead>
                        <TableRow sx={{ '& th': { backgroundColor: '#e6bf3e', color: 'black', fontWeight: 'bold', fontSize: '0.85rem' } }}>
                            <TableCell width="5%" align="center">No</TableCell>
                            <TableCell width="15%">Komponen</TableCell>
                            <TableCell width="15%">Sub Komponen</TableCell>
                            <TableCell width="8%" align="center">Kode</TableCell>
                            <TableCell width="30%">Kriteria</TableCell>
                            <TableCell width="20%">Status Bukti Dukung</TableCell>
                            <TableCell width="7%" align="center">Aksi</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {tableRows.map((row, idx) => (
                            <TableRow key={idx} hover>
                                
                                {/* KOLOM 1: NOMOR (Merged) */}
                                {row.rowspanKomponen > 0 && (
                                    <TableCell 
                                        rowSpan={row.rowspanKomponen} 
                                        align="center" 
                                        sx={{ verticalAlign: 'top', fontWeight: 'bold', bgcolor: '#fffdf0', borderRight: '1px solid #e0e0e0' }}
                                    >
                                        {row.displayNo}
                                    </TableCell>
                                )}

                                {/* KOLOM 2: NAMA KOMPONEN (Merged) */}
                                {row.rowspanKomponen > 0 && (
                                    <TableCell 
                                        rowSpan={row.rowspanKomponen} 
                                        sx={{ verticalAlign: 'top', fontWeight: 'bold', bgcolor: '#fffdf0', borderRight: '1px solid #e0e0e0' }}
                                    >
                                        {row.nama_komponen}
                                    </TableCell>
                                )}

                                {/* KOLOM 3: NAMA SUB KOMPONEN (Merged) */}
                                {row.rowspanSub > 0 && (
                                    <TableCell 
                                        rowSpan={row.rowspanSub} 
                                        sx={{ verticalAlign: 'top', bgcolor: '#fafafa', borderRight: '1px solid #e0e0e0' }}
                                    >
                                        {row.nama_subkomponen}
                                    </TableCell>
                                )}
                                
                                {/* KOLOM 4: KODE KRITERIA */}
                                <TableCell align="center" sx={{ verticalAlign: 'top' }}>
                                    <Chip label={row.kode_kriteria} size="small" variant="outlined" />
                                </TableCell>

                                {/* KOLOM 5: NAMA KRITERIA */}
                                <TableCell sx={{ verticalAlign: 'top' }}>
                                    {row.nama_kriteria}
                                </TableCell>
                                
                                {/* KOLOM 6: STATUS BUKTI DUKUNG */}
                                <TableCell sx={{ verticalAlign: 'top' }}>
                                    <List dense disablePadding>
                                        {row.bukti_list && row.bukti_list.map((bukti, i) => (
                                            <ListItem key={i} disableGutters sx={{ py: 0.5, borderBottom: '1px dashed #eee' }}>
                                                <ListItemIcon sx={{ minWidth: 28 }}>
                                                    {bukti.status === 'Ada' ? (
                                                        <CheckCircleIcon color="success" fontSize="small" />
                                                    ) : bukti.status === 'Tersedia di Sistem (Belum Verif)' ? (
                                                        <InfoIcon color="warning" fontSize="small" />
                                                    ) : (
                                                        <CancelIcon color="error" fontSize="small" />
                                                    )}
                                                </ListItemIcon>
                                                <ListItemText 
                                                    primary={
                                                        <Typography variant="caption" sx={{ fontSize: '0.75rem', fontWeight: bukti.status === 'Ada' ? 'bold' : 'normal' }}>
                                                            {bukti.nama_dokumen}
                                                        </Typography>
                                                    }
                                                    secondary={
                                                        bukti.status === 'Ada' ? (
                                                            <a href={bukti.file_link} target="_blank" rel="noreferrer" style={{ textDecoration: 'none', color: '#1976d2', fontSize: '0.7rem' }}>
                                                                [Lihat File]
                                                            </a>
                                                        ) : null
                                                    }
                                                />
                                            </ListItem>
                                        ))}
                                    </List>
                                </TableCell>

                                {/* KOLOM 7: TOMBOL AKSI */}
                                <TableCell align="center" sx={{ verticalAlign: 'top' }}>
                                    <Button 
                                        variant="contained" 
                                        size="small" 
                                        color="warning"
                                        onClick={() => handleOpenModal(row)}
                                        sx={{ fontSize: '0.7rem', textTransform: 'none', minWidth: '60px', color: 'white' }}
                                    >
                                        Kelola
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Modal ditempatkan disini agar tidak dirender berulang kali di dalam loop */}
            {selectedRow && (
                <LkeModal 
                    open={modalOpen} 
                    onClose={() => setModalOpen(false)} 
                    data={selectedRow}
                />
            )}
        </Box>
    );
}