
</html>


{{-- <footer>
    <center><p>Powered by Kejaksaan @2024</p></center>
</footer> --}}
<footer class="footer">
      <p><b>Panev BiroCana Kejaksaan RI</b> @2025</p>
    </footer>
<!-- Bootstrap JS and Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/custom.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pilih semua elemen dengan class 'card'
        const cards = document.querySelectorAll('.card');

        // Tambahkan class 'show' untuk memulai animasi slide up
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('show');
            }, index * 100); // Animasi akan muncul satu per satu dengan delay 100ms
        });
    });
</script>
<style>
    /* Reset dasar */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body {
  height: 100%;
}

/* Wrapper utama */
.wrapper {
  min-height: 50vh; /* Tinggi minimal seluruh viewport */
  display: flex;
  flex-direction: column;
}

/* Konten utama akan memenuhi ruang tersisa */
.content {
  flex: 1;
  padding: 7px;
}

/* Footer */
.footer {
  background-color: #dbdbdb;
  color: rgb(0, 0, 0);
  text-align: center;
  padding: 7px 0;
}

</style>