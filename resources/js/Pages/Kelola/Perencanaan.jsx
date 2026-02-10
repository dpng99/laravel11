// resources/js/Pages/Kelola/Perencanaan.jsx
import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Box, Card, CardHeader, CardContent, Tabs, Tab, Typography, Alert, Collapse } from '@mui/material';

// Impor komponen tab kustom
import FileUploadSection from '@/Components/Perencanaan/FileUploadSection';
import DipaTab from '@/Components/Perencanaan/DipaTab';
import PerjanjianKinerjaTab from '@/Components/Perencanaan/PerjanjianKinerjaTab';
import EditFileModal from '@/Components/Perencanaan/EditFileModal';

// Helper untuk Panel Tab
function TabPanel(props) {
    const { children, value, index, ...other } = props;
    return (
        <div role="tabpanel" hidden={value !== index} id={`tabpanel-${index}`} {...other}>
            {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
        </div>
    );
}

export default function Perencanaan() {
    const { auth, flash = {}, tahun, renstra, iku, renja, rkakl, dipa, renaksi, indikator, target, bidang, pk } = usePage().props;
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);
    
    // State untuk tab aktif (default ke 'renstra' seperti di Blade)
    const [activeTab, setActiveTab] = useState(flash.active_tab || 'renstra');

    // State untuk Modal Edit
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedFile, setSelectedFile] = useState(null);
    const [selectedType, setSelectedType] = useState('');
    const [actionUrl, setActionUrl] = useState('');

    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
    };

    // Fungsi untuk membuka modal (dipicu dari komponen anak)
    const handleEditClick = (file, type) => {
        setSelectedFile(file);
        setSelectedType(type);
        setActionUrl(`/perencanaan/update/${type}/${file.id}`); // URL tanpa Ziggy
        setModalOpen(true);
    };

    const handleCloseModal = () => {
        setModalOpen(false);
        setSelectedFile(null);
        setSelectedType('');
        setActionUrl('');
    };

    // Ambil pesan flash
    const [flashMessages, setFlashMessages] = useState({
        update: flash['success-update'],
        delete: flash['success-delete'],
        error: flash.error,
    });

    // Fungsi untuk menutup alert
    const closeAlert = (key) => setFlashMessages(prev => ({ ...prev, [key]: null }));

    return (
        <AuthenticatedLayout>
            <Head title="Perencanaan" />

            <Card elevation={3}>
                <CardHeader
                    title="Perencanaan"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    {/* Tampilkan Flash Messages */}
                    <Collapse in={!!flashMessages.update}><Alert severity="success" onClose={() => closeAlert('update')} sx={{ mb: 2 }}>{flashMessages.update}</Alert></Collapse>
                    <Collapse in={!!flashMessages.delete}><Alert severity="success" onClose={() => closeAlert('delete')} sx={{ mb: 2 }}>{flashMessages.delete}</Alert></Collapse>
                    <Collapse in={!!flashMessages.error}><Alert severity="error" onClose={() => closeAlert('error')} sx={{ mb: 2 }}>{flashMessages.error}</Alert></Collapse>

                    {/* Navigasi Tab */}
                    <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
                        <Tabs value={activeTab} onChange={handleTabChange} variant="scrollable" scrollButtons="auto">
                            <Tab label="Renstra" value="renstra" />
                            <Tab label="IKU (Penetapan Target Kinerja)" value="iku" />
                            <Tab label="Renja" value="renja" />
                            <Tab label="RKAKL" value="rkakl" />
                            <Tab label="DIPA" value="dipa" />
                            <Tab label="Rencana Aksi" value="renaksi" />
                            <Tab label="Perjanjian Kinerja" value="perjanjian-kinerja" />
                            {tahun != 2024 && levelSakip === 99 && <Tab label="Cetak PK" value="cetak-pk" />}
                        </Tabs>
                    </Box>

                    {/* === Konten Panel Tab === */}
                    <TabPanel value={activeTab} index="renstra">
                        <FileUploadSection
                            title={`Rencana Strategis (Renstra) Tahun ${tahun == '2024' ? '2019 - 2024' : '2025 - 2029'}`}
                            description="Rencana Strategis (Renstra) merupakan dokumen perencanaan..."
                            uploadRoute="/perencanaan/upload-renstra"
                            fileInputName="renstra_file"
                            files={renstra}
                            flashMessage={flash['success-renstra']}
                            tahun={tahun}
                            idSatker={auth.user.id_satker}
                            fileNamePrefix="Renstra"
                            deleteRoutePrefix="/perencanaan/delete/renstra"
                            onEditClick={(file) => handleEditClick(file, 'renstra')}
                        />
                    </TabPanel>

                    <TabPanel value={activeTab} index="iku">
                        <FileUploadSection
                            title="Indikator Kinerja Utama (IKU)"
                            description="Indikator Kinerja Utama (IKU) Kejaksaan adalah ukuran keberhasilan..."
                            uploadRoute="/perencanaan/upload-iku"
                            fileInputName="iku_file"
                            files={iku}
                            flashMessage={flash['success-iku']}
                            tahun={tahun}
                            idSatker={auth.user.id_satker}
                            fileNamePrefix="IKU"
                            deleteRoutePrefix="/perencanaan/delete/iku"
                            onEditClick={(file) => handleEditClick(file, 'iku')}
                        />
                    </TabPanel>

                     <TabPanel value={activeTab} index="renja">
                        <FileUploadSection
                            title="Rencana Kerja Tahunan"
                            description="Rencana Kinerja Tahunan (RKT) merupakan penjabaran dari sasaran..."
                            uploadRoute="/perencanaan/upload-renja"
                            fileInputName="renja_file"
                            files={renja}
                            flashMessage={flash['success-renja']}
                            tahun={tahun}
                            idSatker={auth.user.id_satker}
                            fileNamePrefix="Renja"
                            deleteRoutePrefix="/perencanaan/delete/renja"
                            onEditClick={(file) => handleEditClick(file, 'renja')}
                        />
                    </TabPanel>
                    
                     <TabPanel value={activeTab} index="rkakl">
                        <FileUploadSection
                            title="Rencana Kerja Anggaran Kementerian atau Lembaga"
                            description="Data Kebutuhan Riil..."
                            uploadRoute="/perencanaan/upload-rkakl"
                            fileInputName="rkakl_file"
                            files={rkakl}
                            flashMessage={flash['success-rkakl']}
                            tahun={tahun}
                            idSatker={auth.user.id_satker}
                            fileNamePrefix="RKAKL"
                            deleteRoutePrefix="/perencanaan/delete/rkakl"
                            onEditClick={(file) => handleEditClick(file, 'rkakl')}
                        />
                    </TabPanel>

                    <TabPanel value={activeTab} index="dipa">
                        <DipaTab
                            dipaFiles={dipa}
                            flashMessage={flash['success-dipa']}
                            tahun={tahun}
                            idSatker={auth.user.id_satker}
                            onEditClick={(file) => handleEditClick(file, 'dipa')}
                            deleteRoutePrefix="/perencanaan/delete/dipa"
                        />
                    </TabPanel>

                    <TabPanel value={activeTab} index="renaksi">
                         <FileUploadSection
                            title="Rencana Aksi"
                            description="Data Kebutuhan Riil..."
                            uploadRoute="/perencanaan/upload-renaksi"
                            fileInputName="renaksi_file"
                            files={renaksi}
                            flashMessage={flash['success-renaksi']}
                            tahun={tahun}
                            idSatker={auth.user.id_satker}
                            fileNamePrefix="Renaksi"
                            deleteRoutePrefix="/perencanaan/delete/renaksi"
                            onEditClick={(file) => handleEditClick(file, 'renaksi')}
                        />
                    </TabPanel>

                    <TabPanel value={activeTab} index="perjanjian-kinerja">
                        <PerjanjianKinerjaTab
                            pkFiles={pk}
                            flashMessage={flash['success-pk-file']}
                            flashMessageTarget={flash['success-pk']}
                            tahun={tahun}
                            idSatker={auth.user.id_satker}
                            onEditClick={(file) => handleEditClick(file, 'pk')}
                            deleteRoutePrefix="/perencanaan/delete/pk"
                            bidangs={bidang}
                            indikators={indikator} // Kirim semua indikator
                            targets={target} // Kirim semua target
                        />
                    </TabPanel>

                    {tahun != 2024 && levelSakip === 99 && (
                        <TabPanel value={activeTab} index="cetak-pk">
                            <Typography variant="h5">Cetak PK</Typography>
                            {/* ... Konten Cetak PK ... */}
                        </TabPanel>
                    )}

                </CardContent>
            </Card>

            {/* Modal Edit (terpisah) */}
            <EditFileModal
                open={modalOpen}
                onClose={handleCloseModal}
                file={selectedFile}
                type={selectedType}
                actionUrl={actionUrl}
            />
        </AuthenticatedLayout>
    );
}