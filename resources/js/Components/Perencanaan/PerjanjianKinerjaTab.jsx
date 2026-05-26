// resources/js/Components/Perencanaan/PerjanjianKinerjaTab.jsx
import React, {useState} from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { 
    Box, Typography, Paper, Accordion, AccordionSummary, AccordionDetails, 
    Grid, Card, CardContent, TextField, Button, Alert, Collapse 
} from '@mui/material';
import DescriptionIcon from '@mui/icons-material/Description';
import DownloadIcon from '@mui/icons-material/Download';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import UploadFileIcon from '@mui/icons-material/UploadFile';
import FileUploadSection from './FileUploadSection'; 

// Komponen Form Target Per Indikator
function IndikatorTargetForm({ indikator, targetData }) {
    const { data, setData, post, processing, errors } = useForm({
        indikator_id: indikator.id,
        target_tahun: targetData?.target_tahun || '',
        target_triwulan_1: targetData?.target_triwulan_1 || '',
        target_triwulan_2: targetData?.target_triwulan_2 || '',
        target_triwulan_3: targetData?.target_triwulan_3 || '',
        target_triwulan_4: targetData?.target_triwulan_4 || '',
    });

    const { tahun } = usePage().props;

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/target/store', { 
            preserveScroll: true,
        });
    };

    return (
        <Card sx={{ mb: 2 }} elevation={2}>
            <CardContent>
                <Typography sx={{ fontWeight: 'bold', color: 'black', textAlign: 'center' }}>
                    {indikator.indikator_nama}
                </Typography>
                {tahun != 2024 && (
                    <Box component="form" onSubmit={handleSubmit} sx={{ mt: 2 }}>
                        <TextField
                            label="Target Pertahun (%)"
                            type="number"
                            name="target_tahun"
                            value={data.target_tahun}
                            onChange={(e) => setData('target_tahun', e.target.value)}
                            error={!!errors.target_tahun}
                            helperText={errors.target_tahun}
                            fullWidth
                            margin="normal"
                        />
                        <Button type="submit" variant="contained" color="success" fullWidth disabled={processing} sx={{ mt: 2 }}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </Box>
                )}
            </CardContent>
        </Card>
    );
}

function PkDataActions() {
    const { data, setData, post, processing, errors, reset } = useForm({
        pk_import_file: null,
    });
    const [inputKey, setInputKey] = useState(Date.now());

    const handleSubmit = (e) => {
        e.preventDefault();

        post('/perencanaan/pk/import-csv', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('pk_import_file');
                setInputKey(Date.now());
            },
        });
    };

    return (
        <Paper sx={{ p: 2, mb: 3 }} elevation={2}>
            <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap', alignItems: 'center' }}>
                <Button
                    variant="contained"
                    color="success"
                    component="a"
                    href="/perencanaan/pk/export-csv"
                    startIcon={<DownloadIcon />}
                >
                    Export CSV
                </Button>
                <Button
                    variant="contained"
                    color="primary"
                    component="a"
                    href="/perencanaan/pk/export-word"
                    startIcon={<DescriptionIcon />}
                >
                    Export Word
                </Button>
                <Box component="form" onSubmit={handleSubmit} sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap', alignItems: 'center' }}>
                    <Button variant="outlined" component="label" startIcon={<UploadFileIcon />}>
                        Pilih CSV
                        <input
                            key={inputKey}
                            type="file"
                            hidden
                            name="pk_import_file"
                            accept=".csv,text/csv"
                            onChange={(e) => setData('pk_import_file', e.target.files[0])}
                        />
                    </Button>
                    <Button type="submit" variant="contained" disabled={!data.pk_import_file || processing}>
                        {processing ? 'Import...' : 'Import CSV'}
                    </Button>
                </Box>
            </Box>
            {data.pk_import_file && (
                <Typography sx={{ mt: 1, fontStyle: 'italic' }} variant="body2">
                    File: {data.pk_import_file.name}
                </Typography>
            )}
            {errors.pk_import_file && (
                <Typography color="error" variant="caption">
                    {errors.pk_import_file}
                </Typography>
            )}
        </Paper>
    );
}

