<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= isset($subtitle) ? htmlspecialchars($subtitle) : 'Daftar Permohonan Impor Kembali'; ?></h1>
        <a href="<?= site_url('user/ajukanPermohonan'); // Pastikan ini link ke method yang benar untuk form pengajuan baru ?>" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Ajukan Permohonan Baru
        </a>
    </div>

    <?php
    // Pesan flashdata akan ditampilkan oleh templates/topbar.php jika sudah di-setup di sana
    // Jika belum, Anda bisa uncomment ini:
    // if ($this->session->flashdata('message')) {
    //     echo $this->session->flashdata('message');
    // }
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Permohonan Anda</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTablePermohonanUser" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Id Aju</th>
                            <th>No Surat Anda</th>
                            <th>Tgl Surat</th>
                            <th>Nama Perusahaan</th>
                            <th>Jenis Barang</th> <th>Waktu Submit</th>
                            <th>Petugas Pemeriksa</th>
                            <th>Jumlah Disetujui</th> <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($permohonan) && is_array($permohonan)) : ?>
                            <?php $no = 1; ?>
                            <?php foreach ($permohonan as $p) : ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= isset($p['id']) ? htmlspecialchars($p['id']) : '-'; ?></td>
                                    <td><?= isset($p['nomorSurat']) ? htmlspecialchars($p['nomorSurat']) : '-'; ?></td>
                                    <td><?= isset($p['TglSurat']) && $p['TglSurat'] != '0000-00-00' ? date('d/m/Y', strtotime($p['TglSurat'])) : '-'; ?></td>
                                    <td><?= isset($p['NamaPers']) ? htmlspecialchars($p['NamaPers']) : '-'; // Asumsi NamaPers ada di $p (mungkin perlu join jika tidak ada di user_permohonan) ?></td>
                                    <td><?= isset($p['NamaBarang']) ? htmlspecialchars($p['NamaBarang']) : '-'; ?></td> <td><?= isset($p['time_stamp']) && $p['time_stamp'] != '0000-00-00 00:00:00' ? date('d/m/Y H:i:s', strtotime($p['time_stamp'])) : '-'; ?></td>
                                    <td>
                                        <?php
                                        // Logika untuk nama petugas pemeriksa (sudah ada di view Anda, pastikan $p['nama_petugas_pemeriksa'] tersedia dari controller)
                                        if (isset($p['status']) && in_array($p['status'], ['1', '2', '3', '4']) && isset($p['nama_petugas_pemeriksa']) && !empty($p['nama_petugas_pemeriksa'])) {
                                            echo htmlspecialchars($p['nama_petugas_pemeriksa']);
                                        } elseif (isset($p['status']) && in_array($p['status'], ['1', '2']) && empty($p['nama_petugas_pemeriksa']) && !empty($p['petugas'])) {
                                            // Jika $p['petugas'] adalah ID, mungkin perlu query lagi atau teks generik
                                            echo 'Petugas Telah Ditunjuk';
                                        } elseif (isset($p['status']) && ($p['status'] == '0' || $p['status'] == '5')) {
                                            echo '<i>Menunggu Penunjukan</i>';
                                        }
                                        else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Tampilkan jumlah yang disetujui hanya jika status 'Disetujui' dan ada datanya
                                        if (isset($p['status']) && $p['status'] == '3' && isset($p['JumlahBenar'])) {
                                            echo htmlspecialchars($p['JumlahBenar']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td> <td>
                                        <?php
                                        $status_text_user = '-';
                                        $status_badge_user = 'secondary';
                                        if (isset($p['status'])) {
                                            switch ($p['status']) {
                                                case '0':
                                                    $status_text_user = 'Diajukan (Menunggu Verifikasi)';
                                                    $status_badge_user = 'info';
                                                    break;
                                                case '5':
                                                    $status_text_user = 'Diproses Kantor'; // Sebelumnya "Sedang Diproses Kantor"
                                                    $status_badge_user = 'info';
                                                    break;
                                                case '1':
                                                    $status_text_user = 'Pemeriksaan Petugas'; // Sebelumnya "Dalam Pemeriksaan Petugas"
                                                    $status_badge_user = 'primary';
                                                    break;
                                                case '2':
                                                    $status_text_user = 'Menunggu Keputusan'; // Sebelumnya "Menunggu Keputusan Akhir"
                                                    $status_badge_user = 'warning';
                                                    break;
                                                case '3':
                                                    $status_text_user = 'Disetujui';
                                                    $status_badge_user = 'success';
                                                    break;
                                                case '4':
                                                    $status_text_user = 'Ditolak';
                                                    $status_badge_user = 'danger';
                                                    break;
                                                default:
                                                    $status_text_user = 'Status Tidak Dikenal (' . htmlspecialchars($p['status']) . ')';
                                                    $status_badge_user = 'dark';
                                            }
                                        }
                                        echo '<span class="badge badge-pill badge-' . $status_badge_user . '">' . htmlspecialchars($status_text_user) . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('user/detailPermohonan/' . (isset($p['id']) ? $p['id'] : '#')); ?>" class="btn btn-info btn-circle btn-sm my-1" title="Lihat Detail Permohonan">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= site_url('user/printPdf/' . (isset($p['id']) ? $p['id'] : '#')); ?>" class="btn btn-secondary btn-circle btn-sm my-1" title="Cetak PDF Permohonan" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <?php if (isset($p['status']) && ($p['status'] == '0' || $p['status'] == '5' || $p['status'] == '4')) : // Bisa edit jika Diajukan, Diproses Kantor, atau Ditolak (untuk pengajuan ulang) ?>
                                            <a href="<?= site_url('user/editPermohonan/' . (isset($p['id']) ? $p['id'] : '#')); // Pastikan method editPermohonan ada ?>" class="btn btn-warning btn-circle btn-sm my-1" title="Edit Permohonan">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (isset($p['status']) && $p['status'] == '3' && !empty($p['link'])) : // Tombol lihat surat persetujuan jika disetujui dan ada link ?>
                                            <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank" class="btn btn-success btn-circle btn-sm my-1" title="Lihat Surat Persetujuan">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
$(document).ready(function() {
    if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        $('#dataTablePermohonanUser').DataTable({
            "order": [[ 6, "desc" ]], // Urutkan berdasarkan Waktu Submit terbaru (indeks kolom ke-6, sesuaikan jika urutan kolom berubah)
            "language": {
                "emptyTable": "Anda belum memiliki data permohonan.",
                "zeroRecords": "Tidak ada data yang cocok ditemukan",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "infoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                "lengthMenu": "Tampilkan _MENU_ entri",
                "search": "Cari:",
                "paginate": {
                    "first":    "Pertama",
                    "last":     "Terakhir",
                    "next":     "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            },
            "columnDefs": [
                // Sesuaikan indeks target jika jumlah kolom berubah
                // Kolom '#' (indeks 0) dan Action (indeks 10) tidak bisa diurutkan
                { "orderable": false, "targets": [0, 10] }
            ]
        });
    } else {
        console.error("DataTables plugin is not loaded for 'dataTablePermohonanUser'.");
    }
});
</script>