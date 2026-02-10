// resources/js/Components/Pelaporan/CapaianKinerjaTab.jsx
import React, { useState, useEffect } from 'react';
import {
    Grid, Card, CardHeader, CardContent, Button, Box, CircularProgress, 
    Typography, FormControl, InputLabel, Select, MenuItem, Alert 
} from '@mui/material';
import axios from 'axios';

// 1. IMPOR KOMPONEN TABEL READ-ONLY YANG BARU
import SubIndikatorTable from '@/Components/Pelaporan/SubIndikatorTable'; 

// Komponen ini sekarang akan memuat data dan meneruskannya ke SubIndikatorTable
function IndikatorDetails({ rumpun, triwulan }) {
    const [indikators, setIndikators] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!rumpun) return;
        setLoading(true);
        setError(null);
        
        // 2. Panggil rute AJAX dari Blade (sesuai PelaporanController@getSubIndikator2)
        axios.get(`/pelaporan/subindikator/${rumpun}?triwulan=${triwulan}`)
            .then(response => {
                // 'response.data' adalah array indikator yang diharapkan oleh SubIndikatorTable
                setIndikators(response.data);
                setLoading(false);
            })
            .catch(err => {
                console.error("Gagal memuat subindikator:", err);
                setError("Gagal memuat data.");
                setLoading(false);
            });
    }, [rumpun, triwulan]); // Muat ulang saat rumpun atau triwulan berubah

    if (loading) return <CircularProgress />;
    if (error) return <Alert severity="error">{error}</Alert>;
    
    // 3. Render komponen SubIndikatorTable dengan data yang telah diambil
    return <SubIndikatorTable indikators={indikators} triwulan={triwulan} />;
}

// Komponen utama tab (tidak banyak berubah)
export default function CapaianKinerjaTab({ bidangs }) {
    const [selectedRumpun, setSelectedRumpun] = useState(null);
    const [selectedTriwulan, setSelectedTriwulan] = useState(''); // Default Triwulan 1

    return (
        <Grid container spacing={3}>
            {/* Kolom Daftar Bidang */}
            <Grid item xs={12} md={3}>
                <Card elevation={2}>
                    <CardHeader title="Daftar Bidang" sx={{ backgroundColor: 'primary.main', color: 'white' }} />
                    <CardContent>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                            {bidangs.map((bidang) => (
                                <Button
                                    key={bidang.rumpun}
                                    variant={selectedRumpun === bidang.rumpun ? "contained" : "outlined"}
                                    onClick={() => setSelectedRumpun(bidang.rumpun)}
                                >
                                    {bidang.bidang_nama}
                                </Button>
                            ))}
                        </Box>
                    </CardContent>
                </Card>
            </Grid>
            
            {/* Kolom Indikator */}
            <Grid item xs={12} md={9}>
                <Card elevation={2}>
                    <CardHeader
                        title="Indikator"
                        action={
                            <FormControl size="small" sx={{ minWidth: 120 }}>
                                <InputLabel id="tw-select-label">Triwulan</InputLabel>
                                <Select
                                    labelId="tw-select-label"
                                    value={selectedTriwulan}
                                    label="Triwulan"
                                    onChange={(e) => setSelectedTriwulan(e.target.value)}
                                >
                                    <MenuItem value="1">Triwulan 1</MenuItem>
                                    <MenuItem value="2">Triwulan 2</MenuItem>
                                    <MenuItem value="3">Triwulan 3</MenuItem>
                                    <MenuItem value="4">Triwulan 4</MenuItem>
                                </Select>
                            </FormControl>
                        }
                    />
                    <CardContent sx={{ minHeight: 300 }}>
                        {!selectedRumpun ? (
                            <Typography color="text.secondary">Pilih Bidang Terlebih dahulu</Typography>
                        ) : (
                            <IndikatorDetails 
                                rumpun={selectedRumpun} 
                                triwulan={selectedTriwulan}
                            />
                        )}
                    </CardContent>
                </Card>
            </Grid>
        </Grid>
    );
}