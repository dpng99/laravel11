// resources/js/Components/SakipWil/SakipWilTable.jsx
import React, { useState } from 'react';
import { 
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow, 
    Paper, TextField, InputAdornment, Box, IconButton, Tooltip, Button 
} from '@mui/material';
import SearchIcon from '@mui/icons-material/Search';
import CheckCircleIcon from '@mui/icons-material/CheckCircle'; // Centang
import CancelIcon from '@mui/icons-material/Cancel'; // Silang
import DownloadIcon from '@mui/icons-material/Download';
import * as XLSX from 'xlsx'; // Import xlsx

// Helper untuk link file
const FileLink = ({ fileData, satkerId, fileName }) => {
    if (fileData) {
        // Asumsi struktur data: { id_satker: '...', id_filename: '...' }
        // Jika data hanya nama file (dari sorted list), gunakan itu
        const link = typeof fileData === 'string' ? fileData : fileData.id_filename;
        if (!link) return <CancelIcon color="error" fontSize="small" />;
        
        return (
           // 🔽 PERUBAHAN DI SINI: Arahkan ke route /file/view 🔽
            <a href={`/file/view/${satkerId}/${encodeURIComponent(link)}`} target="_blank" rel="noopener noreferrer">
                <CheckCircleIcon color="success" />
            </a>
        );
    }
    return <CancelIcon color="error" fontSize="small" />;
};


export default function SakipWilTable({ data, ...props }) {
    const [filter, setFilter] = useState('');

    const handleFilterChange = (event) => {
        setFilter(event.target.value.toLowerCase());
    };

    // Filter data berdasarkan input pencarian
    const filteredData = data.filter(row => 
        row.satkernama.toLowerCase().includes(filter)
    );

    // Fungsi Export Excel
    const handleExportExcel = () => {
        const table = document.getElementById('sakipWilTable'); // Beri ID pada tabel
        const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
        XLSX.writeFile(wb, "data_sakip_wilayah.xlsx");
    };

    return (
        <Paper sx={{ p: 2, overflow: 'hidden' }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 2 }}>
                <TextField
                    label="Cari Nama Satker"
                    variant="outlined"
                    size="small"
                    value={filter}
                    onChange={handleFilterChange}
                    InputProps={{
                        startAdornment: (
                            <InputAdornment position="start">
                                <SearchIcon />
                            </InputAdornment>
                        ),
                    }}
                />
                <Tooltip title="Export ke Excel">
                    <Button
                        variant="contained"
                        color="success"
                        onClick={handleExportExcel}
                        startIcon={<DownloadIcon />}
                    >
                        Export Excel
                    </Button>
                </Tooltip>
            </Box>
            
            <TableContainer sx={{ maxHeight: 800 }}>
                <Table stickyHeader id="sakipWilTable"> {/* Beri ID untuk export */}
                    <TableHead>
                        <TableRow sx={{ '& th': { backgroundColor: 'primary.main', color: 'white' } }}>
                            <TableCell>No</TableCell>
                            <TableCell>Nama Satker</TableCell>
                            <TableCell>Renstra</TableCell>
                            <TableCell>IKU</TableCell>
                            <TableCell>Renja</TableCell>
                            <TableCell>RKAKL</TableCell>
                            <TableCell>Dipa</TableCell>
                            <TableCell>Renaksi</TableCell>
                            <TableCell>PK</TableCell>
                            <TableCell>LKJIP TW1</TableCell>
                            <TableCell>LKJIP TW2</TableCell>
                            <TableCell>LKJIP TW3</TableCell>
                            <TableCell>LKJIP TW4</TableCell>
                            <TableCell>Rapat Staff</TableCell>
                            <TableCell>LHE AKIP</TableCell>
                            <TableCell>TL LHE AKIP</TableCell>
                            <TableCell>Monev Renaksi</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {filteredData.length > 0 ? (
                            filteredData.map((row, index) => (
                                <TableRow key={row.id_satker} hover>
                                    <TableCell>{index + 1}</TableCell>
                                    <TableCell align="left" sx={{ fontWeight: row.id_kejari == 0 ? 'bold' : 'normal' }}>
                                        {row.satkernama.replace(/_/g, ' ')}
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.renstra[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.iku[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.renja[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.rkakl[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.dipa[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.renaksi[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.pk[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.sortedLkjipTW1[row.id_satker]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.sortedLkjipTW2[row.id_satker]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.sortedLkjipTW3[row.id_satker]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.sortedLkjipTW4[row.id_satker]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.rastaff[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.lhe[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.tl_lhe_akip[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                    <TableCell>
                                        <FileLink fileData={props.monev_renaksi[row.id_satker]?.[0]} satkerId={row.id_satker} />
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={17} align="center">
                                    Tidak ada data yang tersedia atau cocok dengan pencarian.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>
        </Paper>
    );
}