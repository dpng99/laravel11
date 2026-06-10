// resources/js/Pages/Dashboard.jsx
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'; // Layout MUI Anda
import FileLinkButton from '@/Components/FileLinkButton';

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
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    Grid,
    InputLabel,
    MenuItem,
    Pagination,
    Paper,
    Select,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import DescriptionIcon from '@mui/icons-material/Description';
import FolderOpenIcon from '@mui/icons-material/FolderOpen';

// Helper komponen untuk Kartu Status Kepatuhan
function StatusCard({ title, isFilled, textFilled, textNotFilled, uploadData, onOpen }) {
    const uploadedSatkers = uploadData?.uploaded_satkers ?? 0;
    const totalSatkers = uploadData?.total_satkers ?? 0;

    return (
        <Paper 
            component="button"
            type="button"
            onClick={onOpen}
            elevation={3} 
            sx={{ 
                width: '100%',
                minHeight: 132,
                p: 2,
                border: 0,
                textAlign: 'left',
                cursor: 'pointer',
                color: 'white', 
                backgroundColor: isFilled || uploadedSatkers > 0 ? 'success.main' : 'error.main',
                transition: 'transform 0.15s ease, box-shadow 0.15s ease',
                '&:hover': {
                    transform: 'translateY(-2px)',
                    boxShadow: 6,
                },
                '&:focus-visible': {
                    outline: '3px solid rgba(25, 118, 210, 0.45)',
                    outlineOffset: 2,
                },
            }}
        >
            <Stack spacing={1}>
                <Typography variant="h6" component="h3" sx={{ fontWeight: 'bold' }}>
                    {title}
                </Typography>
                <Typography variant="body2">
                    {isFilled ? textFilled : textNotFilled}
                </Typography>
                {uploadData && (
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 1, pt: 0.5 }}>
                        <Typography variant="caption" sx={{ fontWeight: 'bold' }}>
                            {uploadedSatkers} dari {totalSatkers} satker sudah upload
                        </Typography>
                        <Typography variant="caption" sx={{ textDecoration: 'underline', whiteSpace: 'nowrap' }}>
                            Lihat data
                        </Typography>
                    </Box>
                )}
            </Stack>
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

