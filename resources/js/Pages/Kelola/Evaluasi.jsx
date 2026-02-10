// resources/js/Pages/Kelola/Evaluasi.jsx
import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Box, Card, CardHeader, CardContent, Tabs, Tab, Typography, Alert } from '@mui/material';

// Impor komponen-komponen terpisah untuk setiap tab
import LkeTabContent from '@/Components/Evaluasi/LkeTabContent';
import FileUploadTab from '@/Components/Evaluasi/FileUploadTab';

// Helper untuk panel tab
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
            {value === index && (
                <Box sx={{ p: 3 }}>
                    {children}
                </Box>
            )}
        </div>
    );
}

export default function Evaluasi() {
    // Ambil props dari EvaluasiController
    const { 
        auth, 
        flash = {}, // <-- Tambahkan default {}
        tahun, 
        lheAkipFiles, 
        tlLheAkipFiles, 
        monevRenaksiFiles, 
        komponen, 
        idSatker, 
        buktiDukung,
        lkeGrouped
    } = usePage().props;

    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);
    
    // State untuk mengelola tab yang aktif
    const [activeTab, setActiveTab] = useState(flash.active_tab || 'lke'); // Default ke 'lke'

    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Evaluasi" />

            <Card elevation={3}>
                <CardHeader
                    title="Evaluasi"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    {/* Navigasi Tab */}
                    <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
                        <Tabs value={activeTab} onChange={handleTabChange} variant="scrollable" scrollButtons="auto">
                            <Tab label="Bukti Dukung LKE" value="lke" />
                            <Tab label="LHE AKIP" value="lhe-akip" />
                            <Tab label="TL LHE AKIP" value="tl-lhe-akip" />
                            <Tab label="Laporan Monev Renaksi" value="monev-renaksi" />
                            {levelSakip === 99 && (
                                <Tab label="Evaluasi Internal" value="evaluasi-internal" />
                            )}
                            {levelSakip === 99 && (
                                <Tab label="Evaluasi Rencana Aksi" value="evaluasi-rencana" />
                            )}
                        </Tabs>
                    </Box>

                    {/* Konten Panel Tab */}
                    <TabPanel value={activeTab} index="lke">
                        {/* 1. Tab LKE */}
                        <Typography paragraph>
                            Halaman ini digunakan untuk melihat dokumen/bukti dukung sebagaimana tercantum pada Lembar Kerja Evaluasi (LKE) AKIP Tahun 2025...
                            {/* ... (salin sisa teks paragraf Anda di sini) ... */}
                        </Typography>
                        <Typography color="error" paragraph>*upload dokumen maks. 4 MB</Typography>
                        <LkeTabContent lkeGrouped={lkeGrouped} />
                    </TabPanel>
                    
                    <TabPanel value={activeTab} index="lhe-akip">
                        {/* 2. Tab LHE AKIP */}
                        <FileUploadTab
                            title="Laporan Hasil Evaluasi AKIP"
                            uploadRoute="/upload/lhe-akip" // URL dari web.php
                            fileInputName="lhe_akip_file"
                            flashMessage={flash['success-lhe']}
                            files={lheAkipFiles}
                            tahun={tahun}
                            idSatker={idSatker}
                            fileNamePrefix="LHE AKIP"
                        />
                    </TabPanel>

                    <TabPanel value={activeTab} index="tl-lhe-akip">
                        {/* 3. Tab TL LHE AKIP */}
                         <FileUploadTab
                            title="Tindak Lanjut Laporan Hasil Evaluasi AKIP"
                            uploadRoute="/upload/tl-lhe-akip"
                            fileInputName="tllhe_file" // Sesuaikan dengan controller
                            flashMessage={flash['success-tllhe']}
                            files={tlLheAkipFiles}
                            tahun={tahun}
                            idSatker={idSatker}
                            fileNamePrefix="TL LHE AKIP"
                        />
                    </TabPanel>

                    <TabPanel value={activeTab} index="monev-renaksi">
                        {/* 4. Tab Monev Renaksi */}
                         <FileUploadTab
                            title="Laporan Monev Renaksi"
                            uploadRoute="/upload/monev-renaksi"
                            fileInputName="monev_file"
                            flashMessage={flash['success-monev']}
                            files={monevRenaksiFiles}
                            tahun={tahun}
                            idSatker={idSatker}
                            fileNamePrefix="Monev Renaksi"
                            showTriwulanSelect={true} // Tampilkan dropdown triwulan
                            triwulanInputName="id_triwulan" // Sesuaikan dengan controller
                        />
                    </TabPanel>

                    {levelSakip === 99 && (
                        <TabPanel value={activeTab} index="evaluasi-internal">
                            <Typography variant="h5">Evaluasi Internal</Typography>
                            <Typography>Content for Evaluasi Internal goes here.</Typography>
                        </TabPanel>
                    )}
                    {levelSakip === 99 && (
                        <TabPanel value={activeTab} index="evaluasi-rencana">
                            <Typography variant="h5">Evaluasi Rencana Aksi</Typography>
                            <Typography>Content for Evaluasi Rencana Aksi goes here.</Typography>
                        </TabPanel>
                    )}

                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}