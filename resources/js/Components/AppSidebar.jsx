// resources/js/Layouts/Partials/AppSidebar.jsx
import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Box, List, ListItem, ListItemButton, ListItemIcon, ListItemText, Collapse, Avatar, Typography, Divider } from '@mui/material';
// Impor Ikon MUI
import HomeIcon from '@mui/icons-material/Home';
import DashboardIcon from '@mui/icons-material/Dashboard';
import AssignmentIcon from '@mui/icons-material/Assignment';
import BarChartIcon from '@mui/icons-material/BarChart';
import AssessmentIcon from '@mui/icons-material/Assessment';
import FactCheckIcon from '@mui/icons-material/FactCheck';
import LanguageIcon from '@mui/icons-material/Language';
import StorageIcon from '@mui/icons-material/Storage';
import GavelIcon from '@mui/icons-material/Gavel';
import QuizIcon from '@mui/icons-material/Quiz';
import VpnKeyIcon from '@mui/icons-material/VpnKey';
import AdminPanelSettingsIcon from '@mui/icons-material/AdminPanelSettings';
import ExpandLess from '@mui/icons-material/ExpandLess';
import ExpandMore from '@mui/icons-material/ExpandMore';
import VisibilityIcon from '@mui/icons-material/Visibility';
import AnalyticsIcon from '@mui/icons-material/Analytics';
import PsychologyIcon from '@mui/icons-material/Psychology';
import { CloudDownload } from '@mui/icons-material';