export default function PerjanjianKinerjaTab({ 
    pkFiles, flashMessage, flashMessageTarget, tahun, idSatker, onEditClick, 
    deleteRoutePrefix, bidangs, indikators, targets 
}) {
    
    const { auth } = usePage().props;
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);
    const [showFlash, setShowFlash] = useState(true);

    // 🔥 VARIABEL PENGAMAN: Jika undefined dari controller, otomatis diubah menjadi Array/Object kosong
    const safeBidangs = bidangs || [];
    const safeIndikators = indikators || [];
    const safeTargets = targets || {};

    return (
        <Box>
            <Collapse in={showFlash && !!flashMessageTarget}>
                <Alert severity="success" onClose={() => setShowFlash(false)} sx={{ mb: 2 }}>
                    {flashMessageTarget}
                </Alert>
            </Collapse>

            <Typography variant="h5" sx={{ mb: 1, fontWeight: 'bold' }}>Perjanjian Kinerja</Typography>
            <Paper sx={{ p: 2, backgroundColor: '#f1e022', color: 'black', mb: 2 }}>
                <Typography>Pengisian Target Perjanjian Kinerja</Typography>
            </Paper>

            <FileUploadSection
                title="UPLOAD File Perjanjian Kinerja"
                description={tahun != 2024 ? "Cukup 1 File saja yang memuat PK seluruh pejabat" : ""}
                uploadRoute="/perencanaan/upload/pk"
                fileInputName="pk_file"
                files={pkFiles}
                flashMessage={flashMessage}
                tahun={tahun}
                idSatker={idSatker}
                fileNamePrefix="PK"
                deleteRoutePrefix={deleteRoutePrefix}
                onEditClick={onEditClick}
                hideForm={tahun == 2024} 
            />

            {tahun != 2024 && <PkDataActions />}

            {/* Bagian Accordion Form Target menggunakan Array Pengaman */}
            {tahun != 2024 && (
                <Box sx={{ mt: 4 }}>
                    {safeBidangs.map((bidang, index) => {
                        
                        // Gunakan safeIndikators agar tidak error saat .filter
                        const relevantIndikators = safeIndikators.filter(ind => {
                            if (ind.link !== bidang.rumpun) return false;
                            
                            // Amankan pencarian string tahun
                            const tahunString = String(tahun);
                            if (ind.tahun && typeof ind.tahun === 'string' && !ind.tahun.includes(tahunString)) return false;
                            
                            switch (levelSakip) {
                                case 0:
                                case 99: return true;
                                case 1: return [0, 1].includes(ind.lingkup);
                                case 2: return [0, 2, 5, 7].includes(ind.lingkup);
                                case 3: return [0, 3, 5, 6, 7].includes(ind.lingkup);
                                case 4: return [0, 4, 6, 7].includes(ind.lingkup);
                                default: return false;
                            }
                        });

                        return (
                            <Accordion key={bidang.id} defaultExpanded={index === 0}>
                                <AccordionSummary
                                    expandIcon={<ExpandMoreIcon />}
                                    sx={{ backgroundColor: '#e6bf3e', color: 'white' }}
                                >
                                    <Typography sx={{ fontWeight: 'bold' }}>{bidang.bidang_nama}</Typography>
                                </AccordionSummary>
                                <AccordionDetails>
                                    {relevantIndikators.length > 0 ? (
                                        <Grid container spacing={2}>
                                            {relevantIndikators.map((indikator) => (
                                                <Grid item xs={12} md={6} key={indikator.id}>
                                                    <IndikatorTargetForm
                                                        indikator={indikator}
                                                        targetData={safeTargets[indikator.id]} // Gunakan safeTargets
                                                    />
                                                </Grid>
                                            ))}
                                        </Grid>
                                    ) : (
                                        <Typography><i>Tidak ada indikator terkait</i></Typography>
                                    )}
                                </AccordionDetails>
                            </Accordion>
                        );
                    })}
                </Box>
            )}
        </Box>
    );
}
