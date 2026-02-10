
// resources/js/Components/Perencanaan/PerjanjianKinerjaTab.jsx
import React, {useState} from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { 
    Box, Typography, Paper, Accordion, AccordionSummary, AccordionDetails, 
    Grid, Card, CardContent, TextField, Button, Alert, Collapse 
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import FileUploadSection from './FileUploadSection'; // Impor ulang komponen file upload

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
        post('/target/store', { // URL dari Blade
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
                        {/* Anda bisa tambahkan input Triwulan di sini
                            jika logikanya diaktifkan kembali
                        */}
                        <Button type="submit" variant="contained" color="success" fullWidth disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </Box>
                )}
            </CardContent>
        </Card>
    );
}


export default function PerjanjianKinerjaTab({ 
    pkFiles, flashMessage, flashMessageTarget, tahun, idSatker, onEditClick, 
    deleteRoutePrefix, bidangs, indikators, targets 
}) {
    
    const { auth } = usePage().props;
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);
    const [showFlash, setShowFlash] = useState(true);

    return (
        <Box>
            {/* 1. Flash Message untuk Target */}
            <Collapse in={showFlash && !!flashMessageTarget}>
                <Alert severity="success" onClose={() => setShowFlash(false)} sx={{ mb: 2 }}>
                    {flashMessageTarget}
                </Alert>
            </Collapse>

            <Typography variant="h5" sx={{ mb: 1, fontWeight: 'bold' }}>Perjanjian Kinerja</Typography>
            <Paper sx={{ p: 2, backgroundColor: '#f1e022', color: 'black', mb: 2 }}>
                <Typography>Pengisian Target Perjanjian Kinerja</Typography>
            </Paper>

            {/* 2. Bagian Upload File PK */}
            <FileUploadSection
                title="UPLOAD File Perjanjian Kinerja"
                description={tahun != 2024 ? "Cukup 1 File saja yang memuat PK seluruh pejabat" : ""}
                uploadRoute="/upload-pk"
                fileInputName="pk_file"
                files={pkFiles}
                flashMessage={flashMessage}
                tahun={tahun}
                idSatker={idSatker}
                fileNamePrefix="PK"
                deleteRoutePrefix={deleteRoutePrefix}
                onEditClick={onEditClick}
                // Sembunyikan form jika tahun 2024
                hideForm={tahun == 2024} 
            />

            {/* 3. Bagian Accordion Form Target (hanya jika tahun != 2024) */}
            {tahun != 2024 && (
                <Box sx={{ mt: 4 }}>
                    {bidangs.map((bidang, index) => {
                        // Filter indikator yang sesuai untuk bidang ini
                        const relevantIndikators = indikators.filter(ind => {
                            if (ind.link !== bidang.rumpun) return false;
                            if (!ind.tahun.includes(String(tahun))) return false;
                            
                            // Logika lingkup dari Blade
                            switch (levelSakip) {
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
                                                        targetData={targets[indikator.id]} // Kirim data target
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