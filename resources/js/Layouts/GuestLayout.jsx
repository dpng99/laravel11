// resources/js/Layouts/GuestLayout.jsx
import React from 'react';
import { Container } from 'react-bootstrap';

export default function GuestLayout({ children }) {
    
    // Style object to apply the background image and centering
const backgroundStyle = {
        backgroundImage: 'url("/gambar/background.jpg")', // Pastikan Anda memiliki gambar peta transparan di folder public/gambar/
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: '#f8f9fa' // Fallback color
    };

    return (
        <div style={backgroundStyle}>
            {children}
        </div>
    );
}