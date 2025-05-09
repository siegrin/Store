<?php
require "app/controllers/ProductsController.php";

$id = $_GET["id"];
$categories = query("SELECT DISTINCT c.* 
                     FROM categories c 
                     JOIN subcategories s ON c.id = s.category_id 
                     ORDER BY c.id");
$subcategories = query("SELECT * FROM subcategories ORDER BY id");
$occasions = query("SELECT * FROM occasions ORDER BY id");
$products = query("SELECT * FROM products WHERE id =$id")[0];

if (isset($_POST["submit"])) {
  //ambil data dari tap elemen dalam form
  //cek apakah data berhasi di tambahkan atau tidak
  if (Edit($_POST) > 0) {
    echo "<script>
          alert('data berhasil diedit!');
          document.location.href = 'index.php?page=products';
          </script>";
  } else {
    echo "<script>
          alert('data gagal diedit!');
          document.location.href = 'index.php?page=products';
          </script>";
  }

}


?>

<!-- ===== FORM SECTION ===== -->
<div class="form__container">
  <form action="" method="post" enctype="multipart/form-data" class="form" id="contact-form">
    <input type="hidden" name="id" id="id" required value="<?= $products["id"]; ?>">
    <input type="hidden" name="gambarLama" value="<?= $products["image"]; ?>">
    <input type="text" id="name" name="name" required placeholder="Input Product Name" class="form__input"
      value="<?= $products["name"]; ?>">

    <div class="form__group">
      <!-- Pilihan Kategori -->
      <select name="category" id="categorySelect" class="form__input">
        <option value="" disabled selected>Pilih Kategori</option>
        <?php foreach ($categories as $row): ?>
          <option value="<?= $row["id"]; ?>" <?= ($row["id"] == $products["category_id"]) ? "selected" : ""; ?>>
            <?= $row["name"]; ?>
          <?php endforeach; ?>
      </select>

      <!-- Pilihan Subkategori -->
      <select name="subcategory" id="subcategorySelect" class="form__input">
        <option value="" disabled selected>Pilih Subkategori</option>
      </select>

      <!-- Pilihan Occasion -->
      <select name="occasion" class="form__input">
        <option value="" disabled selected>Pilih Occasion</option>
        <?php foreach ($occasions as $row): ?>
          <option value="<?= $row["id"]; ?>" <?= ($row["id"] == $products["occasion_id"]) ? "selected" : ""; ?>>
            <?= $row["name"]; ?>
          <?php endforeach; ?>
      </select>

      <input type="text" id="price" name="price" value="<?= formatPrice($products["price"]); ?>"
        placeholder="Input Price Product" class="form__input">
    </div>
    <textarea name="description" required placeholder="Enter Description Product"
      class="form__input"><?= str_replace(['<br>', '<br/>', '<br />'], '', $products["description"]); ?></textarea>

    <!-- Input File -->
    <input type="file" name="image" id="image" accept="image/*" class="form__input" value="">

    <!-- Preview Gambar -->
    <div class="image-preview">
      <img src="../uploads/<?= $products["image"]; ?>" id="preview">
    </div>

    <!-- Input Hidden untuk menyimpan hasil crop -->
    <input type="hidden" name="cropped_image" id="croppedImage">

    <p class="contact__message" id="contact-message"></p>

    <!-- Tombol Submit -->
    <button type="submit" name="submit" class="form__button">
      Add Category
    </button>
  </form>
</div>

<!-- JavaScript untuk Menampilkan Subkategori -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<script>
  // Ambil data subkategori dari PHP
  const subcategories = <?= json_encode($subcategories); ?>;

  // Ambil elemen select
  const categorySelect = document.getElementById("categorySelect");
  const subcategorySelect = document.getElementById("subcategorySelect");

  function updateSubcategories() {
    const categoryId = categorySelect.value;

    // Kosongkan subkategori lama
    subcategorySelect.innerHTML = '<option value="" disabled selected>Pilih Subkategori</option>';

    // Filter subkategori berdasarkan kategori yang dipilih
    subcategories.forEach(subcat => {
      if (subcat.category_id == categoryId) {
        const option = document.createElement("option");
        option.value = subcat.id;
        option.textContent = subcat.name;

        // Jika subkategori sebelumnya dipilih, maka tetap selected
        if (subcat.id == <?= $products["subcategory_id"]; ?>) {
          option.selected = true;
        }

        subcategorySelect.appendChild(option);
      }
    });
  }

  // Saat halaman dimuat, perbarui subkategori sesuai kategori yang dipilih sebelumnya
  updateSubcategories();

  // Saat kategori berubah, update subkategori
  categorySelect.addEventListener("change", updateSubcategories);



  let cropper;
  const inputGambar = document.getElementById('image');
  const preview = document.getElementById('preview');
  const croppedImageInput = document.getElementById('croppedImage');

  inputGambar.addEventListener('change', function (event) {
    let file = event.target.files[0];
    if (file) {
      let reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.parentElement.style.display = 'block';

        // Hapus instance Cropper.js sebelumnya
        if (cropper) {
          cropper.destroy();
        }

        // Inisialisasi Cropper.js (Auto Crop, Persegi)
        cropper = new Cropper(preview, {
          aspectRatio: 1, // Kotak (1:1)
          viewMode: 2,  // Mode agar gambar bisa digeser
          autoCropArea: 1,
          movable: true, // Bisa digeser
          zoomable: true, // Bisa zoom dengan scroll
          rotatable: true, // Bisa diputar
          scalable: true, // Bisa diperbesar/kecil
          wheelZoomRatio: 0.1, // Sensitivitas zoom
          dragMode: 'move', // Mode geser
          crop() {
            let croppedCanvas = cropper.getCroppedCanvas({
              width: 1200,
              height: 1200
            });

            croppedImageInput.value = croppedCanvas.toDataURL('image/jpeg');
          }
        });
      };
      reader.readAsDataURL(file);
    }
  });

  function formatCurrency(value, currencySymbol = "$", thousandSeparator = ",", decimalSeparator = ".") {
    let numberString = value.replace(/[^0-9]/g, ""), // Hanya angka
      split = numberString.split(decimalSeparator),
      remainder = split[0].length % 3,
      formatted = split[0].substr(0, remainder),
      thousands = split[0].substr(remainder).match(/\d{3}/g);

    if (thousands) {
      let separator = remainder ? thousandSeparator : "";
      formatted += separator + thousands.join(thousandSeparator);
    }

    formatted = split[1] !== undefined ? formatted + decimalSeparator + split[1] : formatted;
    return currencySymbol + " " + formatted;
  }

  // Apply formatting on input
  document.getElementById("price").addEventListener("input", function (event) {
    this.value = formatCurrency(this.value, "Rp", ".", ","); // Example for Indonesian Rupiah
  });

</script>