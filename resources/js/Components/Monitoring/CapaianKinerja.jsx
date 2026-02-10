// resources/js/Components/Monitoring/CapaianKinerja.jsx
import React, { useState, useEffect } from 'react';
import { Grid, Card, CardHeader, CardContent, Button, Box, Typography, CircularProgress, Alert } from '@mui/material';
import axios from 'axios';
import IndikatorDetails from './IndikatorDetails'; // Komponen anak

export default function CapaianKinerja({ selectedSatker }) {
    const [bidangs, setBidangs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedRumpun, setSelectedRumpun] = useState(null);
    const [bidangLokasi, setBidangLokasi] = useState(null);

    // 1. Load daftar bidang saat komponen dimuat (atau saat satker berubah)
    useEffect(() => {
        if (!selectedSatker) return;
        setLoading(true);
        setError(null);
        setSelectedRumpun(null); // Reset pilihan bidang

        axios.get(`/monitoring/bidang/${selectedSatker.id_satker}`)
            .then(response => {
                setBidangs(response.data);
                if(response.data.length > 0){
                    setBidangLokasi(response.data[0].bidang_lokasi); // Pilih bidang pertama secara default
                }
                setLoading(false);
                
            })
            .catch(err => {
                console.error("Gagal memuat bidang:", err);
                setError("Gagal memuat daftar bidang.");
                setLoading(false);
            });
    }, [selectedSatker]);

    if (loading) return <CircularProgress />;
    if (error) return <Alert severity="error">{error}</Alert>;

    return (
        <Grid container spacing={3}>
            {/* Kolom Daftar Bidang */}
            <Grid item xs={12} md={3}>
                <Card elevation={2}>
                    <CardHeader title="Daftar Bidang" sx={{ backgroundColor: '#f0bb49' }} />
                    <CardContent>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                            {bidangs.map((bidang) => (
                                <Button
                                    key={bidang.bidang_lokasi === bidangLokasi}
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

            {/* Kolom Konten Indikator */}
            <Grid item xs={12} md={9}>
                <Card elevation={2} sx={{ minHeight: '300px' }}>
                    <IndikatorDetails 
                        rumpun={selectedRumpun} 
                        idSatker={selectedSatker.id_satker} 
                    />
                </Card>
            </Grid>
        </Grid>
    );
}