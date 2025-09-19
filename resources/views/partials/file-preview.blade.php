{{-- resources/views/partials/file-preview.blade.php --}}
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="filePreviewTitle">Pratinjau File</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="filePreviewContent" class="d-flex justify-content-center align-items-center" style="min-height: 40vh;"></div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const modalEl   = document.getElementById('filePreviewModal');
  const modal     = new bootstrap.Modal(modalEl);
  const titleEl   = document.getElementById('filePreviewTitle');
  const contentEl = document.getElementById('filePreviewContent');

  window.previewFile = function(url, title, mime){
    titleEl.textContent = title || 'Pratinjau File';
    contentEl.innerHTML = '';

    if (mime && (mime.startsWith('image/') || mime === 'application/pdf')) {
      const embed = document.createElement('embed');
      embed.src   = url;
      embed.type  = mime;
      embed.style.width  = '100%';
      embed.style.height = '70vh';
      contentEl.appendChild(embed);
    } else {
      const box = document.createElement('div');
      box.className = 'text-center text-muted';
      box.innerHTML = `
        <p>Tipe file ini tidak didukung untuk pratinjau.</p>
        <a href="${url}" class="btn btn-primary mt-2" target="_blank" rel="noopener">Unduh File</a>
      `;
      contentEl.appendChild(box);
    }

    modal.show();
  };
})();
</script>
@endpush
