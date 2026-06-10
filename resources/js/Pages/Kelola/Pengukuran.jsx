import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardContent, Grid, Button, Box, Typography, Paper, Tab, Tabs } from '@mui/material';
import IndikatorContent from '@/Components/Pengukuran/IndikatorContent';
import IkssParameterTab from '@/Components/Pelaporan/IkssParameterTab';

export default function Pengukuran() {
    // Terima props dari Controller
    const { auth, tahun, bidangs } = usePage().props;
    
    const [selectedRumpun, setSelectedRumpun] = useState(null);
    const [activeTab, setActiveTab] = useState('parameter-ss-ikss');
    const daftarBidang = bidangs || []; 
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);

    return (
        <AuthenticatedLayout>
            <Head title="Pengukuran" />

            <Paper elevation={3} sx={{ m: 3, p: 0, overflow: 'hidden' }}>
                <Box sx={{ backgroundColor: '#e6bf3e', p: 2, color: 'white' }}>
                    <Typography variant="h5" align="center" fontWeight="bold">
                        Pengukuran Kinerja Tahun {tahun}
                    </Typography>
                </Box>
                
                <Box sx={{ p: 3 }}>
                    <Tabs
                        value={activeTab}
                        onChange={(_, value) => setActiveTab(value)}
                        variant="scrollable"
                        scrollButtons="auto"
                        sx={{ mb: 3, borderBottom: '1px solid #e0e0e0' }}
                    >
                        <Tab label="Parameter SS / IKSS" value="parameter-ss-ikss" />
                        <Tab label="Pengukuran Indikator Bidang" value="indikator-bidang" />
                    </Tabs>

                    {activeTab === 'parameter-ss-ikss' ? (
                        <IkssParameterTab tahun={tahun} levelSakip={levelSakip} />
                    ) : (
                    <Grid container spacing={3}>
                        {/* Kolom Daftar Bidang (Kiri) */}
                        <Grid item xs={12} md={3}>
                            <Card elevation={2}>
                                <CardHeader 
                                    title="Daftar Bidang" 
                                    sx={{ backgroundColor: '#f0bb49', p: 1.5 }} 
                                    titleTypographyProps={{ variant: 'subtitle1', fontWeight: 'bold' }}
                                />
                                <CardContent sx={{ p: 1 }}>
                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                                        {daftarBidang.map((bidang) => (
                                            <Button
                                                key={bidang.rumpun}
                                                variant={selectedRumpun === bidang.rumpun ? "contained" : "outlined"}
                                                // Logika warna agar terlihat aktif
                                                sx={{ 
                                                    justifyContent: 'flex-start', 
                                                    textAlign: 'left',
                                                    backgroundColor: selectedRumpun === bidang.rumpun ? '#e6bf3e' : 'transparent',
                                                    color: selectedRumpun === bidang.rumpun ? 'white' : '#e6bf3e',
                                                    borderColor: '#e6bf3e',
                                                    '&:hover': {
                                                        backgroundColor: '#d4af37',
                                                        color: 'white',
                                                        borderColor: '#d4af37'
                                                    }
                                                }}
                                                onClick={() => setSelectedRumpun(bidang.rumpun)}
                                            >
                                                {bidang.bidang_nama}
                                            </Button>
                                        ))}
                                    </Box>
                                </CardContent>
                            </Card>
                        </Grid>

                        {/* Kolom Konten Indikator (Kanan) */}
                        <Grid item xs={12} md={9}>
                            <Card elevation={2}>
                                <CardHeader 
                                    title="Indikator Kinerja" 
                                    sx={{ backgroundColor: '#f0bb49', p: 1.5 }} 
                                    titleTypographyProps={{ variant: 'subtitle1', fontWeight: 'bold' }}
                                />
                                <CardContent>
                                    {selectedRumpun ? (
                                        <IndikatorContent 
                                            rumpun={selectedRumpun} 
                                            tahun={tahun}
                                            idSatker={auth.user.id_satker}
                                        />
                                    ) : (
                                        <Typography color="text.secondary" align="center" sx={{ py: 5 }}>
                                            Silakan pilih bidang di sebelah kiri untuk melihat dan mengisi indikator.
                                        </Typography>
                                    )}
                                </CardContent>
                            </Card>
                        </Grid>
                    </Grid>
                    )}
                </Box>
            </Paper>
        </AuthenticatedLayout>
    );
}
