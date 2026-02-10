// resources/js/Layouts/AuthenticatedLayout.jsx
import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Box, Drawer, Toolbar } from '@mui/material';

// Impor Partial Layout Versi MUI
import AppNavbar from '@/Components/AppNavbar';
import AppSidebar from '@/Components/AppSidebar';
import AppFooter from '@/Components/AppFooter';

// Tentukan lebar sidebar
const drawerWidth = 250; 

export default function AuthenticatedLayout({ header, children }) {
    const { auth, tahun } = usePage().props;
    
    // State untuk sidebar mobile
    const [mobileOpen, setMobileOpen] = useState(false);

    const handleDrawerToggle = () => {
        setMobileOpen(!mobileOpen);
    };

    // Konten sidebar (didefinisikan sekali, dipakai dua kali)
    const drawerContent = (
        <AppSidebar user={auth.user} currentYear={tahun} />
    );

    return (
        <Box sx={{ display: 'flex' }}>
            <Head>
                {/* Judul akan diambil dari props 'header' atau halaman */}
                <meta charSet="UTF-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <link rel="icon" href="/gambar/kejaksaan.png" type="image/png" />
            </Head>

            {/* Panggil Komponen Navbar */}
            <AppNavbar
                user={auth.user}
                currentYear={tahun}
                onToggleSidebar={handleDrawerToggle}
                drawerWidth={drawerWidth}
            />

            {/* Sidebar */}
            <Box
                component="nav"
                sx={{ width: { lg: drawerWidth }, flexShrink: { lg: 0 } }}
                aria-label="mailbox folders"
            >
                {/* Sidebar Mobile (Temporary Drawer) */}
                <Drawer
                    variant="temporary"
                    open={mobileOpen}
                    onClose={handleDrawerToggle}
                    ModalProps={{
                        keepMounted: true, // Performa lebih baik di mobile
                    }}
                    sx={{
                        display: { xs: 'block', lg: 'none' },
                        '& .MuiDrawer-paper': { boxSizing: 'border-box', width: drawerWidth },
                    }}
                >
                    {drawerContent}
                </Drawer>

                {/* Sidebar Desktop (Permanent Drawer) */}
                <Drawer
                    variant="permanent"
                    sx={{
                        display: { xs: 'none', lg: 'block' },
                        '& .MuiDrawer-paper': { boxSizing: 'border-box', width: drawerWidth },
                    }}
                    open
                >
                    {drawerContent}
                </Drawer>
            </Box>

            {/* Wrapper Konten Utama & Footer */}
            <Box
                component="main"
                sx={{ 
                    flexGrow: 1, 
                    p: 3, // Padding konten
                    width: { lg: `calc(100% - ${drawerWidth}px)` },
                    display: 'flex',
                    flexDirection: 'column',
                    minHeight: '100vh' // Pastikan mengisi tinggi
                }}
            >
                {/* Toolbar ini bertindak sebagai 'spacer' agar konten tidak di bawah AppBar */}
                <Toolbar />

                {/* Ini adalah @yield('content') */}
                <Box component="div" sx={{ flexGrow: 1 }}> {/* Konten mengisi ruang */}
                    {children}
                </Box>

                {/* Panggil Komponen Footer */}
                <AppFooter />
            </Box>
        </Box>
    );
}