import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Box, Card, CardHeader, CardContent, Tabs, Tab, Typography, Alert, Paper } from '@mui/material';

// Import komponen tab
import CapaianKinerjaTab from '@/Components/Pelaporan/CapaianKinerjaTab';
import FileUploadTab from '@/Components/Pelaporan/FileUploadTab';

// Helper Panel Tab
function TabPanel(props) {
    const { children, value, index, ...other } = props;
    return (
        <div
            role="tabpanel"
            hidden={value !== index}
            id={`tabpanel-${index}`}
            aria-labelledby={`tab-${index}`}
            {...other}
        >
            {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
        </div>
    );
}

export default function Pelaporan() {
    // 1. Ambil data dari Props Inertia (dikirim oleh PelaporanController)
    const { 
        auth, 
        flash = {}, 
        tahun, 
        bidangs, 
        lkjipFiles, 
        rapatStaffEkaFiles 
    } = usePage().props;

    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);
    const idSatker = auth.user?.id_satker;

    // 2. State untuk Tab Aktif
    // Cek apakah ada flash message 'active_tab' dari controller (setelah upload)
    const [activeTab, setActiveTab] = useState('lkjip');

    useEffect(() => {
        if (flash.active_tab) {
            setActiveTab(flash.active_tab);
        }
    }, [flash]);

    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Pelaporan" />

            <Box sx={{ p: 3 }}>
                <Card elevation={3}>
                    {/* Header Kuning seperti Blade */}
                    <CardHeader
                        title="Pelaporan"
                        style={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                        sx={{ backgroundColor: '#e6bf3e', color: 'white' }}
                    />
                    <CardContent>
                        {/* 3. Flash Messages */}
                        {flash['success-lkjip'] && <Alert severity="success" sx={{ mb: 2 }}>{flash['success-lkjip']}</Alert>}
                        {flash['success-rastaff'] && <Alert severity="success" sx={{ mb: 2 }}>{flash['success-rastaff']}</Alert>}
                        {flash['success-update'] && <Alert severity="success" sx={{ mb: 2 }}>{flash['success-update']}</Alert>}
                        {flash['success-delete'] && <Alert severity="success" sx={{ mb: 2 }}>{flash['success-delete']}</Alert>}
                        {flash.error && <Alert severity="error" sx={{ mb: 2 }}>{flash.error}</Alert>}
                        {Object.keys(flash).length === 0 && (
                            // Fallback jika kunci flash message berbeda
                            flash.message && <Alert severity="info" sx={{ mb: 2 }}>{flash.message}</Alert>
                        )}

                        {/* 4. Navigasi Tabs */}
                        <Paper variant="outlined" sx={{ mb: 2 }}>
                            <Tabs 
                                value={activeTab} 
                                onChange={handleTabChange} 
                                variant="scrollable" 
                                scrollButtons="auto"
                                indicatorColor="primary"
                                textColor="primary"
                            >
                                {/* Logic Tab sesuai Blade */}
                                {levelSakip === 99 && <Tab label="Capaian Triwulan II" value="triwulan2" />}
                                {levelSakip === 99 && <Tab label="Capaian Triwulan III" value="triwulan3" />}
                                {levelSakip === 99 && <Tab label="Capaian Triwulan IV" value="triwulan4" />}
                                
                                <Tab label="Laporan Kinerja (LKJiP)" value="lkjip" />
                                
                                {/* Tab Capaian Kinerja (Muncul jika tahun != 2024 atau logic lain) */}
                                <Tab label="Capaian Kinerja" value="capaian" /> 

                                <Tab label="Rapat Staff EKA" value="rapat-staff-eka" />
                                
                                {levelSakip === 99 && <Tab label="Validasi APIP" value="validasi-apip" />}
                            </Tabs>
                        </Paper>

                        {/* --- KONTEN TAB --- */}

                        {/* TAB 1: LKJiP */}
                        <TabPanel value={activeTab} index="lkjip">
                            <FileUploadTab
                                title="Laporan Kinerja (LKJiP)"
                                uploadRoute="/upload/lkjip"            // Sesuai route('upload.lkjip')
                                deleteRoutePrefix="/delete/lkjip"      // Sesuai route('delete.lkjip')
                                fileInputName="lkjip_file"
                                triwulanInputName="id_triwulan"        // Controller baca 'id_triwulan'
                                files={lkjipFiles}
                                tahun={tahun}
                                idSatker={idSatker}
                                fileNamePrefix="LKJiP"
                                showTriwulanSelect={true}
                            />
                        </TabPanel>

                        {/* TAB 2: Capaian Kinerja */}
                        <TabPanel value={activeTab} index="capaian">
                            <CapaianKinerjaTab bidangs={bidangs} />
                        </TabPanel>

                        {/* TAB 3: Rapat Staff EKA */}
                        <TabPanel value={activeTab} index="rapat-staff-eka">
                            <FileUploadTab
                                title="Rapat Staff EKA"
                                uploadRoute="/upload/rapat-staff-eka"  // Sesuai route('upload.rapat_staff_eka')
                                // Perhatikan: Route delete untuk Rapat Staff EKA belum ada di web.php Anda
                                // Tambahkan di web.php: Route::delete('/delete/rapat/{id}', ...)
                                deleteRoutePrefix="/delete/rapat-staff-eka" 
                                fileInputName="rapat_file"
                                triwulanInputName="id_triwulan"
                                files={rapatStaffEkaFiles}
                                tahun={tahun}
                                idSatker={idSatker}
                                fileNamePrefix="Rapat Staff EKA"
                                showTriwulanSelect={true}
                            />
                        </TabPanel>
                        {/* TAB ADMIN (Level 99) */}
                        {levelSakip === 99 && (
                            <>
                                <TabPanel value={activeTab} index="triwulan2">
                                    <Typography>Konten Capaian Triwulan II...</Typography>
                                </TabPanel>
                                <TabPanel value={activeTab} index="validasi-apip">
                                    <Typography>Konten Validasi APIP...</Typography>
                                </TabPanel>
                            </>
                        )}

                    </CardContent>
                </Card>
            </Box>
        </AuthenticatedLayout>
    );
}