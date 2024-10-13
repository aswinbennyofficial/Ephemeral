document.addEventListener('DOMContentLoaded', function() {
    // File upload preview
    const fileInput = document.querySelector('input[type="file"]');
    const filePreview = document.getElementById('file-preview');

    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    filePreview.innerHTML = `
                        <p>Selected file: ${file.name}</p>
                        <p>Size: ${(file.size / 1024).toFixed(2)} KB</p>
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                filePreview.innerHTML = '';
            }
        });
    }

    // Copy share link to clipboard
    const shareBtns = document.querySelectorAll('.share-btn');
    shareBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const link = e.target.dataset.link;
            navigator.clipboard.writeText(link).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy link: ', err);
            });
        });
    });
});