// resources/js/Pages/Dashboard.jsx
import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'; // Layout MUI Anda

// Import Komponen Material-UI
import { Card, CardContent, CardHeader, Typography, Grid, Button, Box, Paper } from '@mui/material';

// Helper komponen untuk Kartu Status Kepatuhan
function StatusCard({ title, isFilled, textFilled, textNotFilled }) {
    return (
        <Paper 
            elevation={3} 
            sx={{ 
                p: 2, 
                color: 'white', 
                backgroundColor: isFilled ? 'success.main' : 'error.main' // Hijau atau Merah
            }}
        >
            <Typography variant="h6" component="h3" sx={{ fontWeight: 'bold' }}>
                {title}
            </Typography>
            <Typography variant="body2">
                {isFilled ? textFilled : textNotFilled}
            </Typography>
        </Paper>
    );
}

// Komponen helper untuk animasi
function AnimatedCard({ children, index = 0 }) {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            setIsVisible(true);
        }, index * 100);
        return () => clearTimeout(timer);
    }, [index]);
    
    // Gunakan style inline untuk transisi, bukan class
    return (
        <Card sx={{ 
            mb: 3, 
            opacity: isVisible ? 1 : 0,
            transform: isVisible ? 'translateY(0)' : 'translateY(50px)',
            transition: 'all 0.6s ease-out',
        }} 
        elevation={3}
        >
            {children}
        </Card>
    );
}

export default function Dashboard(props) {
    const { auth, pengumuman, renstraTerisi, ikuTerisi, renjaTerisi, rkaklTerisi, dipaTerisi, rencanaAksiTerisi } = props;
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />
            
            <Grid container spacing={3}>
                
                {/* 1. Pengumuman */}
                <Grid item xs={12}>
                    <AnimatedCard index={0}>
                        <CardHeader
                            title="Pengumuman"
                            titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                            sx={{ backgroundColor: 'primary.main', color: 'white' }}
                        />
                        <CardContent>
                            {pengumuman.length > 0 ? (
                                pengumuman.map((item, idx) => (
                                    <Paper key={idx} elevation={1} sx={{ p: 2, mb: 2 }}>
                                        <Typography variant="h6" sx={{ color: 'red', fontWeight: 'bold' }}>
                                            {item.judul}
                                        </Typography>
                                        <Typography variant="body1" sx={{ whiteSpace: 'pre-line' }}>
                                            {item.isi}
                                        </Typography>
                                    </Paper>
                                ))
                            ) : (
                                <Typography align="center" color="text.secondary">
                                    Tidak ada pengumuman.
                                </Typography>
                            )}
                        </CardContent>
                    </AnimatedCard>
                </Grid>

                {/* 2. Kepatuhan (Conditional) */}
                {levelSakip !== 0 && (
                    <Grid item xs={12}>
                        <AnimatedCard index={1}>
                            <CardHeader
                                title="Kepatuhan"
                                titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                                sx={{ backgroundColor: 'primary.main', color: 'white' }}
                            />
                            <CardContent>
                                <Grid container spacing={2}>
                                    <Grid item xs={12} sm={6} md={4}>
                                        <StatusCard 
                                            title="Pengisian Renstra" 
                                            isFilled={renstraTerisi}
                                            textFilled="Pengisian Renstra sudah dilakukan"
                                            textNotFilled="Pengisian Renstra belum dilakukan"
                                        />
                                    </Grid>
                                    <Grid item xs={12} sm={6} md={4}>
                                        <StatusCard 
                                            title="Pengisian IKU" 
                                            isFilled={ikuTerisi}
                                            textFilled="Pengisian IKU sudah dilakukan"
                                            textNotFilled="Pengisian IKU belum dilakukan"
                                        />
                                    </Grid>
                                    <Grid item xs={12} sm={6} md={4}>
                                        <StatusCard 
                                            title="Pengisian Renja" 
                                            isFilled={renjaTerisi}
                                            textFilled="Pengisian Renja sudah dilakukan"
                                            textNotFilled="Pengisian Renja belum dilakukan"
                                        />
                                    </Grid>
                                    <Grid item xs={12} sm={6} md={4}>
                                        <StatusCard 
                                            title="Pengisian LKJIP" 
                                            isFilled={rkaklTerisi}
                                            textFilled="Pengisian LKJIP sudah dilakukan"
                                            textNotFilled="Pengisian LKJIP belum dilakukan"
                                        />
                                    </Grid>
                                    <Grid item xs={12} sm={6} md={4}>
                                        <StatusCard 
                                            title="Pengisian DIPA" 
                                            isFilled={dipaTerisi}
                                            textFilled="Pengisian DIPA sudah dilakukan"
                                            textNotFilled="Pengisian DIPA belum dilakukan"
                                        />
                                    </Grid>
                                    <Grid item xs={12} sm={6} md={4}>
                                        <StatusCard 
                                            title="Pengisian Rencana Aksi" 
                                            isFilled={rencanaAksiTerisi}
                                            textFilled="Pengisian Rencana Aksi sudah dilakukan"
                                            textNotFilled="Pengisian Rencana Aksi belum dilakukan"
                                        />
                                    </Grid>
                                </Grid>
                            </CardContent>
                        </AnimatedCard>
                    </Grid>
                )}

                {/* 3. Action Cards (Aturan & FAQ) */}
                <Grid item xs={12} md={6}>
                    <AnimatedCard index={2}>
                        <CardContent>
                            <Typography variant="h5" component="h2" sx={{ fontWeight: 'bold' }}>
                                Sumber Aturan
                            </Typography>
                            <Typography color="text.secondary" sx={{ my: 1 }}>
                                Lihat sumber aturan dan referensi hukum yang relevan.
                            </Typography>
                            <Button 
                                component={Link}
                                href="/aturan" 
                                variant="contained" 
                                color="primary"
                            >
                                Lihat Sumber Aturan
                            </Button>
                        </CardContent>
                    </AnimatedCard>
                </Grid>
                
                <Grid item xs={12} md={6}>
                    <AnimatedCard index={3}>
                        <CardContent>
                            <Typography variant="h5" component="h2" sx={{ fontWeight: 'bold' }}>
                                FAQ
                            </Typography>
                            <Typography color="text.secondary" sx={{ my: 1 }}>
                                Lihat pertanyaan yang sering diajukan tentang sistem ini.
                            </Typography>
                            <Button 
                                component={Link} 
                                href="/faq" 
                                variant="contained" 
                                color="primary"
                            >
                                Lihat FAQ
                            </Button>
                        </CardContent>
                    </AnimatedCard>
                </Grid>

                {/* 4. Image & Text Cards (SAKIP & SMART) */}
                {/* ... (Konten SAKIP & SMART seperti di jawaban saya sebelumnya, menggunakan Grid, Card, CardHeader, Box, Typography) ... */}

            </Grid>
        </AuthenticatedLayout>
    );
}