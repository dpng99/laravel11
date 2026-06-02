// resources/js/Pages/Dashboard.jsx
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'; // Layout MUI Anda

// Import Komponen Material-UI
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Chip,
    Divider,
    FormControl,
    Grid,
    InputLabel,
    MenuItem,
    Pagination,
    Paper,
    Select,
    Stack,
    Typography,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';

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

function IndikatorList({ title, rows }) {
    if (!rows || rows.length === 0) {
        return null;
    }

    return (
        <Box sx={{ mt: 1.5 }}>
            <Typography variant="subtitle2" fontWeight="bold" color="text.secondary" gutterBottom>
                {title}
            </Typography>
            <Stack spacing={1}>
                {rows.map((item) => (
                    <Box key={item.id} sx={{ display: 'flex', gap: 1, alignItems: 'flex-start' }}>
                        <Chip label={item.id} size="small" variant="outlined" color="primary" />
                        <Typography variant="body2">{item.nama}</Typography>
                    </Box>
                ))}
            </Stack>
        </Box>
    );
}

function PohonKinerjaSection({ pohonKinerja, perPageOptions }) {
    const rows = pohonKinerja?.data || [];

    const changePage = (page) => {
        router.get(route('dashboard'), {
            pohon_page: page,
            per_page: pohonKinerja?.per_page || 10,
        }, { preserveScroll: true, preserveState: true, replace: true });
    };

    const changePerPage = (event) => {
        router.get(route('dashboard'), {
            pohon_page: 1,
            per_page: event.target.value,
        }, { preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <AnimatedCard index={1}>
            <CardHeader
                title="Pohon Kinerja"
                titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                sx={{ backgroundColor: 'primary.main', color: 'white' }}
            />
            <CardContent>
                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} justifyContent="space-between" alignItems={{ xs: 'stretch', md: 'center' }} sx={{ mb: 2 }}>
                    <Typography variant="body2" color="text.secondary">
                        Tersusun otomatis dari sasaran strategis, indikator sasaran strategis, sasaran program, dan indikator sasaran program.
                    </Typography>
                    <FormControl size="small" sx={{ width: { xs: '100%', md: 150 } }}>
                        <InputLabel>Per Halaman</InputLabel>
                        <Select value={pohonKinerja?.per_page || 10} label="Per Halaman" onChange={changePerPage}>
                            {perPageOptions.map((value) => (
                                <MenuItem key={value} value={value}>{value}</MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                </Stack>

                {rows.length > 0 ? (
                    <Stack spacing={1.5}>
                        {rows.map((sastra) => (
                            <Accordion key={sastra.id} variant="outlined" disableGutters>
                                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.75 }}>
                                        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center', flexWrap: 'wrap' }}>
                                            <Chip label={`Sastra ${sastra.id}`} size="small" color="primary" />
                                            {sastra.target && <Chip label={`Target Institusi: ${sastra.target}`} size="small" variant="outlined" />}
                                        </Box>
                                        <Typography fontWeight="bold">{sastra.nama}</Typography>
                                    </Box>
                                </AccordionSummary>
                                <AccordionDetails>
                                    <IndikatorList title="Indikator Sasaran Strategis" rows={sastra.indikator} />

                                    {sastra.saspro?.length > 0 && (
                                        <Box sx={{ mt: 2 }}>
                                            <Typography variant="subtitle2" fontWeight="bold" color="text.secondary" gutterBottom>
                                                Sasaran Program
                                            </Typography>
                                            <Stack spacing={1.25}>
                                                {sastra.saspro.map((saspro) => (
                                                    <Paper key={saspro.id} variant="outlined" sx={{ p: 1.5 }}>
                                                        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center', flexWrap: 'wrap', mb: 0.75 }}>
                                                            <Chip label={`Saspro ${saspro.id}`} size="small" color="secondary" />
                                                            {saspro.tahun && <Chip label={saspro.tahun} size="small" variant="outlined" />}
                                                        </Box>
                                                        <Typography variant="body2" fontWeight="bold">{saspro.nama}</Typography>
                                                        <IndikatorList title="Indikator Sasaran Program" rows={saspro.indikator} />
                                                    </Paper>
                                                ))}
                                            </Stack>
                                        </Box>
                                    )}
                                </AccordionDetails>
                            </Accordion>
                        ))}
                    </Stack>
                ) : (
                    <Typography align="center" color="text.secondary">
                        Data pohon kinerja belum tersedia.
                    </Typography>
                )}

                {pohonKinerja?.last_page > 1 && (
                    <>
                        <Divider sx={{ my: 2 }} />
                        <Stack alignItems="center">
                            <Pagination
                                count={pohonKinerja.last_page}
                                page={pohonKinerja.current_page}
                                onChange={(event, page) => changePage(page)}
                                color="primary"
                            />
                        </Stack>
                    </>
                )}
            </CardContent>
        </AnimatedCard>
    );
}

export default function Dashboard(props) {
    const { auth, pengumuman, pohonKinerja, pohonPerPageOptions = [10, 25, 50], renstraTerisi, ikuTerisi, renjaTerisi, rkaklTerisi, dipaTerisi, rencanaAksiTerisi } = props;
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

                <Grid item xs={12}>
                    <PohonKinerjaSection pohonKinerja={pohonKinerja} perPageOptions={pohonPerPageOptions} />
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
