<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Petugas extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->library('upload');
        $this->load->library('session');
        $this->load->helper(array('url', 'form', 'download')); // Tambahkan download jika perlu
        if (!isset($this->db)) {
             $this->load->database();
        }
        // Asumsikan Anda memiliki model ini
        // $this->load->model('Permohonan_model');
        // $this->load->model('Lhp_model');

        $excluded_methods = ['force_change_password_page', 'edit_profil', 'logout'];
        $current_method = $this->router->fetch_method();

        if (!in_array($current_method, $excluded_methods)) {
            $this->_check_auth_petugas();
        } elseif (!$this->session->userdata('email') && $current_method != 'logout' && !($current_method == 'force_change_password_page' && $this->session->flashdata('message')) ) {
             $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Sesi tidak valid atau telah berakhir. Silakan login kembali.</div>');
             redirect('auth');
             exit;
        }
    }

    private function _check_auth_petugas()
    {
        if (!$this->session->userdata('email')) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Please login to continue.</div>');
            redirect('auth');
            exit;
        }

        if ($this->session->userdata('force_change_password') == 1 &&
            $this->router->fetch_method() != 'force_change_password_page' &&
            $this->router->fetch_method() != 'logout') {

            if ($this->session->userdata('role_id') == 3) {
                 $this->session->set_flashdata('message', '<div class="alert alert-warning" role="alert">Untuk keamanan, Anda wajib mengganti password Anda terlebih dahulu.</div>');
                 redirect('petugas/force_change_password_page');
                 exit;
            }
        }

        if ($this->session->userdata('role_id') != 3) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Access Denied! Anda tidak diotorisasi untuk mengakses area Petugas.</div>');
            $role_id_session = $this->session->userdata('role_id');
            if ($role_id_session == 1) redirect('admin');
            elseif ($role_id_session == 2) redirect('user');
            elseif ($role_id_session == 4) redirect('monitoring');
            else redirect('auth/blocked');
            exit;
        }
        if ($this->session->userdata('is_active') == 0) {
           $this->session->set_flashdata('message', '<div class="alert alert-warning" role="alert">Akun Petugas Anda tidak aktif. Hubungi Administrator.</div>');
           $current_controller = $this->router->fetch_class();
           $current_method = $this->router->fetch_method();
            if (!($current_controller == 'auth' || ($current_controller == 'petugas' && $current_method == 'logout'))) {
                 redirect('auth/logout');
                 exit;
            }
        }
    }

    public function index()
    {
        $data['title'] = 'Returnable Package';
        $data['subtitle'] = 'Dashboard Petugas';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $petugas_user_id = $data['user']['id']; // ID dari tabel user

        // Ambil ID petugas dari tabel 'petugas' berdasarkan 'id_user'
        $petugas_detail = $this->db->get_where('petugas', ['id_user' => $petugas_user_id])->row_array();
        $petugas_id_in_petugas_table = $petugas_detail ? $petugas_detail['id'] : null;


        // Jumlah tugas LHP yang menunggu (status '1' dan ditugaskan ke petugas.id dari tabel petugas)
        if ($petugas_id_in_petugas_table) {
            $this->db->where('petugas', $petugas_id_in_petugas_table); // 'petugas' di user_permohonan merujuk ke 'petugas.id'
            $this->db->where('status', '1');
            $data['jumlah_tugas_lhp'] = $this->db->count_all_results('user_permohonan');
        } else {
            $data['jumlah_tugas_lhp'] = 0;
        }


        // Jumlah LHP yang sudah direkam oleh petugas ini (id_petugas_pemeriksa di lhp merujuk ke user.id)
        $this->db->where('id_petugas_pemeriksa', $petugas_user_id);
        $data['jumlah_lhp_selesai'] = $this->db->count_all_results('lhp');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('petugas/dashboard_petugas_view', $data);
        $this->load->view('templates/footer');
    }

    public function daftar_pemeriksaan()
    {
        $data['title'] = 'Returnable Package';
        $data['subtitle'] = 'Daftar Pemeriksaan Ditugaskan';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $petugas_user_id = $data['user']['id'];

        // Ambil ID petugas dari tabel 'petugas' berdasarkan 'id_user'
        $petugas_detail = $this->db->get_where('petugas', ['id_user' => $petugas_user_id])->row_array();
        $petugas_id_in_petugas_table = $petugas_detail ? $petugas_detail['id'] : null;

        if (!$petugas_id_in_petugas_table) {
            $this->session->set_flashdata('message', '<div class="alert alert-warning" role="alert">Data detail petugas tidak ditemukan. Tidak dapat menampilkan tugas.</div>');
            $data['daftar_tugas'] = [];
        } else {
            $this->db->select('up.*, upr.NamaPers, u_pemohon.name as nama_pemohon');
            $this->db->from('user_permohonan up');
            $this->db->join('user_perusahaan upr', 'up.id_pers = upr.id_pers', 'left');
            $this->db->join('user u_pemohon', 'upr.id_pers = u_pemohon.id', 'left');
            $this->db->where('up.petugas', $petugas_id_in_petugas_table); // 'petugas' di user_permohonan merujuk ke 'petugas.id'
            $this->db->where('up.status', '1');
            $this->db->order_by('up.TglSuratTugas DESC, up.WaktuPenunjukanPetugas DESC');
            $data['daftar_tugas'] = $this->db->get()->result_array();
        }

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('petugas/daftar_pemeriksaan_view', $data);
        $this->load->view('templates/footer');
    }

    public function rekam_lhp($id_permohonan = 0)
    {
        if ($id_permohonan == 0 || !is_numeric($id_permohonan)) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">ID Permohonan tidak valid.</div>');
            redirect('petugas/daftar_pemeriksaan');
            return;
        }

        $data['title'] = 'Returnable Package';
        $data['subtitle'] = 'Perekaman Laporan Hasil Pemeriksaan (LHP) - ID Aju: ' . htmlspecialchars($id_permohonan);
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $petugas_user_id = $data['user']['id'];

        $petugas_detail_db = $this->db->get_where('petugas', ['id_user' => $petugas_user_id])->row_array();
        $petugas_id_for_permohonan = $petugas_detail_db ? $petugas_detail_db['id'] : null;

        // Ambil data permohonan, pastikan SELECT semua kolom yang dibutuhkan termasuk jumlah diajukan
        // 'up.*' akan mengambil semua kolom dari user_permohonan, termasuk 'JumlahBarang'
        $this->db->select('up.*, upr.NamaPers, upr.npwp, u_pemohon.name as nama_pemohon');
        $this->db->from('user_permohonan up');
        $this->db->join('user_perusahaan upr', 'up.id_pers = upr.id_pers', 'left');
        $this->db->join('user u_pemohon', 'upr.id_pers = u_pemohon.id', 'left');
        $this->db->where('up.id', $id_permohonan);

        if ($petugas_id_for_permohonan) {
            $this->db->where('up.petugas', $petugas_id_for_permohonan);
        } else {
             $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Detail petugas tidak valid atau tidak ditemukan. Tidak dapat memverifikasi tugas.</div>');
             redirect('petugas/daftar_pemeriksaan');
             return;
        }
        $this->db->where('up.status', '1'); // Hanya status 'Penunjukan Pemeriksa'
        $permohonan = $this->db->get()->row_array();

        if (!$permohonan) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Permohonan tidak ditemukan, tidak ditugaskan kepada Anda, atau status tidak sesuai untuk perekaman LHP.</div>');
            redirect('petugas/daftar_pemeriksaan');
            return;
        }
        // Logging untuk memastikan data permohonan dan kolom jumlah terambil
        log_message('debug', 'Petugas Rekam LHP - Data Permohonan: ' . print_r($permohonan, true));
        $data['permohonan'] = $permohonan;


        $existing_lhp = $this->db->get_where('lhp', ['id_permohonan' => $id_permohonan])->row_array();
        if ($existing_lhp && $this->input->method() !== 'post') {
             // $this->session->set_flashdata('message_transient', '<div class="alert alert-info" role="alert">LHP sudah ada, Anda dapat mengeditnya.</div>');
        }
        $data['lhp_data'] = $existing_lhp;


        // Validasi form LHP
        $this->form_validation->set_rules('NoLHP', 'Nomor LHP', 'trim|required');
        $this->form_validation->set_rules('TglLHP', 'Tanggal LHP', 'trim|required');
        $this->form_validation->set_rules('JumlahBenar', 'Jumlah Disetujui (LHP)', 'trim|required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('JumlahSalah', 'Jumlah Ditolak (LHP)', 'trim|required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('Catatan', 'Catatan LHP', 'trim');

        if (!$existing_lhp && empty($_FILES['FileLHP']['name'])) {
            $this->form_validation->set_rules('FileLHP', 'File LHP Resmi', 'required');
        }


        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('petugas/form_rekam_lhp_view', $data);
            $this->load->view('templates/footer');
        } else {
            // Ambil JumlahAju dari data permohonan yang sudah di-load
            // ** PERBAIKAN DI SINI: Gunakan 'JumlahBarang' **
            $jumlah_diajukan_pemohon = $permohonan['JumlahBarang'] ?? 0;
            if (!isset($permohonan['JumlahBarang'])) {
                log_message('error', 'Petugas Rekam LHP - Kolom "JumlahBarang" tidak ditemukan dalam data permohonan untuk ID: ' . $id_permohonan);
                // Anda mungkin ingin menghentikan proses atau memberikan nilai default yang aman jika kolom tidak ada
            } else if ($jumlah_diajukan_pemohon == 0) {
                log_message('warning', 'Petugas Rekam LHP - Jumlah diajukan dari permohonan (JumlahBarang) adalah 0 untuk ID: ' . $id_permohonan);
            }

            $data_lhp_to_save = [
                'id_permohonan'         => $id_permohonan,
                'id_petugas_pemeriksa'  => $petugas_user_id,
                'NoLHP'                 => $this->input->post('NoLHP'),
                'TglLHP'                => $this->input->post('TglLHP'),
                'JumlahAju'             => (int)$jumlah_diajukan_pemohon, // ** Sudah menggunakan nilai yang benar **
                'JumlahBenar'           => (int)$this->input->post('JumlahBenar'),
                'JumlahSalah'           => (int)$this->input->post('JumlahSalah'),
                'Catatan'               => $this->input->post('Catatan'),
            ];
            if (!$existing_lhp) {
                $data_lhp_to_save['submit_time'] = date('Y-m-d H:i:s');
            }


            // Proses Upload File LHP (dokumen resmi)
            $nama_file_lhp_resmi = $existing_lhp['FileLHP'] ?? null;
            if (isset($_FILES['FileLHP']) && $_FILES['FileLHP']['error'] != UPLOAD_ERR_NO_FILE) {
                $upload_dir_lhp = './uploads/lhp/';
                if (!is_dir($upload_dir_lhp)) { @mkdir($upload_dir_lhp, 0777, true); }

                $config_lhp['upload_path']   = $upload_dir_lhp;
                $config_lhp['allowed_types'] = 'pdf|doc|docx|jpg|jpeg|png';
                $config_lhp['max_size']      = '2048';
                $config_lhp['encrypt_name']  = TRUE;
                $this->upload->initialize($config_lhp, TRUE);

                if ($this->upload->do_upload('FileLHP')) {
                    if ($existing_lhp && !empty($existing_lhp['FileLHP']) && file_exists($upload_dir_lhp . $existing_lhp['FileLHP'])) {
                        @unlink($upload_dir_lhp . $existing_lhp['FileLHP']);
                    }
                    $nama_file_lhp_resmi = $this->upload->data('file_name');
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Upload File LHP Resmi Gagal: ' . $this->upload->display_errors('', '') . '</div>');
                    redirect('petugas/rekam_lhp/' . $id_permohonan);
                    return;
                }
            }
            $data_lhp_to_save['FileLHP'] = $nama_file_lhp_resmi;


            // Proses Upload File Dokumentasi Foto
            $nama_file_doc_foto = $existing_lhp['file_dokumentasi_foto'] ?? null;
            if (isset($_FILES['file_dokumentasi_foto']) && $_FILES['file_dokumentasi_foto']['error'] != UPLOAD_ERR_NO_FILE) {
                $upload_dir_doc_foto = './uploads/dokumentasi_lhp/';
                if (!is_dir($upload_dir_doc_foto)) { @mkdir($upload_dir_doc_foto, 0777, true); }

                $config_doc_foto['upload_path']   = $upload_dir_doc_foto;
                $config_doc_foto['allowed_types'] = 'jpg|jpeg|png|gif';
                $config_doc_foto['max_size']      = '2048';
                $config_doc_foto['encrypt_name']  = TRUE;
                $this->upload->initialize($config_doc_foto, TRUE);

                if ($this->upload->do_upload('file_dokumentasi_foto')) {
                    if ($existing_lhp && !empty($existing_lhp['file_dokumentasi_foto']) && file_exists($upload_dir_doc_foto . $existing_lhp['file_dokumentasi_foto'])) {
                        @unlink($upload_dir_doc_foto . $existing_lhp['file_dokumentasi_foto']);
                    }
                    $nama_file_doc_foto = $this->upload->data('file_name');
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Upload File Dokumentasi Foto Gagal: ' . $this->upload->display_errors('', '') . '</div>');
                    redirect('petugas/rekam_lhp/' . $id_permohonan);
                    return;
                }
            }
            $data_lhp_to_save['file_dokumentasi_foto'] = $nama_file_doc_foto;


            $lhp_processed_id = null;
            if ($existing_lhp) {
                $this->db->where('id', $existing_lhp['id']);
                $this->db->update('lhp', $data_lhp_to_save);
                $lhp_processed_id = $existing_lhp['id'];
                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">LHP berhasil diperbarui!</div>');
            } else {
                $this->db->insert('lhp', $data_lhp_to_save);
                $lhp_processed_id = $this->db->insert_id();
                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">LHP berhasil direkam!</div>');
            }


            if ($lhp_processed_id) {
                $this->db->where('id', $id_permohonan);
                $this->db->update('user_permohonan', ['status' => '2']);
                log_message('info', 'Status permohonan ID ' . $id_permohonan . ' diubah menjadi LHP Direkam (2). LHP ID: ' . $lhp_processed_id);
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Gagal menyimpan data LHP ke database. Silakan coba lagi.</div>');
            }
            redirect('petugas/daftar_pemeriksaan');
        }
    }

    public function force_change_password_page()
    {
        if (!$this->session->userdata('email') ||
            $this->session->userdata('force_change_password') != 1 ||
            $this->session->userdata('role_id') != 3) {
            if ($this->session->userdata('role_id') == 3) {
                redirect('petugas/index');
            } else {
                redirect('auth/logout');
            }
            return;
        }
        $data['title'] = 'Returnable Package';
        $data['subtitle'] = 'Wajib Ganti Password (Petugas)';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $this->form_validation->set_rules('new_password', 'Password Baru', 'required|trim|min_length[6]|matches[confirm_new_password]', ['min_length' => 'Password minimal 6 karakter.', 'matches'    => 'Konfirmasi password tidak cocok.']);
        $this->form_validation->set_rules('confirm_new_password', 'Konfirmasi Password Baru', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('petugas/form_force_change_password', $data);
            $this->load->view('templates/footer');
        } else {
            $new_password_hash = password_hash($this->input->post('new_password'), PASSWORD_DEFAULT);
            $user_id = $data['user']['id'];
            $update_data_db = ['password' => $new_password_hash, 'force_change_password' => 0];
            $this->db->where('id', $user_id);
            $this->db->update('user', $update_data_db);
            $this->session->set_userdata('force_change_password', 0);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password Anda telah berhasil diubah. Selamat datang di dashboard Anda.</div>');
            redirect('petugas/index');
        }
    }

    public function edit_profil()
    {
        $this->_check_auth_petugas();
        $data['title'] = 'Returnable Package';
        $data['subtitle'] = 'Edit Profil Saya';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $user_id = $data['user']['id'];
        if ($this->db->field_exists('id_user', 'petugas')) {
            $data['petugas_detail'] = $this->db->get_where('petugas', ['id_user' => $user_id])->row_array();
        } else {
            $data['petugas_detail'] = null;
            log_message('error', 'Kolom id_user tidak ditemukan di tabel petugas untuk edit_profil Petugas.');
        }
        if ($this->input->method() === 'post') {
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] != UPLOAD_ERR_NO_FILE) {
                $upload_dir_profile = 'uploads/profile_images/';
                $upload_path_profile = FCPATH . $upload_dir_profile;
                if (!is_dir($upload_path_profile)) {
                    if (!@mkdir($upload_path_profile, 0777, true)) {
                        $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Gagal membuat direktori upload foto profil.</div>');
                        redirect('petugas/edit_profil'); return;
                    }
                }
                if (!is_writable($upload_path_profile)) {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Upload error: Direktori foto profil tidak writable.</div>');
                    redirect('petugas/edit_profil'); return;
                }
                $config_profile['upload_path']   = $upload_path_profile;
                $config_profile['allowed_types'] = 'jpg|png|jpeg|gif';
                $config_profile['max_size']      = '2048';
                $config_profile['max_width']     = '1024';
                $config_profile['max_height']    = '1024';
                $config_profile['encrypt_name']  = TRUE;
                $this->upload->initialize($config_profile, TRUE);
                if ($this->upload->do_upload('profile_image')) {
                    $old_image = $data['user']['image'];
                    if ($old_image != 'default.jpg' && !empty($old_image) && file_exists($upload_path_profile . $old_image)) {
                        @unlink($upload_path_profile . $old_image);
                    }
                    $new_image_name = $this->upload->data('file_name');
                    $this->db->where('id', $user_id);
                    $this->db->update('user', ['image' => $new_image_name]);
                    $this->session->set_userdata('user_image', $new_image_name);
                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Foto profil berhasil diupdate.</div>');
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Upload Foto Profil Gagal: ' . $this->upload->display_errors('', '') . '</div>');
                }
                redirect('petugas/edit_profil'); return;
            }
        }
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('petugas/form_edit_profil_petugas', $data);
        $this->load->view('templates/footer');
    }

    public function riwayat_lhp_direkam()
    {
        $data['title'] = 'Returnable Package';
        $data['subtitle'] = 'Riwayat LHP yang Telah Direkam';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $petugas_user_id = $data['user']['id']; // Ini adalah user.id dari petugas yang login

        // Ambil semua LHP yang direkam oleh petugas ini
        // Kita akan join dengan user_permohonan dan user_perusahaan untuk info tambahan
        $this->db->select(
            'lhp.*, '.
            'up.nomorSurat as nomor_surat_permohonan, '.
            'up.TglSurat as tanggal_surat_permohonan, '.
            'upr.NamaPers as nama_perusahaan_pemohon, '.
            'up.status as status_permohonan_terkini, '.
            'up.NamaBarang as nama_barang_permohonan' // Ambil nama barang dari permohonan
        );
        $this->db->from('lhp');
        $this->db->join('user_permohonan up', 'lhp.id_permohonan = up.id', 'left');
        $this->db->join('user_perusahaan upr', 'up.id_pers = upr.id_pers', 'left');
        $this->db->where('lhp.id_petugas_pemeriksa', $petugas_user_id); // Hanya LHP dari petugas ini
        $this->db->order_by('lhp.submit_time', 'DESC'); // Tampilkan yang terbaru dulu
        $data['riwayat_lhp'] = $this->db->get()->result_array();

        // Logging untuk debug
        log_message('debug', 'PETUGAS RIWAYAT LHP - User ID: ' . $petugas_user_id);
        log_message('debug', 'PETUGAS RIWAYAT LHP - Query: ' . $this->db->last_query());
        log_message('debug', 'PETUGAS RIWAYAT LHP - Jumlah Data: ' . count($data['riwayat_lhp']));

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('petugas/riwayat_lhp_direkam_view', $data); // Buat view baru ini
        $this->load->view('templates/footer', $data);
    }

    public function detail_lhp_direkam($id_lhp = 0)
    {
        if ($id_lhp == 0 || !is_numeric($id_lhp)) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">ID LHP tidak valid.</div>');
            redirect('petugas/riwayat_lhp_direkam'); // Arahkan kembali ke daftar riwayat LHP
            return;
        }

        $data['title'] = 'Returnable Package';
        $data['subtitle'] = 'Detail LHP Direkam (ID LHP: '.htmlspecialchars($id_lhp).')';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $petugas_user_id = $data['user']['id']; // ID user dari petugas yang login

        // Ambil detail LHP spesifik
        // Pastikan hanya LHP yang direkam oleh petugas yang login yang bisa diakses
        // Join dengan user_permohonan dan user_perusahaan untuk mendapatkan info terkait
        $this->db->select(
            'lhp.*, '.
            'up.id as id_permohonan_ajuan, up.nomorSurat as nomor_surat_permohonan, up.TglSurat as tanggal_surat_pemohon, '.
            'up.NamaBarang as nama_barang_di_permohonan, up.JumlahBarang as jumlah_barang_di_permohonan, '.
            'upr.NamaPers as nama_perusahaan_pemohon, upr.npwp as npwp_perusahaan'
        );
        $this->db->from('lhp'); // Asumsi PK tabel lhp adalah 'id' atau 'id_lhp'
        $this->db->join('user_permohonan up', 'lhp.id_permohonan = up.id', 'left');
        $this->db->join('user_perusahaan upr', 'up.id_pers = upr.id_pers', 'left');
        $this->db->where('lhp.id', $id_lhp); // Ganti 'lhp.id' dengan 'lhp.id_lhp' jika PK Anda adalah id_lhp
        $this->db->where('lhp.id_petugas_pemeriksa', $petugas_user_id); // Keamanan: Hanya LHP miliknya
        $data['lhp_detail'] = $this->db->get()->row_array();

        if (!$data['lhp_detail']) {
            $this->session->set_flashdata('message', '<div class="alert alert-warning" role="alert">Detail LHP tidak ditemukan atau Anda tidak memiliki akses untuk melihatnya.</div>');
            redirect('petugas/riwayat_lhp_direkam');
            return;
        }

        // Anda bisa juga mengambil data permohonan terkait secara terpisah jika perlu lebih banyak field
        // $data['permohonan_terkait'] = $this->db->get_where('user_permohonan', ['id' => $data['lhp_detail']['id_permohonan']])->row_array();

        log_message('debug', 'PETUGAS DETAIL LHP - Data LHP: ' . print_r($data['lhp_detail'], true));

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('petugas/detail_lhp_view', $data); // Anda perlu membuat view ini
        $this->load->view('templates/footer', $data);
    }
}