function DocumentUploadDialog({ open, onClose, document }) {
    const rows = document?.rows || [];

    return (
        <Dialog open={open} onClose={onClose} fullWidth maxWidth="lg">
            <DialogTitle sx={{ fontWeight: 'bold' }}>
                Data Upload Dokumen {document?.label || ''}
            </DialogTitle>
            <DialogContent dividers>
                <Stack spacing={2}>
                    <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} alignItems={{ xs: 'flex-start', sm: 'center' }} justifyContent="space-between">
                        <Typography variant="body2" color="text.secondary">
                            Menampilkan satker yang sudah mengunggah dokumen pada tahun/periode terpilih.
                        </Typography>
                        <Chip
                            color={rows.length > 0 ? 'success' : 'default'}
                            label={`${document?.uploaded_satkers ?? 0} / ${document?.total_satkers ?? 0} satker`}
                        />
                    </Stack>

                    {rows.length > 0 ? (
                        <TableContainer component={Paper} variant="outlined" sx={{ maxHeight: 520 }}>
                            <Table stickyHeader size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell width={56}>No</TableCell>
                                        <TableCell>Satker</TableCell>
                                        <TableCell width={120}>Kode</TableCell>
                                        <TableCell width={120}>Periode</TableCell>
                                        <TableCell width={120}>Perubahan</TableCell>
                                        <TableCell width={180}>Tanggal Upload</TableCell>
                                        <TableCell>Nama File</TableCell>
                                        <TableCell width={88} align="center">File</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {rows.map((row, index) => (
                                        <TableRow key={`${row.id_satker}-${row.filename || index}`} hover>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell>
                                                <Typography variant="body2" fontWeight="bold">
                                                    {row.satkernama || '-'}
                                                </Typography>
                                            </TableCell>
                                            <TableCell>{row.id_satker}</TableCell>
                                            <TableCell>{row.period || '-'}</TableCell>
                                            <TableCell>{row.revision ?? '-'}</TableCell>
                                            <TableCell>{row.uploaded_at || '-'}</TableCell>
                                            <TableCell>
                                                <Typography variant="body2" sx={{ wordBreak: 'break-word' }}>
                                                    {row.filename || '-'}
                                                </Typography>
                                                {row.document_count > 1 && (
                                                    <Typography variant="caption" color="text.secondary">
                                                        {row.document_count} dokumen, menampilkan yang terbaru
                                                    </Typography>
                                                )}
                                            </TableCell>
                                            <TableCell align="center">
                                                {row.filename ? (
                                                    <FileLinkButton satkerId={row.id_satker} fileName={row.filename} variant="icon" tooltip="Lihat dokumen">
                                                        <OpenInNewIcon fontSize="small" />
                                                    </FileLinkButton>
                                                ) : (
                                                    '-'
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    ) : (
                        <Paper variant="outlined" sx={{ p: 4, textAlign: 'center' }}>
                            <Typography color="text.secondary">
                                Belum ada satker yang mengunggah dokumen ini.
                            </Typography>
                        </Paper>
                    )}
                </Stack>
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose}>Tutup</Button>
            </DialogActions>
        </Dialog>
    );
}

function SakipDocumentsPanel({ documents = [], idSatker, tahun }) {
    const availableCount = documents.filter((document) => document.available).length;

    return (
        <Card
            elevation={3}
            sx={{
                position: { lg: 'sticky' },
                top: { lg: 88 },
                overflow: 'hidden',
            }}
        >
            <CardHeader
                avatar={<DescriptionIcon />}
                title="Dokumen SAKIP"
                subheader={`Akses cepat dokumen tahun ${tahun}`}
                titleTypographyProps={{ variant: 'h5', fontWeight: 'bold' }}
                sx={{
                    backgroundColor: 'primary.main',
                    color: 'white',
                    '& .MuiCardHeader-subheader': { color: 'rgba(255,255,255,0.85)' },
                }}
            />
            <CardContent sx={{ maxHeight: { lg: 'calc(100vh - 132px)' }, overflowY: { lg: 'auto' } }}>
                <Paper variant="outlined" sx={{ p: 1.5, mb: 2, backgroundColor: '#fffaf0' }}>
                    <Stack direction="row" justifyContent="space-between" alignItems="center" spacing={1}>
                        <Box>
                            <Typography variant="caption" color="text.secondary">
                                Dokumen tersedia
                            </Typography>
                            <Typography variant="h5" fontWeight="bold">
                                {availableCount} / {documents.length}
                            </Typography>
                        </Box>
                        <Chip
                            color={availableCount === documents.length ? 'success' : 'warning'}
                            label={availableCount === documents.length ? 'Lengkap' : 'Perlu dilengkapi'}
                            size="small"
                        />
                    </Stack>
                </Paper>

                <Stack spacing={1.25}>
                    {documents.map((document) => (
                        <Paper key={document.key} variant="outlined" sx={{ p: 1.5 }}>
                            <Stack spacing={1}>
                                <Stack direction="row" spacing={1} justifyContent="space-between" alignItems="flex-start">
                                    <Box sx={{ minWidth: 0 }}>
                                        <Typography variant="subtitle2" fontWeight="bold">
                                            {document.label}
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary" display="block">
                                            {document.triwulan || `Periode ${document.period}`}
                                            {document.revision !== null ? `, versi ${document.revision}` : ''}
                                        </Typography>
                                    </Box>
                                    <Chip
                                        size="small"
                                        color={document.available ? 'success' : 'default'}
                                        label={document.available ? 'Tersedia' : 'Belum ada'}
                                    />
                                </Stack>

                                {document.available && (
                                    <>
                                        <Typography
                                            variant="caption"
                                            color="text.secondary"
                                            sx={{ wordBreak: 'break-word' }}
                                        >
                                            {document.filename}
                                        </Typography>
                                        {document.uploaded_at && (
                                            <Typography variant="caption" color="text.secondary">
                                                Diunggah: {document.uploaded_at}
                                            </Typography>
                                        )}
                                        <FileLinkButton satkerId={idSatker} fileName={document.filename}>
                                            Buka dokumen
                                        </FileLinkButton>
                                    </>
                                )}

                                <Button
                                    component={Link}
                                    href={document.manage_url}
                                    size="small"
                                    variant={document.available ? 'text' : 'outlined'}
                                    startIcon={<FolderOpenIcon />}
                                    sx={{ alignSelf: 'flex-start' }}
                                >
                                    {document.available ? 'Kelola dokumen' : 'Lengkapi dokumen'}
                                </Button>
                            </Stack>
                        </Paper>
                    ))}
                </Stack>
            </CardContent>
        </Card>
    );
}

export default function Dashboard(props) {
    const { auth, tahun, pengumuman, pohonKinerja, pohonPerPageOptions = [10, 25, 50], renstraTerisi, ikuTerisi, renjaTerisi, rkaklTerisi, dipaTerisi, rencanaAksiTerisi, documentUploads = {}, sakipDocuments = [] } = props;
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);
    const [selectedDocumentKey, setSelectedDocumentKey] = useState(null);
    const selectedDocument = selectedDocumentKey ? documentUploads[selectedDocumentKey] : null;

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />
            
            <Grid container spacing={3} alignItems="flex-start">
                <Grid item xs={12} lg={8}>
                    <Stack spacing={3}>
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

                        <PohonKinerjaSection pohonKinerja={pohonKinerja} perPageOptions={pohonPerPageOptions} />

                        {levelSakip !== 0 && (
                            <AnimatedCard index={1}>
                                <CardHeader
                                    title="Kepatuhan"
                                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                                />
                                <CardContent>
                                    <Grid container spacing={2}>
                                        {[
                                            ['renstra', 'Pengisian Renstra', renstraTerisi],
                                            ['iku', 'Pengisian IKU', ikuTerisi],
                                            ['renja', 'Pengisian Renja', renjaTerisi],
                                            ['rkakl', 'Pengisian RKAKL', rkaklTerisi],
                                            ['dipa', 'Pengisian DIPA', dipaTerisi],
                                            ['renaksi', 'Pengisian Rencana Aksi', rencanaAksiTerisi],
                                        ].map(([key, title, isFilled]) => (
                                            <Grid item xs={12} md={6} key={key}>
                                                <StatusCard
                                                    title={title}
                                                    isFilled={isFilled}
                                                    textFilled={`${title} sudah dilakukan`}
                                                    textNotFilled={`${title} belum dilakukan`}
                                                    uploadData={documentUploads[key]}
                                                    onOpen={() => setSelectedDocumentKey(key)}
                                                />
                                            </Grid>
                                        ))}
                                    </Grid>
                                </CardContent>
                            </AnimatedCard>
                        )}

                        <Grid container spacing={2}>
                            <Grid item xs={12} md={6}>
                                <AnimatedCard index={2}>
                                    <CardContent>
                                        <Typography variant="h5" component="h2" sx={{ fontWeight: 'bold' }}>
                                            Sumber Aturan
                                        </Typography>
                                        <Typography color="text.secondary" sx={{ my: 1 }}>
                                            Lihat sumber aturan dan referensi hukum yang relevan.
                                        </Typography>
                                        <Button component={Link} href="/aturan" variant="contained">
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
                                        <Button component={Link} href="/faq" variant="contained">
                                            Lihat FAQ
                                        </Button>
                                    </CardContent>
                                </AnimatedCard>
                            </Grid>
                        </Grid>
                    </Stack>
                </Grid>

                <Grid item xs={12} lg={4}>
                    <SakipDocumentsPanel
                        documents={sakipDocuments}
                        idSatker={auth.user?.id_satker}
                        tahun={tahun}
                    />
                </Grid>
            </Grid>

            <DocumentUploadDialog
                open={Boolean(selectedDocument)}
                document={selectedDocument}
                onClose={() => setSelectedDocumentKey(null)}
            />
        </AuthenticatedLayout>
    );
}
