<?php if ($deviceType === DEVICE_HANDHELD) { ?>
<footer class="footer">
    <div class="buttons">
        <button id="btn-shutdown" type="button">Shut down</button>
    </div>

    <div class="status-icons">
        <?php if ($offlineDevice) { ?>
        <div class="upload-status"></div>
        <div class="connectivity"></div>
        <?php } ?>
        <div class="battery-level"></div>
    </div>
</footer>
<?php } ?>
