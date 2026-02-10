// resources/js/Components/SakipWil/PieChartCard.jsx
import React from 'react';
import { Card, CardContent, CardHeader, Typography } from '@mui/material';
import { Pie } from 'react-chartjs-2';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

export default function PieChartCard({ title, dataList }) {
    // Hitung data terisi vs belum
    const terisi = dataList ? dataList.filter(item => item).length : 0;
    const belumTerisi = dataList ? dataList.length - terisi : 0;

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