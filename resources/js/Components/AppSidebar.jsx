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
import SecurityIcon from '@mui/icons-material/Security';
import SupportAgentIcon from '@mui/icons-material/SupportAgent';
import CampaignIcon from '@mui/icons-material/Campaign';
import StorageIcon from '@mui/icons-material/Storage';
import GavelIcon from '@mui/icons-material/Gavel';
import QuizIcon from '@mui/icons-material/Quiz';
import VpnKeyIcon from '@mui/icons-material/VpnKey';
import ExpandLess from '@mui/icons-material/ExpandLess';
import ExpandMore from '@mui/icons-material/ExpandMore';
import CloudUploadIcon from '@mui/icons-material/CloudUpload';
import VisibilityIcon from '@mui/icons-material/Visibility';
import { CloudDownload } from '@mui/icons-material';

export default function AppSidebar({ user, currentYear }) {
    const { url } = usePage();
    const satkernama = user?.satkernama || 'Nama Satker';
    const idSatker = user?.id_satker || 'ID Satker';
    const levelSakip = parseInt(user?.id_sakip_level || 0, 10);
    const tahunAplikasi = currentYear;

    // Cek submenu aktif
    const isSubmenuActive = [
        '/kep', '/perencanaan', '/pengukuran', '/pelaporan',
        '/evaluasi', '/evaluasi-akip', '/upload/bukti-dukung'
    ].some(path => url.startsWith(path));

    // State untuk collapse submenu
    const [submenuOpen, setSubmenuOpen] = useState(isSubmenuActive);

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
                                {/* ... tambahkan item submenu lainnya ... */}
                            </List>
                        </Collapse>
                    </>
                )}

                {/* === Menu Utama Lainnya === */}
                {[99, 0, 2].includes(levelSakip) && (
                    <NavLink href="/sakipwil" icon={<LanguageIcon />} text="SAKIP Wilayah" active={url.startsWith('/sakipwil')} />
                )}
                {/* ... (Tambahkan link lain dengan ikon MUI) ... */}
                {[99, 0].includes(levelSakip) && (
                    <NavLink href="/was-lke" icon={<VisibilityIcon />} text="Evaluasi" active={url.startsWith('/was-lke')} />
                )}

                {/* === Menu Admin (Level 99) === */}
                {levelSakip === 99 && (
                    <>
                        <NavLink href="/sakipvalidasi" icon={<SecurityIcon />} text="SAKIP Validasi" active={url.startsWith('/sakipvalidasi')} />
                        <NavLink href="/chatsupport" icon={<SupportAgentIcon />} text="Chat Support" active={url.startsWith('/chatsupport')} />
                        <NavLink href="/pengumuman" icon={<CampaignIcon />} text="Pengumuman" active={url.startsWith('/pengumuman')} />
                        <NavLink href="/keloladata" icon={<StorageIcon />} text="Kelola Data" active={url.startsWith('/keloladata')} />
                        <NavLink href="/monitoring" icon={<BarChartIcon />} text="Monitoring" active={url.startsWith('/monitoring')} />
                        <NavLink 
                            href={route('admin.download.index')} 
                            icon={<CloudDownload />}
                            active={route().current('admin.download.index')}
                            text=" Download Arsip Wilayah"
                        />
                           
                        
                    </>
                )}
                
                {/* === Menu Bantuan === */}
                {[99, 1, 2, 3, 4].includes(levelSakip) || String(idSatker).startsWith('Pengawasan') || String(idSatker).startsWith('menpanrb') || String(idSatker).startsWith('Panev') ? (
                    <>  <NavLink href="/indikator-view" icon={<CloudUploadIcon />} text="Indikator Sastra & Saspro" active={url.startsWith('/indikator-view')} />
                        <NavLink href="/aturan" icon={<GavelIcon />} text="Sumber Aturan" active={url.startsWith('/aturan')} />
                        <NavLink href="/faq" icon={<QuizIcon />} text="FAQ" active={url.startsWith('/faq')} />
                        {levelSakip === 99 && (
                            <NavLink href="/ubahpassword" icon={<VpnKeyIcon />} text="Ubah Password" active={url.startsWith('/ubahpassword')} />
                        )}
                    </>
                ) : null}
            </List>
        </Box>
    );
}