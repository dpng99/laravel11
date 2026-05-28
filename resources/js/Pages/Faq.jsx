import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

// Import komponen Material-UI
import {
    Card,
    CardContent,
    Typography,
    Box,
    Accordion,
    AccordionSummary,
    AccordionDetails,
    Container
} from '@mui/material';

// Import Icon
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import HelpOutlineIcon from '@mui/icons-material/HelpOutline';

export default function Faq() {
    // Memisahkan data FAQ ke dalam Array agar kode lebih bersih (Clean Code)
    const faqData = [
        {
            question: "1. Bagaimana cara login?",
            answer: "Untuk login gunakan akun sebagaimana telah diberikan."
        },
        {
            question: "2. Bagaimana cara memulai untuk mengisi pelaporan AKIP?",
            answer: "Satker harus mengupload SK Tim AKIP terlebih dahulu untuk membuka seluruh form yang ada, baik dari form 1 hingga form 6."
        },
        {
            question: "3. Mengapa tidak ada fungsi perbaikan pada upload Keputusan maupun isian AKIP?",
            answer: "Kami menyediakan informasi pada setiap fungsi pengisian dan upload. Baca dengan seksama dan hindari kesalahan. Aplikasi ini dibuat dengan metode proses bisnis yang mengalir sehingga proses harus berurutan dan benar."
        },
        {
            question: "4. Apa saja yang harus \"kami\" persiapkan dalam pengisian AKIP?",
            answer: "Siapkan program/kegiatan yang didukung oleh DIPA. Ketahui tugas dan fungsi sesuai jabatan. Siapkan renstra, renja, IKU, IKI, dan PK."
        },
        {
            question: "5. Siapakah yang mengisi AKIP?",
            answer: "Pengisian dilakukan oleh seluruh bagian/bidang yang ada di bawah kendali Kasatker."
        },
        {
            question: "6. Apakah maksud pelaksanaan AKIP terkolaborasi?",
            answer: "Jangan menyerahkan tanggung jawab penuh kepada operator input. Setiap pejabat harus melaksanakan tanggung jawabnya masing-masing."
        },
        {
            question: "7. Dimana saya bisa mendapatkan informasi lebih tentang AKIP?",
            answer: "Pada menu login, terdapat tab menu download untuk mengunduh informasi terkait AKIP."
        }
    ];

    return (
        <AuthenticatedLayout>
            <Head title="FAQ - Bantuan" />

            <Container maxWidth="md" sx={{ mt: 4, mb: 6 }}>
                <Card elevation={4} sx={{ borderRadius: 3 }}>
                    
                    {/* Header Kotak Kuning Khas SAKIP */}
                    <Box sx={{
                        bgcolor: '#e6bf3e', // Warna kuning SAKIP
                        color: 'black',
                        py: 3,
                        px: 2,
                        textAlign: 'center',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        gap: 1
                    }}>
                        <HelpOutlineIcon sx={{ fontSize: 40 }} />
                        <Typography variant="h5" sx={{ fontWeight: 'bold' }}>
                            FAQ
                        </Typography>
                        <Typography variant="subtitle1" sx={{ fontWeight: 'bold' }}>
                            Penggunaan Aplikasi SAKIP Kejaksaan RI
                        </Typography>
                    </Box>

                    <CardContent sx={{ p: { xs: 2, sm: 4 } }}>
                        <Typography variant="body1" color="text.secondary" sx={{ mb: 4, textAlign: 'center' }}>
                            Berikut adalah daftar pertanyaan yang paling sering ditanyakan seputar penggunaan aplikasi. Silakan klik pada pertanyaan untuk melihat jawabannya.
                        </Typography>

                        {/* Looping Data FAQ menjadi Accordion */}
                        {faqData.map((faq, index) => (
                            <Accordion 
                                key={index} 
                                disableGutters 
                                elevation={0} 
                                sx={{
                                    border: '1px solid #e0e0e0',
                                    borderRadius: '8px !important', // Memaksa sudut melengkung
                                    mb: 1.5,
                                    '&:before': { display: 'none' }, // Menghilangkan garis default MUI
                                    transition: '0.3s',
                                    '&:hover': {
                                        boxShadow: '0 4px 12px rgba(0,0,0,0.05)'
                                    }
                                }}
                            >
                                <AccordionSummary
                                    expandIcon={<ExpandMoreIcon sx={{ color: '#1976d2' }} />}
                                    sx={{ 
                                        px: 3, 
                                        backgroundColor: '#fafafa',
                                        borderRadius: '8px'
                                    }}
                                >
                                    <Typography variant="subtitle1" sx={{ fontWeight: 'bold', color: '#333' }}>
                                        {faq.question}
                                    </Typography>
                                </AccordionSummary>
                                <AccordionDetails sx={{ px: 3, py: 2, backgroundColor: '#fff' }}>
                                    <Typography variant="body1" color="text.secondary" sx={{ lineHeight: 1.7 }}>
                                        <strong style={{ color: '#000' }}>Jawaban:</strong> {faq.answer}
                                    </Typography>
                                </AccordionDetails>
                            </Accordion>
                        ))}
                    </CardContent>
                </Card>
            </Container>
        </AuthenticatedLayout>
    );
}