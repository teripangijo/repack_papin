<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Variabel yang dikirim dari Controller:
// $user (array): Data user yang login
// $user_perusahaan (array): Data perusahaan terkait
// $list_barang_berkuota (array): Daftar barang yang memiliki kuota aktif untuk user ini
// $title (string)
// $subtitle (string)
// $this->session->flashdata('message_form_permohonan') (jika ada pesan spesifik untuk form ini)

// Inisialisasi untuk JavaScript jika ada value lama dari form_error atau set_value
$selected_id_kuota_barang_js = set_value('id_kuota_barang_selected', ''); // Ambil dari set_value jika ada
$selected_nama_barang_js = set_value('NamaBarang', '');
$prefill_skep = '';
$prefill_sisa_kuota = 0;

// Jika ada data $list_barang_berkuota dan ada barang yang sudah terpilih sebelumnya (misal karena validasi gagal)
if (!empty($selected_id_kuota_barang_js) && !empty($list_barang_berkuota)) {
    foreach ($list_barang_berkuota as $barang_opt) {
        if ($barang_opt['id_kuota_barang'] == $selected_id_kuota_barang_js) {
            $prefill_skep = $barang_opt['nomor_skep_asal'] ?? 'SKEP Tidak Ada';
            $prefill_sisa_kuota = $barang_opt['remaining_quota_barang'] ?? 0;
            // Pastikan nama barang juga konsisten jika id_kuota_barang yang jadi acuan utama
            $selected_nama_barang_js = $barang_opt['nama_barang'];
            break;
        }
    }
} elseif (empty($selected_id_kuota_barang_js) && !empty($selected_nama_barang_js) && !empty($list_barang_berkuota)) {
    // Fallback jika hanya NamaBarang yang di-set_value (misal callback validasi hanya berdasarkan nama)
    foreach ($list_barang_berkuota as $barang_opt) {
        if ($barang_opt['nama_barang'] == $selected_nama_barang_js) {
            // Ambil data dari barang pertama yang cocok namanya (bisa kurang akurat jika ada duplikasi nama barang dengan skep berbeda)
            $prefill_skep = $barang_opt['nomor_skep_asal'] ?? 'SKEP Tidak Ada';
            $prefill_sisa_kuota = $barang_opt['remaining_quota_barang'] ?? 0;
            $selected_id_kuota_barang_js = $barang_opt['id_kuota_barang']; // Set ID kuota barang juga
            break;
        }
    }
}


