<dialog id="error-dialog" class="modal error-modal" aria-labelledby="error-title" aria-describedby="error-messages"<?= !empty($errors)?' open data-open-error':'' ?>>
<form method="dialog"><button class="icon-button modal-close" aria-label="Tutup notifikasi error" value="close">×</button></form>
<div class="empty-icon"><?= icon('shield') ?></div><h2 id="error-title">Permintaan belum berhasil</h2>
<ul id="error-messages"><?php foreach(array_unique(array_map('strval',$errors??[])) as $message):?><li><?= e($message) ?></li><?php endforeach;?></ul>
<form method="dialog"><button class="button secondary" value="close">Tutup</button></form></dialog>
