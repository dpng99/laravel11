// resources/js/Layouts/Partials/AppFooter.jsx
import React from 'react';
import { Box, Typography } from '@mui/material';

export default function AppFooter() {
    return (
        <Box 
            component="footer" 
            sx={{
                py: 1, // Padding Y
                px: 2, // Padding X
                mt: 'auto', // Margin top auto (mendorong ke bawah)
                backgroundColor: '#dbdbdb', // Warna dari CSS lama
                textAlign: 'center'
            }}
        >
            <Typography variant="body2" color="text.primary">
                <b>Panev BiroCana Kejaksaan RI</b> @2025
            </Typography>
        </Box>
    );
}