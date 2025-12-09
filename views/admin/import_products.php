<div class="page">
  <h2>Import Products SQL</h2>
  <p>Upload a <code>.sql</code> file exported from this app to replace the <strong>products</strong> table.
     The import may drop and recreate the table as defined in the file.</p>

  <?php if (!empty($flash)): ?>
    <div class="flash-wrapper">
      <?php foreach ($flash as $f): ?>
        <div class="flash <?= htmlspecialchars($f['type']) ?>"><?= htmlspecialchars($f['message']) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($this->baseUrl('admin/import-products')) ?>" class="card" style="max-width:520px;padding:1rem;">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? $this->csrfToken()) ?>">
    <div class="form-group" style="margin-bottom:1rem;">
      <label for="sql_file">Select .sql file</label>
      <input id="sql_file" name="sql_file" type="file" accept=".sql" required>
    </div>
    <div class="modal-actions" style="display:flex;gap:.5rem;">
      <button type="submit" class="btn lg">Import SQL</button>
      <a class="btn lg light" href="<?= htmlspecialchars($this->baseUrl('admin/dashboard')) ?>">Cancel</a>
    </div>
  </form>

  <p style="margin-top:1rem;color:#a33;">
    Warning: Importing will overwrite existing products data according to the uploaded file.
  </p>
</div>
