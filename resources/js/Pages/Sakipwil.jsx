// resources/js/Pages/Sakipwil.jsx
import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, Grid, Typography, Box, Button } from '@mui/material';

// Impor komponen yang baru dibuat
import SakipWilTable from '@/Components/SakipWil/SakipWilTable';
import PieChartCard from '@/Components/SakipWil/PieChartCard';

export default function Sakipwil(props) {
    // 'props' akan berisi semua data yang dikirim dari SakipwilController
    const { auth } = usePage().props;
    const levelSakip = parseInt(auth.user?.id_sakip_level || 0, 10);

    // Siapkan data untuk charts
    const chartData = {
        renstra: props.sortedRenstraList,
        iku: props.sortedIkuList,
        renja: props.sortedRenjaList,
        rkakl: props.sortedRkaklList,
        dipa: props.sortedDipaList,
        renaksi: props.sortedRenaksiList,
        pk: props.sortedPkList,
        lkjipTW1: Object.values(props.sortedLkjipTW1), // Ubah objek ke array
        lkjipTW2: Object.values(props.sortedLkjipTW2),
        lkjipTW3: Object.values(props.sortedLkjipTW3),
        lkjipTW4: Object.values(props.sortedLkjipTW4),
        rastaff: props.sortedRastaffList,
        lhe: props.sortedLheList,
        tlLheAkip: props.sortedTlLheAkipList,
        monevRenaksi: props.sortedMonevRenaksiList,
    };

    return (
        <AuthenticatedLayout>
            <Head title="SAKIP Wilayah" />

            <Card elevation={3}>
                <CardHeader
                    title="DATA PERENCANAAN AKIP SATUAN KERJA KEJAKSAAN RI"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: 'primary.main', color: 'white' }}
                />
                <CardContent>
                    {/* Kirim semua props tabel ke komponen SakipWilTable */}
                    <SakipWilTable {...props} />
                </CardContent>
            </Card>

            {/* Bagian Rekap/Chart */}
            <Card elevation={3} sx={{ mt: 3 }}>
                <CardHeader
                    title="Rekap Dokumen Seluruh Satker"
                    titleTypographyProps={{ variant: 'h5', align: 'center', fontWeight: 'bold' }}
                    sx={{ backgroundColor: '#e3e2e2' }} // Warna abu-abu
                />
                <CardContent>
                    <Grid container spacing={3}>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="Renstra" dataList={chartData.renstra} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="IKU" dataList={chartData.iku} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="Renja" dataList={chartData.renja} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="RKAKL" dataList={chartData.rkakl} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="DIPA" dataList={chartData.dipa} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="Renaksi" dataList={chartData.renaksi} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="Perjanjian Kinerja" dataList={chartData.pk} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="LKJIP TW 1" dataList={chartData.lkjipTW1} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="LKJIP TW 2" dataList={chartData.lkjipTW2} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="LKJIP TW 3" dataList={chartData.lkjipTW3} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="LKJIP TW 4" dataList={chartData.lkjipTW4} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="Rapat Staff" dataList={chartData.rastaff} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="LHE AKIP" dataList={chartData.lhe} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="TL LHE AKIP" dataList={chartData.tlLheAkip} />
                        </Grid>
                        <Grid item xs={12} sm={6} md={4}>
                            <PieChartCard title="Monev Renaksi" dataList={chartData.monevRenaksi} />
                        </Grid>
                    </Grid>
                </CardContent>
            </Card>
            
        </AuthenticatedLayout>
    );
}