import React from 'react';
import { Typography, IconButton, Tooltip } from '@mui/material';

export default function FileLinkButton({ 
    satkerId, 
    fileName, 
    variant = "text", // Pilihan wujud: "text" atau "icon"
    tooltip = "Buka Dokumen",
    children // 🔥 INI KUNCI DINAMISNYA: Menerima teks atau elemen apapun dari luar
}) {
    if (!fileName) return null;

    // 1. Amankan ID Satker (006050) dan Nama File untuk URL
    const safeSatkerId = String(satkerId).padStart(6, '0');
    const safeFileName = encodeURIComponent(fileName);
    const url = `/file/view/${safeSatkerId}/${safeFileName}`;

    // 2. Jika wujudnya Icon (Untuk Sakipwil atau tabel ringkas)
    if (variant === "icon") {
        return (
            <Tooltip title={tooltip}>
                <IconButton component="a" href={url} target="_blank" rel="noopener noreferrer" color="primary" size="small">
                    {/* Render icon apapun yang dikirim dari luar */}
                    {children} 
                </IconButton>
            </Tooltip>
        );
    }

    // 3. Jika wujudnya Teks (Untuk Perencanaan, Pelaporan, Evaluasi)
    return (
        <Typography 
            component="a" 
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            variant="caption"
            sx={{ 
                textDecoration: 'none', 
                color: '#1976d2', 
                cursor: 'pointer', 
                fontWeight: 'bold',
                '&:hover': { textDecoration: 'underline' } 
            }}
        >
            {/* Render teks apapun yang dikirim dari luar, fallback ke default jika kosong */}
            {children || "[ Lihat Dokumen ]"}
        </Typography>
    );
}