export default function AppSidebar({ user, currentYear }) {
    const { url } = usePage();
    const satkernama = user?.satkernama || 'Nama Satker';
    const rawIdSatker = String(user?.id_satker || '');
    const idSatker = (rawIdSatker || 'ID Satker').padStart(6, '0'); // Format ID Satker jadi 6 digit dengan leading zeros
    const levelSakip = parseInt(user?.id_sakip_level || 0, 10);
    const tahunAplikasi = currentYear;
    const adminSatkerIds = ['admin', '999999', 'menpanrb', 'Pengawasan', 'Panev'];
    const isAdmin = levelSakip === 99 || adminSatkerIds.includes(rawIdSatker);
    const canUseScopedSatkerPages = [99, 2, 1, 0].includes(levelSakip);
    const canUseSpipInput = [1, 2].includes(levelSakip)
        && !['888881', '888882', '999999', 'admin', 'Pengawasan', 'Panev', 'menpanrb'].includes(rawIdSatker);

    // Cek submenu aktif
    const isSubmenuActive = [
        '/kep', '/perencanaan', '/pengukuran', '/pelaporan',
        '/evaluasi', '/evaluasi-akip', '/upload/bukti-dukung', '/spip'
    ].some(path => url.startsWith(path));
    const isKewilayahanActive = ['/sakipwil', '/was-lke'].some(path => url.startsWith(path));
    const isAdministratorActive = [
        '/admin/dashboard-analitik', '/admin/business-intelligence', '/admin/spip', '/keloladata', '/diagnostik', '/monitoring', '/admin/download-dokumen'
    ].some(path => url.startsWith(path));

    // State untuk collapse submenu
    const [submenuOpen, setSubmenuOpen] = useState(isSubmenuActive);
    const [kewilayahanOpen, setKewilayahanOpen] = useState(isKewilayahanActive);
    const [administratorOpen, setAdministratorOpen] = useState(isAdministratorActive);

    // Komponen helper untuk link
    const NavLink = ({ href, icon, text, active }) => (
        <ListItem disablePadding>
            <ListItemButton component={Link} href={href} selected={active}>
                <ListItemIcon>{icon}</ListItemIcon>
                <ListItemText primary={text} />
            </ListItemButton>
        </ListItem>
    );

    return (
        <Box>
            {/* User Info */}
            <Box sx={{ p: 2, textAlign: 'center' }}>
                <Avatar
                    src="/gambar/kejaksaan.png" // Path ke logo
                    alt="Profile Picture"
                    sx={{ width: 60, height: 60, margin: '0 auto 8px auto' }}
                />
                <Typography variant="subtitle2" sx={{ fontWeight: 'bold' }}>Selamat Datang</Typography>
                <Typography variant="caption" display="block">{satkernama}</Typography>
                <Typography variant="caption" display="block">ID Satker: {idSatker}</Typography>
            </Box>
            <Divider />

            {/* List Menu */}
            <List>
                <NavLink href="/dashboard" icon={<HomeIcon />} text="Beranda" active={url === '/dashboard'} />

                {/* === Tata Kelola AKIP Dropdown === */}
                {[99, 1, 2, 3, 4].includes(levelSakip) && (
                    <>
                        <ListItemButton onClick={() => setSubmenuOpen(!submenuOpen)} selected={isSubmenuActive}>
                            <ListItemIcon><DashboardIcon /></ListItemIcon>
                            <ListItemText primary="Tata Kelola AKIP" />
                            {submenuOpen ? <ExpandLess /> : <ExpandMore />}
                        </ListItemButton>
                        <Collapse in={submenuOpen} timeout="auto" unmountOnExit>
                            <List component="div" disablePadding sx={{ pl: 4 }}>
                                <NavLink href="/perencanaan" icon={<AssignmentIcon />} text="Perencanaan" active={url.startsWith('/perencanaan')} />
                                {tahunAplikasi != 2024 && (
                                    <NavLink href="/pengukuran" icon={<BarChartIcon />} text="Pengukuran" active={url.startsWith('/pengukuran')} />
                                )}
                                <NavLink href="/pelaporan" icon={<AssessmentIcon />} text="Pelaporan" active={url.startsWith('/pelaporan')} />
                                <NavLink href="/evaluasi" icon={<FactCheckIcon />} text="Evaluasi" active={url.startsWith('/evaluasi')} />
                                {canUseSpipInput && (
                                    <NavLink href="/spip" icon={<FactCheckIcon />} text="SPIP" active={url.startsWith('/spip')} />
                                )}
                                {/* ... tambahkan item submenu lainnya ... */}
                            </List>
                        </Collapse>
                    </>
                )}

                {/* === Kewilayahan Dropdown === */}
                {canUseScopedSatkerPages && (
                    <>
                        <ListItemButton onClick={() => setKewilayahanOpen(!kewilayahanOpen)} selected={isKewilayahanActive}>
                            <ListItemIcon><LanguageIcon /></ListItemIcon>
                            <ListItemText primary="Kewilayahan" />
                            {kewilayahanOpen ? <ExpandLess /> : <ExpandMore />}
                        </ListItemButton>
                        <Collapse in={kewilayahanOpen} timeout="auto" unmountOnExit>
                            <List component="div" disablePadding sx={{ pl: 4 }}>
                                <NavLink href="/sakipwil" icon={<LanguageIcon />} text="SAKIP Wilayah" active={url.startsWith('/sakipwil')} />
                                <NavLink href="/was-lke" icon={<VisibilityIcon />} text="Evaluasi Wilayah" active={url.startsWith('/was-lke')} />
                            </List>
                        </Collapse>
                    </>
                )}

                {/* === Administrator Dropdown === */}
                {isAdmin && (
                    <>
                        <ListItemButton onClick={() => setAdministratorOpen(!administratorOpen)} selected={isAdministratorActive}>
                            <ListItemIcon><AdminPanelSettingsIcon /></ListItemIcon>
                            <ListItemText primary="Administrator" />
                            {administratorOpen ? <ExpandLess /> : <ExpandMore />}
                        </ListItemButton>
                        <Collapse in={administratorOpen} timeout="auto" unmountOnExit>
                            <List component="div" disablePadding sx={{ pl: 4 }}>
                                <NavLink href="/admin/dashboard-analitik" icon={<AnalyticsIcon />} text="Dashboard Analitik" active={url.startsWith('/admin/dashboard-analitik')} />
                                <NavLink href="/admin/business-intelligence" icon={<PsychologyIcon />} text="Business Intelligence" active={url.startsWith('/admin/business-intelligence')} />
                                <NavLink href="/admin/spip" icon={<FactCheckIcon />} text="Admin SPIP" active={url.startsWith('/admin/spip')} />
                                <NavLink href="/keloladata" icon={<StorageIcon />} text="Kelola Data" active={url.startsWith('/keloladata')} />
                                <NavLink href="/diagnostik" icon={<FactCheckIcon />} text="Pengecekan Sistem" active={url.startsWith('/diagnostik')} />
                                <NavLink href="/monitoring" icon={<BarChartIcon />} text="Monitoring" active={url.startsWith('/monitoring')} />
                                <NavLink href="/admin/download-dokumen" icon={<CloudDownload />} text="Download Arsip Wilayah" active={url.startsWith('/admin/download-dokumen')} />
                            </List>
                        </Collapse>
                    </>
                )}
                
                {/* === Menu Bantuan === */}
                {[99, 1, 2, 3, 4].includes(levelSakip) || rawIdSatker.startsWith('Pengawasan') || rawIdSatker.startsWith('menpanrb') || rawIdSatker.startsWith('Panev') ? (
                    <>
                        <NavLink href="/aturan" icon={<GavelIcon />} text="Sumber Aturan" active={url.startsWith('/aturan')} />
                        <NavLink href="/faq" icon={<QuizIcon />} text="FAQ" active={url.startsWith('/faq')} />
                        {isAdmin && (
                            <NavLink href="/ubahpassword" icon={<VpnKeyIcon />} text="Ubah Password" active={url.startsWith('/ubahpassword')} />
                        )}
                    </>
                ) : null}
            </List>
        </Box>
    );
}
