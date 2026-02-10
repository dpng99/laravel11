import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Container, Card, CardHeader, CardContent, Tabs, Tab, Box, Alert, Paper } from '@mui/material';

// Import Komponen Tab Fitur
import BidangTab from '@/Components/KelolaData/BidangTab';
import SasproTab from '@/Components/KelolaData/SasproTab';
import IndikatorTab from '@/Components/KelolaData/IndikatorTab';

function TabPanel(props) {
    const { children, value, index, ...other } = props;
    return (
        <div role="tabpanel" hidden={value !== index} {...other}>
            {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
        </div>
    );
}

export default function KelolaData({ bidangs, saspros, indikators, bidangall, sasproAll }) {
    // Gunakan default object {} agar tidak error jika flash undefined
    const { flash = {} } = usePage().props;
    
    // State Tab Aktif
    const [activeTab, setActiveTab] = useState(0);

    // Auto-hide Flash Message
    const [showFlash, setShowFlash] = useState(true);
    useEffect(() => {
        if (flash.success || flash.error) {
            setShowFlash(true);
            const timer = setTimeout(() => setShowFlash(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Kelola Data" />

            <Container maxWidth="xl" sx={{ mt: 4, mb: 4 }}>
                <Card elevation={3}>
                    <CardHeader
                        title="Kelola Data"
                        titleTypographyProps={{ variant: 'h4', align: 'center', fontWeight: 'bold' }}
                        sx={{ backgroundColor: '#e6bf3e', color: 'white' }}
                    />
                    <CardContent>
                        {/* Notifikasi */}
                        {showFlash && flash.success && <Alert severity="success" sx={{ mb: 2 }}>{flash.success}</Alert>}
                        {showFlash && flash.error && <Alert severity="error" sx={{ mb: 2 }}>{flash.error}</Alert>}

                        {/* Navigasi Tab */}
                        <Paper variant="outlined" sx={{ mb: 2 }}>
                            <Tabs 
                                value={activeTab} 
                                onChange={handleTabChange} 
                                variant="scrollable"
                                scrollButtons="auto"
                                indicatorColor="primary"
                                textColor="inherit"
                                sx={{ '& .Mui-selected': { color: '#d4af37 !important', fontWeight: 'bold' } }}
                            >
                                <Tab label="Data Bidang" />
                                <Tab label="Data Saspro" />
                                <Tab label="Data Indikator" />
                            </Tabs>
                        </Paper>

                        {/* --- KONTEN FITUR --- */}
                        
                        {/* 1. Fitur Data Bidang */}
                        <TabPanel value={activeTab} index={0}>
                            <BidangTab bidangs={bidangs} />
                        </TabPanel>

                        {/* 2. Fitur Data Saspro */}
                        <TabPanel value={activeTab} index={1}>
                            <SasproTab 
                                saspros={saspros} 
                                bidangAll={bidangall || []} 
                            />
                        </TabPanel>

                        {/* 3. Fitur Data Indikator */}
                        <TabPanel value={activeTab} index={2}>
                            <IndikatorTab 
                                indikators={indikators}
                                bidangAll={bidangall || []}
                                sasproAll={sasproAll || []} 
                            />
                        </TabPanel>

                    </CardContent>
                </Card>
            </Container>
        </AuthenticatedLayout>
    );
}