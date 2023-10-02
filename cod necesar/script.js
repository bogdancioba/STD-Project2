document.getElementById('fileToUpload').addEventListener('change', function (e) {
    var fileSize = this.files[0].size;
    if (fileSize > 1000000) {  // Limita dimensiunii fișierului la 1MB
        alert('Fișierul este prea mare!');
        this.value = '';  // Resetați valoarea input-ului pentru a elimina fișierul selectat
    }
});
