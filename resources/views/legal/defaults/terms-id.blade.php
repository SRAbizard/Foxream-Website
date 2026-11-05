<p>Syarat &amp; Ketentuan ini mengatur penggunaan website dan layanan {{ $company }} (“kami”). Dengan membuat akun atau menggunakan website ini, Anda dianggap telah membaca, memahami, dan menyetujui Syarat &amp; Ketentuan ini serta <a href="{{ route('legal.privacy', [], false) }}">Kebijakan Privasi</a> kami.</p>

<h2>1. Layanan kami</h2>
<p>Kami menyediakan informasi program kerja di Jepang (Tokutei Ginou, Internship/Magang, dan Engineer), lowongan dari perusahaan mitra, serta platform pendaftaran, pengelolaan dokumen, dan lamaran kerja. Pelatihan bahasa disediakan melalui platform terpisah, {{ \App\Support\Brand::learning() }}.</p>

<h2>2. Akun pengguna</h2>
<ul>
    <li>Data yang Anda isi wajib benar, lengkap, dan terbaru.</li>
    <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi dan seluruh aktivitas di akun Anda.</li>
    <li>Satu orang hanya boleh memiliki satu akun.</li>
</ul>

<h2>3. Lowongan dan seleksi</h2>
<ul>
    <li>Informasi lowongan berasal dari perusahaan mitra dan dapat berubah sewaktu-waktu.</li>
    <li>Keputusan penerimaan sepenuhnya berada di tangan perusahaan di Jepang.</li>
    <li>Kami membantu dan mendampingi proses seleksi, tetapi tidak menjamin kelulusan interview, penerbitan visa, maupun keberangkatan.</li>
</ul>

<h2>4. Biaya dan pembayaran</h2>
<ul>
    <li>Biaya resmi hanya yang tercantum di halaman <a href="{{ route('fees', [], false) }}">Biaya Program</a> atau dalam perjanjian tertulis yang Anda tanda tangani.</li>
    <li>Pembayaran resmi hanya ke rekening atas nama {{ $company }}, dan selalu disertai kuitansi resmi.</li>
    <li>Kami tidak bertanggung jawab atas pembayaran kepada pihak yang mengatasnamakan kami tanpa melalui kanal resmi.</li>
</ul>

<h2>5. Kewajiban pengguna</h2>
<p>Anda dilarang:</p>
<ul>
    <li>Mengisi data palsu atau mengunggah dokumen palsu maupun milik orang lain.</li>
    <li>Menggunakan website untuk tujuan yang melanggar hukum.</li>
    <li>Mengganggu keamanan atau kinerja website, termasuk mengambil data secara otomatis tanpa izin.</li>
</ul>
<p>Pemalsuan data atau dokumen dapat mengakibatkan pembatalan lamaran dan penutupan akun.</p>

<h2>6. Hak kekayaan intelektual</h2>
<p>Seluruh konten website ini, termasuk teks, logo, gambar, dan desain, adalah milik {{ $company }} atau pemberi lisensinya, dan tidak boleh digunakan tanpa izin tertulis.</p>

<h2>7. Batasan tanggung jawab</h2>
<p>Kami berupaya menyajikan informasi yang akurat, tetapi tidak menjamin website selalu bebas dari kesalahan atau gangguan. Kami tidak bertanggung jawab atas kerugian tidak langsung yang timbul dari penggunaan website ini, sepanjang diizinkan oleh hukum.</p>

<h2>8. Penangguhan dan penutupan akun</h2>
<p>Kami dapat menangguhkan atau menutup akun yang melanggar Syarat &amp; Ketentuan ini. Anda dapat meminta penutupan akun kapan saja dengan menghubungi kami.</p>

<h2>9. Hukum yang berlaku</h2>
<p>Syarat &amp; Ketentuan ini tunduk pada hukum Republik Indonesia. Perselisihan akan diselesaikan terlebih dahulu secara musyawarah.</p>

<h2>10. Perubahan</h2>
<p>Kami dapat memperbarui Syarat &amp; Ketentuan ini sewaktu-waktu. Versi terbaru selalu tersedia di halaman ini.</p>

<h2>11. Hubungi kami</h2>
<p>{{ $company }}<br>
@if($email)Email: <a href="mailto:{{ $email }}">{{ $email }}</a><br>@endif
@if($phone)Telepon/WhatsApp: {{ $phone }}<br>@endif
</p>
