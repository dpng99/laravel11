import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { 
    Box, 
    Card, 
    CardContent, 
    Button, 
    TextField, 
    Typography, 
    Alert, 
    Container, 
    CssBaseline 
} from '@mui/material';
import GuestLayout from '@/Layouts/GuestLayout';
export default function Login({ status }) {
    
    // Inisialisasi form handling dengan Inertia
    const { data, setData, post, processing, errors } = useForm({
        email: '', // 'email' digunakan untuk 'Kode Satker'
        password: '',
    });

    // Handle submit form
    const submit = (e) => {
        e.preventDefault();
        post('/login-auto'); // Kirim ke rute /login (tanpa Ziggy)        post('/login-auto'); // Kirim ke rute /login (tanpa Ziggy)
    };

    return (
        <GuestLayout>
            <Head title="Login E-SAKIP" />
            <CssBaseline />

            <Container component="main" maxWidth="xs">
                <Card 
                    elevation={6} 
                    sx={{ 
                        borderRadius: 4, 
                        backgroundColor: 'rgba(255, 255, 255, 0.5)', // Sedikit transparan
                        padding: 3 
                    }}
                >
                    <CardContent sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                        
                        {/* --- Logo & Header --- */}
                        <Box sx={{ mb: 3, textAlign: 'center', display: 'flex', 
                            flexDirection: 'column', 
                            alignItems: 'center', }}>
                            <img 
                                src="/gambar/kejaksaan.png" 
                                alt="Logo Kejaksaan" 
                                style={{ width: '80px', height: 'auto', marginBottom: '16px', alignContent: 'center' }} 
                            />
                            <Typography variant="overline" display="block" sx={{ letterSpacing: 3, color: 'text.secondary', fontWeight: 'bold' }}>
                                E-SAKIP
                            </Typography>
                            <Typography component="h1" variant="h6" sx={{ fontWeight: '800', color: '#333', lineHeight: 1.2 }}>
                                KEJAKSAAN AGUNG <br /> REPUBLIK INDONESIA
                            </Typography>
                        </Box>

                        {/* --- Alert Status / Error --- */}
                        {status && (
                            <Alert severity="success" sx={{ width: '100%', mb: 2 }}>
                                {status}
                            </Alert>
                        )}

                        {/* --- Form Login --- */}
                        <Box component="form" onSubmit={submit} noValidate sx={{ mt: 1, width: '100%' }}>
                            
                            <TextField
                                margin="normal"
                                required
                                fullWidth
                                id="kode_satker"
                                label="Kode Satker"
                                name="email"
                                autoComplete="username"
                                autoFocus
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={!!errors.email}
                                helperText={errors.email}
                                // Style input agar terlihat lebih soft
                                sx={{
                                    '& .MuiOutlinedInput-root': {
                                        borderRadius: 2,
                                        backgroundColor: '#f8f9fa'
                                    }
                                }}
                            />

                            <TextField
                                margin="normal"
                                required
                                fullWidth
                                name="password"
                                label="Password"
                                type="password"
                                id="password"
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={!!errors.password}
                                helperText={errors.password}
                                sx={{
                                    '& .MuiOutlinedInput-root': {
                                        borderRadius: 2,
                                        backgroundColor: '#f8f9fa'
                                    }
                                }}
                            />

                            <Button
                                type="submit"
                                fullWidth
                                variant="contained"
                                disabled={processing}
                                sx={{
                                    mt: 3,
                                    mb: 2,
                                    py: 1.5,
                                    borderRadius: 50, // Membuat tombol bulat (pill shape)
                                    backgroundColor: '#f0bb49', // Warna kuning kejaksaan
                                    fontWeight: 'bold',
                                    fontSize: '1rem',
                                    color: '#fff',
                                    boxShadow: '0 4px 12px rgba(240, 187, 73, 0.4)',
                                    '&:hover': {
                                        backgroundColor: '#d4a030',
                                    },
                                }}
                            >
                                {processing ? 'Memproses...' : 'Login'}
                            </Button>

                            {/* --- Footer --- */}
                            <Typography variant="body2" color="text.secondary" align="center" sx={{ mt: 3, fontSize: '0.75rem' }}>
                                Panev BiroCana Kejaksaan RI @2025
                            </Typography>
                        </Box>
                    </CardContent>
                </Card>
            </Container>
        </GuestLayout>
    );
}