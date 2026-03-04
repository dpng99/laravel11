// resources/js/Components/SakipWil/PieChartCard.jsx
import React from 'react';
import { Card, CardContent, CardHeader, Typography } from '@mui/material';
import { Pie } from 'react-chartjs-2';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

export default function PieChartCard({ title, dataList }) {
// 1. Pastikan dataList diubah menjadi Array (mengambil nilai/values-nya saja)
    const safeDataList = dataList 
        ? (Array.isArray(dataList) ? dataList : Object.values(dataList)) 
        : [];

    // 2. Hitung data terisi vs belum menggunakan array yang sudah aman
    const terisi = safeDataList.filter(item => item !== null && item !== '').length;
    const belumTerisi = safeDataList.length - terisi;
    
    const data = {
        labels: ['Terisi', 'Belum Terisi'],
        datasets: [{
            data: [terisi, belumTerisi],
            backgroundColor: ['#4CAF50', '#E53935'], // Hijau & Merah
            borderColor: ['#FFFFFF', '#FFFFFF'],
            borderWidth: 2,
        }],
    };

    const options = {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
        },
    };

    return (
        <Card elevation={2}>
            <CardHeader
                title={title}
                titleTypographyProps={{ variant: 'h6', align: 'center', fontWeight: 'bold' }}
            />
            <CardContent>
                {dataList ? (
                    <Pie data={data} options={options} />
                ) : (
                    <Typography align="center" color="text.secondary">Data tidak tersedia</Typography>
                )}
            </CardContent>
        </Card>
    );
}