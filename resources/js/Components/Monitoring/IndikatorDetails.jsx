// resources/js/Components/Monitoring/IndikatorDetails.jsx
import React, { useState, useEffect } from 'react';
import { 
    Box, CircularProgress, Typography, Alert, FormControl, InputLabel, 
    Select, MenuItem, CardHeader, CardContent,
    Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper
} from '@mui/material';
import axios from 'axios';

export default function IndikatorDetails({ rumpun, idSatker }) {
    const [triwulan, setTriwulan] = useState('1');
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // 2. Load data indikator saat rumpun atau triwulan berubah
    useEffect(() => {
        if (!rumpun) return; // Jangan lakukan apa-apa jika tidak ada rumpun dipilih
        
        setLoading(true);
        setError(null);
        setData([]);

        axios.get(`/monitoring/subindikator2/${rumpun}`, {
            params: {
                triwulan: triwulan,
                id_satker: idSatker
            }
        })
        .then(response => {
            setData(response.data);
            setLoading(false);
        })
        .catch(err => {
            console.error("Gagal memuat subindikator:", err);
            setError("Gagal memuat data indikator.");
            setLoading(false);
        });
    }, [rumpun, triwulan, idSatker]); // Dependensi

    if (!rumpun) {
        return (
            <CardContent>
                <Alert severity="info">Pilih Bidang Terlebih dahulu</Alert>
            </CardContent>
        );
    }

    return (
        <>
            <CardHeader
                title="Indikator"
                action={
                    <FormControl size="small" sx={{ minWidth: 120 }}>
                        <InputLabel id="tw-select-label">Triwulan</InputLabel>
                        <Select
                            labelId="tw-select-label"
                            value={triwulan}
                            label="Triwulan"
                            onChange={(e) => setTriwulan(e.target.value)}
                        >
                            <MenuItem value="1">Triwulan 1</MenuItem>
                            <MenuItem value="2">Triwulan 2</MenuItem>
                            <MenuItem value="3">Triwulan 3</MenuItem>
                            <MenuItem value="4">Triwulan 4</MenuItem>
                        </Select>
                    </FormControl>
                }
                sx={{ backgroundColor: '#f0bb49' }}
            />
            <CardContent>
                {loading && <Box sx={{ display: 'flex', justifyContent: 'center', p: 5 }}><CircularProgress /></Box>}
                {error && <Alert severity="error">{error}</Alert>}
                
                {!loading && !error && (
                    data.length === 0 ? (
                        <Alert severity="warning">Tidak ada data</Alert>
                    ) : (
                        <TableContainer component={Paper}>
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Indikator</TableCell>
                                        <TableCell>Capaian</TableCell>
                                        <TableCell>Target</TableCell>
                                        <TableCell>Capaian thd Target</TableCell>
                                        <TableCell>Faktor</TableCell>
                                        <TableCell>Upaya</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.map((row, i) => (
                                        <TableRow key={i}>
                                            <TableCell>{row.indikator_nama}</TableCell>
                                            <TableCell>{row.persentase}%</TableCell>
                                            <TableCell>{row.target_pk}%</TableCell>
                                            <TableCell>{row.capaian_pk}%</TableCell>
                                            <TableCell>{row.faktor || '-'}</TableCell>
                                            <TableCell>{row.langkah || '-'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    )
                )}
            </CardContent>
        </>
    );
}