?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= isset($subtitle) ? htmlspecialchars($subtitle) : 'Form Permohonan Impor Kembali'; ?></h1>
        <a href="<?= site_url('user/daftarPermohonan'); // Ke daftar permohonan user ?>" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali ke Daftar Permohonan
        </a>
    </div>

    <?php
    // if ($this->session->flashdata('message')) { echo $this->session->flashdata('message'); }
    if ($this->session->flashdata('message_form_permohonan')) { echo $this->session->flashdata('message_form_permohonan'); }
    if (validation_errors()) { echo '<div class="alert alert-danger" role="alert">' . validation_errors() . '</div>';}
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Pengajuan Permohonan Impor Kembali</h6>
        </div>
        <div class="card-body">
            <?php if (isset($user['is_active']) && $user['is_active'] == 1 && !empty($user_perusahaan)) : ?>
                <?php echo form_open(site_url('user/permohonan_impor_kembali'), ['class' => 'needs-validation', 'novalidate' => '']); ?>

                <div class="alert alert-secondary small">
                    <strong>Data Perusahaan:</strong><br>
                    Nama: <?= htmlspecialchars($user_perusahaan['NamaPers'] ?? 'N/A'); ?><br>
                    NPWP: <?= htmlspecialchars($user_perusahaan['npwp'] ?? 'N/A'); ?>
                </div>
                <hr>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="nomorSurat">Nomor Surat Pengajuan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= (form_error('nomorSurat')) ? 'is-invalid' : ''; ?>" id="nomorSurat" name="nomorSurat" value="<?= set_value('nomorSurat'); ?>" required>
                        <?= form_error('nomorSurat', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="TglSurat">Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control gj-datepicker <?= (form_error('TglSurat')) ? 'is-invalid' : ''; ?>" id="TglSurat" name="TglSurat" placeholder="YYYY-MM-DD" value="<?= set_value('TglSurat', date('Y-m-d')); ?>" required>
                        <?= form_error('TglSurat', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="Perihal">Perihal Surat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= (form_error('Perihal')) ? 'is-invalid' : ''; ?>" id="Perihal" name="Perihal" value="<?= set_value('Perihal'); ?>" required>
                        <?= form_error('Perihal', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="id_kuota_barang_selected">Pilih Barang Berdasarkan Kuota <span class="text-danger">*</span></label>
                        <select class="form-control <?= (form_error('id_kuota_barang_selected') || form_error('NamaBarang')) ? 'is-invalid' : ''; ?>" id="id_kuota_barang_selected" name="id_kuota_barang_selected" required>
                            <option value="">-- Pilih Barang & Kuota SKEP --</option>
                            <?php if (!empty($list_barang_berkuota)): ?>
                                <?php foreach($list_barang_berkuota as $barang): ?>
                                    <option value="<?= htmlspecialchars($barang['id_kuota_barang']); ?>"
                                            data-nama_barang="<?= htmlspecialchars($barang['nama_barang']); ?>"
                                            data-sisa_kuota="<?= htmlspecialchars($barang['remaining_quota_barang'] ?? 0); ?>"
                                            data-skep="<?= htmlspecialchars($barang['nomor_skep_asal'] ?? ''); ?>"
                                            <?= set_select('id_kuota_barang_selected', $barang['id_kuota_barang']); ?>>
                                        <?= htmlspecialchars($barang['nama_barang']); ?> (Sisa: <?= number_format($barang['remaining_quota_barang'] ?? 0, 0, ',', '.'); ?> Unit - SKEP: <?= htmlspecialchars($barang['nomor_skep_asal'] ?? 'N/A'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?= form_error('id_kuota_barang_selected', '<small class="text-danger pl-1">', '</small>'); ?>
                        <?= form_error('NamaBarang', '<small class="text-danger pl-1">', '</small>'); // Jika masih ada validasi NamaBarang terpisah ?>
                        <small id="sisaKuotaInfo" class="form-text text-info"></small>
                        <input type="hidden" name="NamaBarang" id="NamaBarangHidden" value="<?= $selected_nama_barang_js; ?>"> </div>
                    <div class="form-group col-md-3">
                        <label for="JumlahBarang">Jumlah Barang Diajukan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control <?= (form_error('JumlahBarang')) ? 'is-invalid' : ''; ?>" id="JumlahBarang" name="JumlahBarang" value="<?= set_value('JumlahBarang'); ?>" required min="1" max="0">
                        <?= form_error('JumlahBarang', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="NoSkepOtomatis">No. SKEP (Dasar Permohonan)</label>
                        <input type="text" class="form-control" id="NoSkepOtomatis" name="NoSkepOtomatis" value="<?= set_value('NoSkepOtomatis', $prefill_skep); ?>" readonly title="No. SKEP otomatis terisi berdasarkan barang yang dipilih.">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="NegaraAsal">Negara Asal Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= (form_error('NegaraAsal')) ? 'is-invalid' : ''; ?>" id="NegaraAsal" name="NegaraAsal" value="<?= set_value('NegaraAsal'); ?>" required>
                        <?= form_error('NegaraAsal', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="NamaKapal">Nama Kapal / Sarana Pengangkut <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= (form_error('NamaKapal')) ? 'is-invalid' : ''; ?>" id="NamaKapal" name="NamaKapal" value="<?= set_value('NamaKapal'); ?>" required>
                        <?= form_error('NamaKapal', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="noVoyage">No. Voyage / Flight <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= (form_error('noVoyage')) ? 'is-invalid' : ''; ?>" id="noVoyage" name="noVoyage" value="<?= set_value('noVoyage'); ?>" required>
                        <?= form_error('noVoyage', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="TglKedatangan">Tanggal Perkiraan Kedatangan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control gj-datepicker <?= (form_error('TglKedatangan')) ? 'is-invalid' : ''; ?>" id="TglKedatangan" name="TglKedatangan" placeholder="YYYY-MM-DD" value="<?= set_value('TglKedatangan'); ?>" required>
                        <?= form_error('TglKedatangan', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="TglBongkar">Tanggal Perkiraan Bongkar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control gj-datepicker <?= (form_error('TglBongkar')) ? 'is-invalid' : ''; ?>" id="TglBongkar" name="TglBongkar" placeholder="YYYY-MM-DD" value="<?= set_value('TglBongkar'); ?>" required>
                        <?= form_error('TglBongkar', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="lokasi">Lokasi Bongkar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= (form_error('lokasi')) ? 'is-invalid' : ''; ?>" id="lokasi" name="lokasi" value="<?= set_value('lokasi'); ?>" required>
                        <?= form_error('lokasi', '<small class="text-danger pl-1">', '</small>'); ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-user btn-block mt-4" id="submitPermohonanBtn" <?= empty($list_barang_berkuota) ? 'disabled title="Tidak ada barang dengan kuota aktif untuk diajukan."' : ''; ?>>
                    <i class="fas fa-paper-plane fa-fw"></i> Ajukan Permohonan
                </button>
                <?php echo form_close(); ?>

            <?php else : ?>
                <div class="alert alert-warning" role="alert">
                    Akun Anda belum aktif atau data perusahaan belum lengkap. Silakan lengkapi <a href="<?= site_url('user/edit'); ?>" class="alert-link">profil perusahaan Anda</a> terlebih dahulu.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Inisialisasi Gijgo Datepicker
    if (typeof $ !== 'undefined' && typeof $.fn.datepicker !== 'undefined') {
        var datepickerConfig = { uiLibrary: 'bootstrap4', format: 'yyyy-mm-dd', showOnFocus: true, showRightIcon: true, autoClose: true };
        $('#TglSurat').datepicker(datepickerConfig);
        $('#TglKedatangan').datepicker(datepickerConfig);
        $('#TglBongkar').datepicker(datepickerConfig);
    }

    // Event handler untuk dropdown barang
    $('#id_kuota_barang_selected').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var sisaKuota = selectedOption.data('sisa_kuota') || 0;
        var skep = selectedOption.data('skep') || 'SKEP Tidak Tersedia';
        var namaBarang = selectedOption.data('nama_barang') || '';

        $('#sisaKuotaInfo').text('Sisa kuota untuk barang (' + namaBarang + ') ini: ' + parseInt(sisaKuota).toLocaleString() + ' Unit');
        $('#NoSkepOtomatis').val(skep);
        $('#JumlahBarang').attr('max', parseInt(sisaKuota)).val(''); // Set max dan reset jumlah barang
        $('#NamaBarangHidden').val(namaBarang); // Set hidden input untuk nama barang

        if(parseInt(sisaKuota) <= 0 || $(this).val() === "") {
            $('#submitPermohonanBtn').prop('disabled', true).attr('title', 'Tidak ada kuota tersedia untuk barang ini atau barang belum dipilih.');
            $('#JumlahBarang').val(0).prop('readonly', true);
        } else {
            $('#submitPermohonanBtn').prop('disabled', false).attr('title', '');
            $('#JumlahBarang').prop('readonly', false);
        }
    }).trigger('change'); // Trigger saat halaman load untuk inisialisasi jika ada set_value

    // Validasi jumlah barang tidak melebihi sisa kuota (client-side)
    $('#JumlahBarang').on('input', function() {
        var jumlahDimohon = parseInt($(this).val()) || 0;
        var sisaKuota = parseInt($(this).attr('max')) || 0; // Ambil dari max attribute
        var idKuotaBarang = $('#id_kuota_barang_selected').val();

        if (jumlahDimohon > sisaKuota && idKuotaBarang !== "") { // Hanya validasi jika barang sudah dipilih
            $(this).val(sisaKuota);
            // Notifikasi sederhana, bisa diganti dengan toast atau alert yang lebih baik
            $('#sisaKuotaInfo').append(' <strong class="text-danger">(Jumlah melebihi sisa!)</strong>');
            setTimeout(function(){
                 $('#sisaKuotaInfo .text-danger').remove();
            }, 3000);
        } else {
            $('#sisaKuotaInfo .text-danger').remove();
        }

        if (jumlahDimohon <= 0 && idKuotaBarang !== "") {
             $('#submitPermohonanBtn').prop('disabled', true).attr('title', 'Jumlah barang harus lebih dari 0.');
        } else if (sisaKuota > 0 && jumlahDimohon > 0 && jumlahDimohon <= sisaKuota && idKuotaBarang !== "") {
             $('#submitPermohonanBtn').prop('disabled', false).attr('title', '');
        } else if (idKuotaBarang === "") { // Jika belum ada barang dipilih
            $('#submitPermohonanBtn').prop('disabled', true).attr('title', 'Pilih barang berkuota terlebih dahulu.');
        }
    });
});
</script>