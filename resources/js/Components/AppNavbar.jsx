// resources/js/Layouts/Partials/AppNavbar.jsx
import React from 'react';
import { Link, router } from '@inertiajs/react';
import { AppBar, Toolbar, Typography, Button, Select, MenuItem, IconButton, FormControl, Box } from '@mui/material';
import MenuIcon from '@mui/icons-material/Menu'; // Ikon Hamburger
import LogoutIcon from '@mui/icons-material/Logout'; // Ikon Logout

// Terima props drawerWidth dan handleDrawerToggle
export default function AppNavbar({ user, currentYear, onToggleSidebar, drawerWidth }) {
    const satkernama = user?.satkernama || 'Nama Satker';

    const renderTahunOptions = () => {
        const currentSystemYear = new Date().getFullYear();
        const years = [];
        for (let i = 2024; i <= currentSystemYear + 5; i++) {
            years.push(
                <MenuItem key={i} value={i}>
                    {i}
                </MenuItem>
            );
        }
        return years;
    };

    const handleYearChange = (event) => {
        const selectedYear = event.target.value;
        router.post('/pilih-tahun', { tahun: selectedYear }, { preserveState: true });
    };

    return (
        <AppBar
            position="fixed"
            color="inherit" // Ganti 'inherit' agar warnanya putih/terang
            elevation={1} // Bayangan tipis
            sx={{
                width: { lg: `calc(100% - ${drawerWidth}px)` }, // Lebar di desktop
                ml: { lg: `${drawerWidth}px` }, // Margin kiri di desktop
                zIndex: (theme) => theme.zIndex.drawer + 1, // Tampil di atas drawer
            }}
        >
            <Toolbar>
                {/* Tombol Hamburger Mobile */}
                <IconButton
                    color="inherit"
                    aria-label="open drawer"
                    edge="start"
                    onClick={onToggleSidebar}
                    sx={{ mr: 2, display: { lg: 'none' } }} // Tampil hanya di mobile
                >
                    <MenuIcon />
                </IconButton>
                
                {/* Nama Satker */}
                <Typography variant="h6" noWrap component="div" sx={{ display: { xs: 'none', sm: 'block' } }}>
                    {satkernama}
                </Typography>

                {/* Spacer (mendorong item ke kanan) */}
                <Box sx={{ flexGrow: 1 }} />

                {/* Pemilih Tahun */}
                <FormControl variant="standard" size="small" sx={{ minWidth: 100, mr: 2 }}>
                    <Select
                        id="tahunSelectNavbar"
                        value={currentYear}
                        onChange={handleYearChange}
                        disableUnderline
                    >
                        {renderTahunOptions()}
                    </Select>
                </FormControl>

                {/* Tombol Logout */}
                <Button
                    component={Link} // Buat tombol berfungsi sebagai Link Inertia
                    href="/logout"
                    method="post"
                    variant="contained"
                    color="error" // Warna merah
                    size="small"
                    startIcon={<LogoutIcon />}
                >
                    Logout
                </Button>
            </Toolbar>
        </AppBar>
    );
}